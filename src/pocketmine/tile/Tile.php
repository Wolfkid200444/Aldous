<?php

/*
 *               _ _
 *         /\   | | |
 *        /  \  | | |_ __ _ _   _
 *       / /\ \ | | __/ _` | | | |
 *      / ____ \| | || (_| | |_| |
 *     /_/    \_|_|\__\__,_|\__, |
 *                           __/ |
 *                          |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author TuranicTeam
 * @link https://github.com/TuranicTeam/Altay
 *
 */

declare(strict_types=1);

/**
 * All the Tile classes and related classes
 */

namespace pocketmine\tile;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\timings\Timings;
use pocketmine\timings\TimingsHandler;
use pocketmine\utils\Utils;

abstract class Tile extends Position{

	public const TAG_ID = "id";
	public const TAG_X = "x";
	public const TAG_Y = "y";
	public const TAG_Z = "z";

	public const BANNER = "Banner";
	public const BEACON = "Beacon";
	public const BED = "Bed";
	public const BREWING_STAND = "BrewingStand";
	public const CHEST = "Chest";
	public const ENCHANT_TABLE = "EnchantTable";
	public const ENDER_CHEST = "EnderChest";
	public const FLOWER_POT = "FlowerPot";
	public const FURNACE = "Furnace";
	public const HOPPER = "Hopper";
	public const ITEM_FRAME = "ItemFrame";
	public const JUKEBOX = "Jukebox";
	public const MOB_SPAWNER = "MobSpawner";
	public const NOTEBLOCK = "noteblock";
	public const SIGN = "Sign";
	public const SKULL = "Skull";
	public const SHULKER_BOX = "ShulkerBox";

	/** @var string[] classes that extend Tile */
	private static $knownTiles = [];
	/** @var string[][] */
	private static $saveNames = [];

	/** @var string */
	public $name = "";
	/** @var bool */
	public $closed = false;
	/** @var TimingsHandler */
	protected $timings;

	public static function init(){
		self::registerTile(Banner::class, [
			self::BANNER,
			"minecraft:banner"
		]);
		self::registerTile(Beacon::class, [
			self::BEACON,
			"minecraft:beacon"
		]);
		self::registerTile(Bed::class, [
			self::BED,
			"minecraft:bed"
		]);
		self::registerTile(Chest::class, [
			self::CHEST,
			"minecraft:chest"
		]);
		self::registerTile(EnchantTable::class, [
			self::ENCHANT_TABLE,
			"minecraft:enchanting_table"
		]);
		self::registerTile(EnderChest::class, [
			self::ENDER_CHEST,
			"minecraft:ender_chest"
		]);
		self::registerTile(FlowerPot::class, [
			self::FLOWER_POT,
			"minecraft:flower_pot"
		]);
		self::registerTile(Furnace::class, [
			self::FURNACE,
			"minecraft:furnace"
		]);
		self::registerTile(Hopper::class, [
			self::HOPPER,
			"minecraft:hopper"
		]);
		self::registerTile(ItemFrame::class, [self::ITEM_FRAME]); //this is an entity in PC
		self::registerTile(Jukebox::class, [
			self::JUKEBOX,
			"minecraft:jukebox"
		]);
		self::registerTile(NoteBlock::class, [
			self::NOTEBLOCK,
			"minecraft:noteblock"
		]);
		self::registerTile(Sign::class, [
			self::SIGN,
			"minecraft:sign"
		]);
		self::registerTile(Skull::class, [
			self::SKULL,
			"minecraft:skull"
		]);
		self::registerTile(ShulkerBox::class, [
			self::SHULKER_BOX,
			"minecraft:shulker_box"
		]);
		self::registerTile(MobSpawner::class, [
			self::MOB_SPAWNER,
			"minecraft:mob_spawner"
		]);

	}

	/**
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 *
	 * @return Tile|null
	 */
	public static function createFromData(Level $level, CompoundTag $nbt) : ?Tile{
		$type = $nbt->getString(self::TAG_ID, "", true);
		if($type === ""){
			return null;
		}
		$tile = self::create($type, $level, new Vector3($nbt->getInt(self::TAG_X), $nbt->getInt(self::TAG_Y), $nbt->getInt(self::TAG_Z)));
		if($tile !== null){
			$tile->readSaveData($nbt);
		}
		return $tile;
	}

