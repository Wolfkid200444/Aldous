<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\NetworkInterface;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\network\mcpe\protocol\BlockPickRequestPacket;
use pocketmine\network\mcpe\protocol\BookEditPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\ClientToServerHandshakePacket;
use pocketmine\network\mcpe\protocol\CommandBlockUpdatePacket;
use pocketmine\network\mcpe\protocol\CommandRequestPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\CraftingEventPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\network\mcpe\protocol\EntityFallPacket;
use pocketmine\network\mcpe\protocol\EntityPickRequestPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerHotbarPacket;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\ServerSettingsRequestPacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\ShowCreditsPacket;
use pocketmine\network\mcpe\protocol\SpawnExperienceOrbPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\timings\Timings;

abstract class ServerPlayerNetworkSession extends NetworkSession implements IPlayerNetworkSession{

	/** @var string */
	protected $ip;
	/** @var int */
	protected $port;
	/** @var int */
	protected $lastPingMeasure = 1;

	/** @var Server */
	protected $server;
	/** @var Player */
	protected $player;
	/** @var RakLibInterface */
	protected $interface;

	/** @var bool */
	protected $connected = true;

	/** @var bool */
	protected $loggedIn = false;

	/** @var PacketBuffer */
	protected $batchBuffer;

	/** @var \SplQueue|CompressedPacketBuffer[] */
	protected $batchQueue;

	public function __construct(Server $server, NetworkInterface $interface, string $ip, int $port){
		$this->server = $server;
		$this->interface = $interface;
		$this->ip = $ip;
		$this->port = $port;

		$this->batchQueue = new \SplQueue();

		//TODO: this shouldn't happen here, it should happen during the login sequence
		$this->player = $this->server->createPlayer($this);
	}

	public function getIp() : string{
		return $this->ip;
	}

	public function getPort() : int{
		return $this->port;
	}

	public function getPing() : int{
		return $this->lastPingMeasure;
	}

	/**
	 * @internal Called by the network interface to update session ping measurements.
	 *
	 * @param int $pingMS
	 */
	public function updatePing(int $pingMS) : void{
		$this->lastPingMeasure = $pingMS;
	}

	public function getInterface() : NetworkInterface{
		return $this->interface;
	}

	public function setLoggedIn() : void{
		$this->loggedIn = true;
	}

	public function isConnected() : bool{
		return $this->connected;
	}

	protected function handleDataPacket(DataPacket $packet) : void{
		$timings = Timings::getReceiveDataPacketTimings($packet);
		$timings->startTiming();

		$packet->decode();
		if(!$packet->feof() and !$packet->mayHaveUnreadBytes()){
			$remains = substr($packet->buffer, $packet->offset);
			$this->server->getLogger()->debug("Still " . strlen($remains) . " bytes unread in " . $packet->getName() . ": 0x" . bin2hex($remains));
		}

		$this->server->getPluginManager()->callEvent($ev = new DataPacketReceiveEvent($this->player, $packet));
		if(!$ev->isCancelled() and !$packet->handle($this)){
			$this->server->getLogger()->debug("Unhandled " . $packet->getName() . " received from " . $this->player->getName() . ": 0x" . bin2hex($packet->buffer));
		}

		$timings->stopTiming();
	}

	/**
	 * @param DataPacket $packet
	 * @param bool       $immediateFlush
	 *
	 * @return bool
	 */
	public function sendDataPacket(DataPacket $packet, bool $immediateFlush = false) : bool{
		//Basic safety restriction. TODO: improve this
		if(!$this->loggedIn and !$packet->canBeSentBeforeLogin()){
			throw new \InvalidArgumentException("Attempted to send " . get_class($packet) . " to " . $this->player->getName() . " too early");
		}

		$timings = Timings::getSendDataPacketTimings($packet);
		$timings->startTiming();
		try{
			$this->server->getPluginManager()->callEvent($ev = new DataPacketSendEvent($this->player, $packet));
			if($ev->isCancelled()){
				return false;
			}

			$this->addToBatchBuffer($packet);
			if($immediateFlush){
				$this->flushBatchBuffer($immediateFlush);
			}

			return true;
		}finally{
			$timings->stopTiming();
		}
	}

