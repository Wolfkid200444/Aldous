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

namespace pocketmine\level\format;

use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;

class SubChunk implements SubChunkInterface{

	/** @var int[] */
	protected $ids = [];
	/** @var int[] */
	protected $data = [];
	/** @var int[] */
	protected $blockLight = [];
	/** @var int[] */
	protected $skyLight = [];

	private static function assignData(&$target, array $data, int $length, int $value = 0){
		if(count($data) !== $length){
			assert($data === "", "Invalid non-zero length given, expected $length, got " . count($data));
			$target = array_fill(0, $length, $value);
		}else{
			$target = $data;
		}
	}

	public function __construct(array $ids = [], array $data = [], array $skyLight = [], array $blockLight = []){
		self::assignData($this->ids, $ids, 4096);
		self::assignData($this->data, $data, 4096);
		self::assignData($this->skyLight, $skyLight, 4096, 15);
		self::assignData($this->blockLight, $blockLight, 4096);
	}

	public function isEmpty(bool $checkLight = true) : bool{
		function isFullWith(array $array, $targetValue) : bool{
			foreach($array as $v){
				if($v !== $targetValue){
					return false;
				}
			}

			return true;
		}

		return (
			isFullWith($this->ids, 0) and
			(!$checkLight or (
				isFullWith($this->skyLight, 15) and
				isFullWith($this->blockLight, 0)
			))
		);
	}

	public static function getIndex(int $x, int $y, int $z) : int{
		return ($x * 256) + ($y * 16) + $z;
	}

	public function getBlockId(int $x, int $y, int $z) : int{
		return $this->ids[self::getIndex($x, $y, $z)];
	}

	public function setBlockId(int $x, int $y, int $z, int $id) : bool{
		$this->ids[self::getIndex($x, $y, $z)] = $id;
		return true;
	}

	public function getBlockData(int $x, int $y, int $z) : int{
		return $this->data[self::getIndex($x, $y, $z)];
	}

	public function setBlockData(int $x, int $y, int $z, int $data) : bool{
		$this->data[self::getIndex($x, $y, $z)] = $data;
		return true;
	}

	public function getFullBlock(int $x, int $y, int $z) : int{
		$i = self::getIndex($x, $y, $z);
		return ($this->ids[$i] << 4) | $this->data[$i];
	}

	public function setBlock(int $x, int $y, int $z, ?int $id = null, ?int $data = null) : bool{
		$i = self::getIndex($x, $y, $z);
		$changed = false;
		if($id !== null){
			if($this->ids[$i] !== $id){
				$this->ids[$i] = $id;
				$changed = true;
			}
		}

		if($data !== null){
			if($this->data[$i] !== $data){
				$this->data[$i] = $data;
				$changed = true;
			}
		}

		return $changed;
	}

	public function getBlockLight(int $x, int $y, int $z) : int{
		return $this->blockLight[self::getIndex($x, $y, $z)];
	}

	public function setBlockLight(int $x, int $y, int $z, int $level) : bool{
		$this->blockLight[self::getIndex($x, $y, $z)] = $level;
		return true;
	}

	public function getBlockSkyLight(int $x, int $y, int $z) : int{
		return $this->skyLight[self::getIndex($x, $y, $z)];
	}

	public function setBlockSkyLight(int $x, int $y, int $z, int $level) : bool{
		$this->skyLight[self::getIndex($x, $y, $z)] = $level;
		return true;
	}

	public function getHighestBlockAt(int $x, int $z) : int{
		for($y = 15; $y >= 0; $y--){
			if(($id = $this->getBlockId($x, $y, $z)) !== 0){
				return $y;
			}
		}

		return -1; //highest block not in this subchunk
	}

	public function getBlockIdColumn(int $x, int $z) : array{
		$column = [];
		for($y = 0; $y < 16; $y++){
			$column[self::getIndex($x, $y, $z)] = $this->getBlockId($x, $y, $z);
		}
		return $column;
	}

	public function getBlockDataColumn(int $x, int $z) : array{
		$column = [];
		for($y = 0; $y < 16; $y++){
			$column[self::getIndex($x, $y, $z)] = $this->getBlockData($x, $y, $z);
		}
		return $column;
	}

	public function getBlockLightColumn(int $x, int $z) : array{
		$column = [];
		for($y = 0; $y < 16; $y++){
			$column[self::getIndex($x, $y, $z)] = $this->getBlockLight($x, $y, $z);
		}
		return $column;
	}

	public function getBlockSkyLightColumn(int $x, int $z) : array{
		$column = [];
		for($y = 0; $y < 16; $y++){
			$column[self::getIndex($x, $y, $z)] = $this->getBlockSkyLight($x, $y, $z);
		}
		return $column;
	}

	public function getBlockIdArray() : array{
		assert(count($this->ids) === 4096, "Wrong length of ID array, expecting 4096 bytes, got " . strlen($this->ids));
		return $this->ids;
	}

	public function getBlockDataArray() : array{
		assert(count($this->data) === 4096, "Wrong length of data array, expecting 2048 bytes, got " . strlen($this->data));
		return $this->data;
	}

	public function getBlockSkyLightArray() : array{
		assert(count($this->skyLight) === 4096, "Wrong length of skylight array, expecting 2048 bytes, got " . strlen($this->skyLight));
		return $this->skyLight;
	}

	public function setBlockSkyLightArray(array $data){
		assert(count($data) === 4096, "Wrong length of skylight array, expecting 2048 bytes, got " . strlen($data));
		$this->skyLight = $data;
	}

	public function getBlockLightArray() : array{
		assert(count($this->blockLight) === 4096, "Wrong length of light array, expecting 2048 bytes, got " . strlen($this->blockLight));
		return $this->blockLight;
	}

	public function setBlockLightArray(array $data){
		assert(count($data) === 4096, "Wrong length of light array, expecting 2048 bytes, got " . strlen($data));
		$this->blockLight = $data;
	}

	public function networkSerialize() : string{
		return chr(Chunk::CURRENT_SUB_CHUNK_VERSION) . $data;
	}

	public function fastSerialize() : string{
		$ids = "";
		$data = "";
		$skyLight = "";
		$blockLight = "";

		for($i = 0; $i < 4096; $i++){
			$ids .= Binary::writeVarInt($)
		}
		return
			$ids .
			$data .
			$skyLight .
			$blockLight;
	}

	public static function fastDeserialize(string $data) : SubChunk{
		return new SubChunk(
			substr($data,    0, 4096), //ids
			substr($data, 4096, 2048), //data
			substr($data, 6144, 2048), //sky light
			substr($data, 8192, 2048)  //block light
		);
	}

	public function __debugInfo(){
		return [];
	}
}
