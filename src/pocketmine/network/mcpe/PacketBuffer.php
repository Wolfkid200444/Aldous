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

#include <rules/DataPacket.h>


use pocketmine\network\mcpe\protocol\DataPacket;

class PacketBuffer extends NetworkBinaryStream{

	public function compress(int $compressionLevel) : string{
		return \zlib_encode($this->buffer, ZLIB_ENCODING_DEFLATE, $compressionLevel);
	}

	public static function decompress(string $payload) : PacketBuffer{
		$batch = new self();
		$batch->buffer = \zlib_decode($payload, 1024 * 1024 * 64); //Max 64MB

		return $batch;
	}

	/**
	 * @param DataPacket $packet
	 */
	public function addPacket(DataPacket $packet) : void{
		if(!$packet->isEncoded){
			$packet->encode();
		}

		$this->putString($packet->buffer);
	}

	/**
	 * @return \Generator|string[]
	 */
	public function getPackets(){
		while(!$this->feof()){
			yield $this->getString();
		}
	}
}