	/**
	 * Adds a packet to the session list to be sent in a batch at the next available opportunity.
	 *
	 * @param DataPacket $packet
	 */
	private function addToBatchBuffer(DataPacket $packet) : void{
		if($this->batchBuffer === null){
			$this->batchBuffer = new PacketBuffer();
		}

		$this->batchBuffer->addPacket($packet);
	}

	/**
	 * Flushes pending buffered packets in a single batch to the network.
	 *
	 * @param bool $immediateFlush
	 */
	private function flushBatchBuffer(bool $immediateFlush = false) : void{
		if($this->batchBuffer !== null){
			//this might sync-send and call back to this again, so make sure we don't double-flush
			$buf = $this->batchBuffer;
			$this->batchBuffer = null;

			$this->server->prepareBatch([$this], $buf, $immediateFlush, $immediateFlush);
		}
	}

	public function serverDisconnect(string $reason = "", bool $mcpeDisconnect = true) : void{
		if($this->connected){
			$this->connected = false;

			if($mcpeDisconnect){
				$pk = new DisconnectPacket();
				$pk->message = $reason;
				$pk->hideDisconnectionScreen = $reason === "";
				$this->sendDataPacket($pk, true);
			}

			$this->disconnectFromInterface($reason);

			$this->player = null;
			$this->interface = null;
		}
	}

	abstract protected function disconnectFromInterface(string $reason) : void;

	/**
	 * @internal Called by the network interface when a player disconnects of their own accord.
	 *
	 * @param string $reason
	 */
	public function onClientDisconnect(string $reason) : void{
		if($this->connected){
			$this->connected = false;

			$this->player->close($this->player->getLeaveMessage(), $reason);

			$this->player = null;
			$this->interface = null;
		}
	}


	public function notifyPendingBatch(CompressedPacketBuffer $buffer) : void{
		$this->flushBatchBuffer();
		$this->batchQueue->enqueue($buffer);
	}

	public function sendPreparedBatch(CompressedPacketBuffer $buffer, bool $immediateFlush = false) : void{
		$this->flushBatchBuffer($immediateFlush);
		$this->batchQueue->enqueue($buffer);
		$this->flushBatchQueue($immediateFlush);
	}

	public function flushBatchQueue(bool $immediateFlush = false) : void{
		while(!$this->batchQueue->isEmpty()){
			/** @var CompressedPacketBuffer $nextBatch */
			$nextBatch = $this->batchQueue->bottom();
			if($nextBatch->isReady()){
				//this gets modified by the async task preparing it
				$this->batchQueue->dequeue();

				//TODO: encryption

				$this->sendBatch($nextBatch, $immediateFlush);
			}else{
				//we're still waiting for this one being async-prepared
				break;
			}
		}
	}

	abstract protected function sendBatch(CompressedPacketBuffer $buffer, bool $immediateFlush) : void;

	/**
	 * @internal Called by RakLibInterface every tick to flush buffered packets.
	 */
	public function tick() : void{
		if($this->batchBuffer !== null){
			$this->flushBatchBuffer();
		}
	}

	public function handleLogin(LoginPacket $packet) : bool{
		return $this->player->handleLogin($packet);
	}

	public function handleClientToServerHandshake(ClientToServerHandshakePacket $packet) : bool{
		return false; //TODO
	}

	public function handleResourcePackClientResponse(ResourcePackClientResponsePacket $packet) : bool{
		return $this->player->handleResourcePackClientResponse($packet);
	}

	public function handleText(TextPacket $packet) : bool{
		if($packet->type === TextPacket::TYPE_CHAT){
			return $this->player->chat($packet->message);
		}

		return false;
	}

	public function handleMovePlayer(MovePlayerPacket $packet) : bool{
		return $this->player->handleMovePlayer($packet);
	}

	public function handleLevelSoundEvent(LevelSoundEventPacket $packet) : bool{
		return $this->player->handleLevelSoundEvent($packet);
	}

