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

namespace pocketmine\level\light;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\level\ChunkManager;

class ChunkLightPopulator{
	/** @var ChunkManager */
	protected $level;

	/** @var int */
	protected $chunkX;
	/** @var int */
	protected $chunkZ;

	protected $blockLightUpdates = null;
	protected $skyLightUpdates = null;

	public function __construct(ChunkManager $level, int $chunkX, int $chunkZ){
		$this->level = $level;
		$this->chunkX = $chunkX;
		$this->chunkZ = $chunkZ;

		$this->blockLightUpdates = new BlockLightUpdate($level);
		$this->skyLightUpdates = new SkyLightUpdate($level);
	}

	public function populate(){
		$chunk = $this->level->getChunk($this->chunkX, $this->chunkZ);

		$chunk->setAllBlockSkyLight(0);
		$chunk->setAllBlockLight(0);

		$maxY = $chunk->getMaxY();

		for($x = $this->chunkX << 4, $maxX = $x + 16; $x < $maxX; ++$x){
			for($z = $this->chunkZ << 4, $maxZ = $z + 16; $z < $maxZ; ++$z){
				$heightMap = $chunk->getHeightMap($x & 0x0f, $z & 0x0f);
				$heightMapMax = max(
					$this->level->getHeightMap($x + 1, $z),
					$this->level->getHeightMap($x - 1, $z),
					$this->level->getHeightMap($x, $z + 1),
					$this->level->getHeightMap($x, $z - 1)
				);

				for($y = $maxY; $y >= 0; --$y){
					if($y >= $heightMap){
						if($y === $heightMap or $y < $heightMapMax){
							$this->skyLightUpdates->setAndUpdateLight($x, $y, $z, 15);
						}else{
							$chunk->setBlockSkyLight($x & 0x0f, $y, $z & 0x0f, 15);
						}
					}

					if(($blockLight = BlockFactory::$light[$chunk->getBlockId($x & 0x0f, $y, $z & 0x0f)]) > 0){
						$this->blockLightUpdates->setAndUpdateLight($x, $y, $z, $blockLight);
					}
				}
			}
		}

		$this->blockLightUpdates->execute();
		$this->skyLightUpdates->execute();

	}
}