	/**
	 * @param string  $type
	 * @param Level   $level
	 * @param Vector3 $pos
	 * @param Item    $item
	 *
	 * @return Tile|null
	 */
	public static function createFromItem(string $type, Level $level, Vector3 $pos, Item $item) : ?Tile{
		$tile = self::create($type, $level, $pos);
		if($tile !== null and $item->hasCustomBlockData()){
			$tile->readSaveData($item->getCustomBlockData());
		}
		if($tile instanceof Nameable and $item->hasCustomName()){ //this should take precedence over saved NBT
			$tile->setName($item->getCustomName());
		}

		return $tile;
	}

	/**
	 * @param string  $type
	 * @param Level   $level
	 * @param Vector3 $pos
	 *
	 * @return Tile|null
	 */
	public static function create(string $type, Level $level, Vector3 $pos) : ?Tile{
		if(isset(self::$knownTiles[$type])){
			$class = self::$knownTiles[$type];
			/**
			 * @var Tile $tile
			 * @see Tile::__construct()
			 */
			$tile = new $class($level, $pos);
			return $tile;
		}

		return null;
	}

	/**
	 * @param string   $className
	 * @param string[] $saveNames
	 *
	 * @throws \ReflectionException
	 */
	public static function registerTile(string $className, array $saveNames = []) : void{
		Utils::testValidInstance($className, Tile::class);

		$shortName = (new \ReflectionClass($className))->getShortName();
		if(!in_array($shortName, $saveNames, true)){
			$saveNames[] = $shortName;
		}

		foreach($saveNames as $name){
			self::$knownTiles[$name] = $className;
		}

		self::$saveNames[$className] = $saveNames;
	}

	/**
	 * Returns the short save name
	 * @return string
	 */
	public static function getSaveId() : string{
		if(!isset(self::$saveNames[static::class])){
			throw new \InvalidStateException("Tile is not registered");
		}

		reset(self::$saveNames[static::class]);
		return current(self::$saveNames[static::class]);
	}

	public function __construct(Level $level, Vector3 $pos){
		$this->timings = Timings::getTileEntityTimings($this);
		parent::__construct($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ(), $level);
	}

	/**
	 * Reads additional data from the CompoundTag on tile creation.
	 *
	 * @param CompoundTag $nbt
	 */
	abstract protected function readSaveData(CompoundTag $nbt) : void;

	/**
	 * Writes additional save data to a CompoundTag, not including generic things like ID and coordinates.
	 *
	 * @param CompoundTag $nbt
	 */
	abstract protected function writeSaveData(CompoundTag $nbt) : void;

	public function saveNBT() : CompoundTag{
		$nbt = new CompoundTag();
		$nbt->setString(self::TAG_ID, static::getSaveId());
		$nbt->setInt(self::TAG_X, $this->x);
		$nbt->setInt(self::TAG_Y, $this->y);
		$nbt->setInt(self::TAG_Z, $this->z);
		$this->writeSaveData($nbt);

		return $nbt;
	}

	public function getCleanedNBT() : ?CompoundTag{
		$this->writeSaveData($tag = new CompoundTag());
		return $tag->getCount() > 0 ? $tag : null;
	}

	/**
	 * @return Block
	 */
	public function getBlock() : Block{
		return $this->level->getBlockAt($this->x, $this->y, $this->z);
	}

	/**
	 * @return bool
	 */
	public function onUpdate() : bool{
		return false;
	}

	final public function scheduleUpdate() : void{
		if($this->closed){
			throw new \InvalidStateException("Cannot schedule update on garbage tile " . get_class($this));
		}
		$this->level->updateTiles[Level::blockHash($this->x, $this->y, $this->z)] = $this;
	}

	public function isClosed() : bool{
		return $this->closed;
	}

	public function __destruct(){
		$this->close();
	}

	public function close() : void{
		if(!$this->closed){
			$this->closed = true;

			if($this->isValid()){
				$this->level->removeTile($this);
				$this->setLevel(null);
			}
		}
	}

	public function getName() : string{
		return $this->name;
	}

}