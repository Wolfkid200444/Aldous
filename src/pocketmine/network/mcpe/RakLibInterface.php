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

use pocketmine\network\AdvancedNetworkInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\Network;
use pocketmine\Server;
use pocketmine\snooze\SleeperNotifier;
use raklib\protocol\EncapsulatedPacket;
use raklib\RakLib;
use raklib\server\RakLibServer;
use raklib\server\ServerHandler;
use raklib\server\ServerInstance;
use raklib\utils\InternetAddress;

class RakLibInterface implements ServerInstance, AdvancedNetworkInterface{
	/**
	 * Sometimes this gets changed when the MCPE-layer protocol gets broken to the point where old and new can't
	 * communicate. It's important that we check this to avoid catastrophes.
	 */
	private const MCPE_RAKNET_PROTOCOL_VERSION = 8;

	/** @var Server */
	private $server;

	/** @var Network */
	private $network;

	/** @var RakLibServer */
	private $rakLib;

	/** @var RakLibSession[] */
	private $sessions = [];

	/** @var ServerHandler */
	private $interface;

	/** @var SleeperNotifier */
	private $sleeper;

	public function __construct(Server $server){
		$this->server = $server;

		$this->sleeper = new SleeperNotifier();
		$server->getTickSleeper()->addNotifier($this->sleeper, function() : void{
			$this->server->getNetwork()->processInterface($this);
		});

		$this->rakLib = new RakLibServer(
			$this->server->getLogger(),
			\pocketmine\COMPOSER_AUTOLOADER_PATH,
			new InternetAddress($this->server->getIp(), $this->server->getPort(), 4),
			(int) $this->server->getProperty("network.max-mtu-size", 1492),
			self::MCPE_RAKNET_PROTOCOL_VERSION,
			$this->sleeper
		);
		$this->interface = new ServerHandler($this->rakLib, $this);
	}

	public function start() : void{
		$this->rakLib->start(PTHREADS_INHERIT_CONSTANTS | PTHREADS_INHERIT_INI); //HACK: MainLogger needs INI and constants
	}

	public function setNetwork(Network $network) : void{
		$this->network = $network;
	}

	public function processEvents() : void{
		while($this->interface->handlePacket());
	}

	public function tick() : void{
		if(!$this->rakLib->isRunning() and !$this->rakLib->isShutdown()){
			throw new \Exception("RakLib Thread crashed");
		}
	}

	public function closeSession(string $identifier, string $reason) : void{
		if(isset($this->sessions[$identifier])){
			$session = $this->sessions[$identifier];
			unset($this->sessions[$identifier]);
			$session->onClientDisconnect($reason);
		}
	}

	public function close(RakLibSession $session, string $reason = "unknown reason") : void{
		unset($this->sessions[$session->getSessionIdentifier()]);
		$this->interface->closeSession($session->getSessionIdentifier(), $reason);
	}

	public function shutdown() : void{
		$this->server->getTickSleeper()->removeNotifier($this->sleeper);
		$this->interface->shutdown();
	}

	public function emergencyShutdown() : void{
		$this->server->getTickSleeper()->removeNotifier($this->sleeper);
		$this->interface->emergencyShutdown();
	}

	public function openSession(string $identifier, string $address, int $port, int $clientID) : void{
		$this->sessions[$identifier] = new RakLibSession($this->server, $this, $address, $port, $identifier);
	}

	public function handleEncapsulated(string $identifier, EncapsulatedPacket $packet, int $flags) : void{
		if(isset($this->sessions[$identifier])){
			$session = $this->sessions[$identifier];

			try{
				$session->handleEncapsulated($packet);
			}catch(\Throwable $e){
				$logger = $this->server->getLogger();
				$logger->debug("EncapsulatedPacket 0x" . bin2hex($packet->buffer));
				$logger->logException($e);

				$this->interface->blockAddress($session->getIp(), 5);
			}
		}
	}

	public function blockAddress(string $address, int $timeout = 300) : void{
		$this->interface->blockAddress($address, $timeout);
	}

	public function unblockAddress(string $address) : void{
		$this->interface->unblockAddress($address);
	}

	public function handleRaw(string $address, int $port, string $payload) : void{
		$this->server->handlePacket($this, $address, $port, $payload);
	}

	public function sendRawPacket(string $address, int $port, string $payload) : void{
		$this->interface->sendRaw($address, $port, $payload);
	}

	public function notifyACK(string $identifier, int $identifierACK) : void{

	}

	public function setName(string $name) : void{
		$info = $this->server->getQueryInformation();

		$this->interface->sendOption("name", implode(";",
			[
				"MCPE",
				rtrim(addcslashes($name, ";"), '\\'),
				ProtocolInfo::CURRENT_PROTOCOL,
				ProtocolInfo::MINECRAFT_VERSION_NETWORK,
				$info->getPlayerCount(),
				$info->getMaxPlayerCount(),
				$this->rakLib->getServerId(),
				$this->server->getName(),
				Server::getGamemodeName($this->server->getGamemode())
			]) . ";"
		);
	}

	public function setPortCheck(bool $name) : void{
		$this->interface->sendOption("portChecking", $name);
	}

	public function handleOption(string $option, string $value) : void{
		if($option === "bandwidth"){
			$v = unserialize($value);
			$this->network->addStatistics($v["up"], $v["down"]);
		}
	}

	public function sendEncapsulated(string $identifier, string $payload, int $reliability, ?int $orderChannel = null, bool $immediate = false) : void{
		$encapsulatedPacket = new EncapsulatedPacket();
		$encapsulatedPacket->buffer = $payload;
		$encapsulatedPacket->reliability = $reliability;
		$encapsulatedPacket->orderChannel = $orderChannel;

		$this->interface->sendEncapsulated($identifier, $encapsulatedPacket, $immediate ? RakLib::PRIORITY_IMMEDIATE : RakLib::PRIORITY_NORMAL);
	}

	public function updatePing(string $identifier, int $pingMS) : void{
		if(isset($this->sessions[$identifier])){
			$this->sessions[$identifier]->updatePing($pingMS);
		}
	}
}