	public function handleEntityEvent(EntityEventPacket $packet) : bool{
		return $this->player->handleEntityEvent($packet);
	}

	public function handleInventoryTransaction(InventoryTransactionPacket $packet) : bool{
		return $this->player->handleInventoryTransaction($packet);
	}

	public function handleMobEquipment(MobEquipmentPacket $packet) : bool{
		return $this->player->handleMobEquipment($packet);
	}

	public function handleMobArmorEquipment(MobArmorEquipmentPacket $packet) : bool{
		return true; //Not used
	}

	public function handleInteract(InteractPacket $packet) : bool{
		return $this->player->handleInteract($packet);
	}

	public function handleBlockPickRequest(BlockPickRequestPacket $packet) : bool{
		return $this->player->handleBlockPickRequest($packet);
	}

	public function handleEntityPickRequest(EntityPickRequestPacket $packet) : bool{
		return false; //TODO
	}

	public function handlePlayerAction(PlayerActionPacket $packet) : bool{
		return $this->player->handlePlayerAction($packet);
	}

	public function handleEntityFall(EntityFallPacket $packet) : bool{
		return true; //Not used
	}

	public function handleAnimate(AnimatePacket $packet) : bool{
		return $this->player->handleAnimate($packet);
	}

	public function handleContainerClose(ContainerClosePacket $packet) : bool{
		return $this->player->handleContainerClose($packet);
	}

	public function handlePlayerHotbar(PlayerHotbarPacket $packet) : bool{
		return true; //this packet is useless
	}

	public function handleCraftingEvent(CraftingEventPacket $packet) : bool{
		return true; //this is a broken useless packet, so we don't use it
	}

	public function handleAdventureSettings(AdventureSettingsPacket $packet) : bool{
		return $this->player->handleAdventureSettings($packet);
	}

	public function handleBlockEntityData(BlockEntityDataPacket $packet) : bool{
		return $this->player->handleBlockEntityData($packet);
	}

	public function handlePlayerInput(PlayerInputPacket $packet) : bool{
		return false; //TODO
	}

	public function handleSetPlayerGameType(SetPlayerGameTypePacket $packet) : bool{
		return $this->player->handleSetPlayerGameType($packet);
	}

	public function handleSpawnExperienceOrb(SpawnExperienceOrbPacket $packet) : bool{
		return false; //TODO
	}

	public function handleMapInfoRequest(MapInfoRequestPacket $packet) : bool{
		return false; //TODO
	}

	public function handleRequestChunkRadius(RequestChunkRadiusPacket $packet) : bool{
		$this->player->setViewDistance($packet->radius);

		return true;
	}

	public function handleItemFrameDropItem(ItemFrameDropItemPacket $packet) : bool{
		return $this->player->handleItemFrameDropItem($packet);
	}

	public function handleBossEvent(BossEventPacket $packet) : bool{
		return false; //TODO
	}

	public function handleShowCredits(ShowCreditsPacket $packet) : bool{
		return false; //TODO: handle resume
	}

	public function handleCommandRequest(CommandRequestPacket $packet) : bool{
		return $this->player->chat($packet->command);
	}

	public function handleCommandBlockUpdate(CommandBlockUpdatePacket $packet) : bool{
		return false; //TODO
	}

	public function handleResourcePackChunkRequest(ResourcePackChunkRequestPacket $packet) : bool{
		return $this->player->handleResourcePackChunkRequest($packet);
	}

	public function handlePlayerSkin(PlayerSkinPacket $packet) : bool{
		return $this->player->changeSkin($packet->skin, $packet->newSkinName, $packet->oldSkinName);
	}

	public function handleBookEdit(BookEditPacket $packet) : bool{
		return $this->player->handleBookEdit($packet);
	}

	public function handleModalFormResponse(ModalFormResponsePacket $packet) : bool{
		return false; //TODO: GUI stuff
	}

	public function handleServerSettingsRequest(ServerSettingsRequestPacket $packet) : bool{
		return false; //TODO: GUI stuff
	}
}
