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

use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\SourceInterface;
use pocketmine\Server;
use raklib\protocol\EncapsulatedPacket;
use raklib\protocol\PacketReliability;

class RakLibSession extends ServerPlayerNetworkSession{
	/**
	 * Header byte at the start of every MCPE RakNet packet
	 */
	private const MCPE_MAGIC_BYTE = "\xfe";

	/** @var string */
	private $sessionIdentifier;

	/** @var RakLibInterface */
	protected $interface;

	public function __construct(Server $server, RakLibInterface $interface, string $ip, int $port, string $sessionIdentifier){
		parent::__construct($server, $interface, $ip, $port);
		$this->sessionIdentifier = $sessionIdentifier;
	}

	/**
	 * @noinspection SenselessProxyMethodInspection
	 *
	 * @return RakLibInterface
	 */
	public function getInterface() : SourceInterface{
		return parent::getInterface();
	}

	/**
	 * @internal Returns the RakLib session identifier.
	 *
	 * @return string
	 */
	public function getSessionIdentifier() : string{
		return $this->sessionIdentifier;
	}

	/**
	 * @internal Called by RakLibInterface when an encapsulated packet is received for this session.
	 *
	 * @param EncapsulatedPacket $packet
	 *
	 * @return bool
	 */
	public function handleEncapsulated(EncapsulatedPacket $packet) : bool{
		if($packet->buffer{0} !== self::MCPE_MAGIC_BYTE){
			return false;
		}

		//TODO: decryption if encryption is enabled

		//Skip 0xfe byte
		$batch = PacketBuffer::decompress(substr($packet->buffer, 1));
		foreach($batch->getPackets() as $str){
			$pk = PacketPool::getPacket($str);
			$this->handleDataPacket($pk);
		}

		return true;
	}

	protected function disconnectFromInterface(string $reason) : void{
		$this->interface->close($this, $reason);
	}

	protected function sendBatch(CompressedPacketBuffer $buffer, bool $immediateFlush) : void{
		$this->interface->sendEncapsulated($this->sessionIdentifier, self::MCPE_MAGIC_BYTE . $buffer->getBuffer(), PacketReliability::RELIABLE_ORDERED, 0, $immediateFlush);
	}
}
