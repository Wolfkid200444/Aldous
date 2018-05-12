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

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class CompressBatchedTask extends AsyncTask{
	/** @var string */
	private $uncompressedPayload;
	/** @var int */
	private $compressionLevel;

	/**
	 * @param CompressedPacketBuffer $buffer
	 * @param string                 $uncompressedPayload
	 * @param int                    $compressionLevel
	 * @param PlayerNetworkSession[] $targets
	 */
	public function __construct(CompressedPacketBuffer $buffer, string $uncompressedPayload, int $compressionLevel, array $targets){
		$this->storeLocal(["targets" => $targets, "batch" => $buffer]);

		$this->uncompressedPayload = $uncompressedPayload;
		$this->compressionLevel = $compressionLevel;
	}

	public function onRun() : void{
		$batch = new PacketBuffer($this->uncompressedPayload);
		$this->uncompressedPayload = null;

		$this->setResult($batch->compress($this->compressionLevel), false);
	}

	public function onCompletion(Server $server) : void{
		$data = $this->fetchLocal();

		/** @var PlayerNetworkSession[] $targets */
		$targets = $data["targets"];
		/** @var CompressedPacketBuffer $buffer */
		$buffer = $data["batch"];
		$buffer->setBuffer($this->getResult());

		$server->broadcastPacketsCallback($targets);
	}
}
