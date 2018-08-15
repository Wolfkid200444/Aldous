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
 * This program is free software): you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author TuranicTeam
 * @link https)://github.com/TuranicTeam/Altay
 *
 */

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Liquid;
use pocketmine\block\Planks;
use pocketmine\block\Prismarine;
use pocketmine\block\Stone;
use pocketmine\block\StoneSlab;
use pocketmine\level\Level;
use pocketmine\maps\MapData;
use pocketmine\maps\MapManager;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\utils\Color;

class FilledMap extends Item{

	public const TAG_MAP_UUID = "map_uuid";
	public const TAG_ZOOM = "zoom";

	public function __construct(int $meta = 0){
		parent::__construct(self::FILLED_MAP, $meta, "Filled Map");

		if($this->getNamedTag()->hasTag("map_uuid")){
			MapManager::loadMapData($this->getMapId());
		}
	}

	public function getMapData() : ?MapData{
		return MapManager::getMapDataById($this->getMapId());
	}

	public function onUpdate(Player $player) : void{
		if($data = $this->getMapData()){
			if($data->isDirty()){
				$pk = $data->getMapDataPacket($player);

				if($pk != null){
					$player->sendDataPacket($pk);
				}
			}

			$this->updateMapData($player, $data);
		}
	}

	public function updateMapData(Player $player, MapData $data) : void{
		if($player->level->getDimension() === $data->getDimension()){
			$i = 1 << $data->getScale();
			$center = $data->getCenter();
			$j = $center->x;
			$k = $center->y;
			$l = (int) floor($player->x - $j) / $i + 64;
			$i1 = (int) floor($player->z - $k) / $i + 64;
			$j1 = 128 / $i;

			$info = $data->getMapInfo($player);
			$info->mapIndex++;

			$flag = false;
			$world = $player->level;

			$tempVector = new Vector3();

			for($k1 = $l - $j1 + 1; $k1 < $l + $j1; ++$k1){
				if(($k1 & 15) == ($info->mapIndex & 15) || $flag){
					$flag = false;
					$d0 = 0.0;

					for($l1 = $i1 - $j1 - 1; $l1 < $i1 + $j1; ++$l1){
						if($k1 >= 0 and $l1 >= -1 and $k1 < 128 and $l1 < 128){
							$i2 = $k1 - $l;
							$j2 = $l1 - $i1;
							$flag1 = $i2 * $i2 + $j2 * $j2 > ($j1 - 2) * ($j1 - 2);
							$k2 = ($j / $i + $k1 - 64) * $i;
							$l2 = ($k / $i + $l1 - 64) * $i;

							if($world->isChunkInUse($k2 >> 4, $l2 >> 4)){
								$k3 = 0;
								$d1 = 0.0;

								$mapcolor = 0;

								$h = $world->getHighestBlockAt((int) floor($k2), (int) floor($l2));

								if($h > 0){
									$block = $world->getBlock($tempVector->setComponents($k2, $h, $l2));
									if($block instanceof Liquid){
										while($block->getSide(Vector3::SIDE_DOWN) instanceof Liquid and $h > 0){
											$block = $block->getSide(Vector3::SIDE_DOWN);
											$h--;
										}
									}
									$d1 += (int) $h / (int) ($i * $i);
									$color = self::getMapColorByBlock($block);
									$mapcolor = $color->toABGR();
								}

								$k3 = $k3 / ($i * $i);
								$d2 = ($d1 - $d0) * 4.0 / (int) ($i + 4) + ((int) ($k1 + $l1 & 1) - 0.5) * 0.4;
								$i5 = 1;

								if($d2 > 0.6){
									$i5 = 2;
								}

								if($d2 < -0.6){
									$i5 = 0;
								}

								if($mapcolor === 0){
									$d2 = (int) $k3 * 0.1 + (int) ($k1 + $l1 & 1) * 0.2;
									$i5 = 1;

									if($d2 < 0.5){
										$i5 = 2;
									}

									if($d2 > 0.9){
										$i5 = 0;
									}
								}

								$d0 = $d1;

								if($l1 >= 0 and $i2 * $i2 + $j2 * $j2 < $j1 * $j1 and (!$flag1 || ($k1 + $l1 & 1) != 0)){
									$b0 = $data->getColorAt($k1, $l1)->toABGR();
									$b1 = self::colorizeMapColor($mapcolor, $i5);

									if($b0 !== $b1){
										$data->setColorAt($k1, $l1, Color::fromABGR($b1));
										$data->updateInfo($k1, $l1);
										$flag = true;
									}
								}
							}
						}
					}
				}
			}
		}
	}

	public function onCreateMap(Player $player, int $scale) : void{
		$this->setMapId($id = MapManager::getNextId());
		$this->setZoom($scale);

		$data = new MapData($id);
		$data->setScale($scale);
		$data->setDimension($player->level->getDimension());
		$data->calculateMapCenter($player->getFloorX(), $player->getFloorZ(), $scale);

		$sc = (1 << $scale) * 128;
		for($x = 0; $x < $sc; $x++){
			for($y = 0; $y < $sc; $y++){
				$data->setColorAt($x, $y, new Color(0, 0, 0, 0));
			}
		}

		$this->setCustomName("map_" . strval($id));

		MapManager::registerMapData($data);
	}

	/**
	 * @return int
	 */
	public function getMaxStackSize() : int{
		return 1;
	}

	/**
	 * @param int $zoom
	 */
	public function setZoom(int $zoom) : void{
		if($zoom > 4){
			$zoom = 4;
		}
		$this->setNamedTagEntry(new ByteTag(self::TAG_ZOOM, $zoom));
	}

	/**
	 * @return int
	 */
	public function getZoom() : int{
		return $this->getNamedTag()->getByte(self::TAG_ZOOM, 0);
	}

	/**
	 * @param int $mapId
	 */
	public function setMapId(int $mapId) : void{
		$this->setNamedTagEntry(new LongTag(self::TAG_MAP_UUID, $mapId));
	}

	/**
	 * @return int
	 */
	public function getMapId() : int{
		return $this->getNamedTag()->getLong(self::TAG_MAP_UUID, 0, false);
	}


	/**
	 * TODO): Separate map colors to blocks
	 *
	 * @param Block $block
	 *
	 * @return Color
	 */
	public static function getMapColorByBlock(Block $block) : Color{
		$meta = $block->getDamage();
		$id = $block->getId();
		switch($id){
			case ($id === Block::AIR):
				return new Color(0, 0, 0);
			case ($id === Block::GRASS):
			case ($id === Block::SLIME_BLOCK):
				return new Color(127, 178, 56);
			case ($id === Block::SAND):
			case ($id === Block::SANDSTONE):
			case ($id === Block::SANDSTONE_STAIRS):
			case ($id === Block::STONE_SLAB and ($meta & 0x07) == StoneSlab::SANDSTONE):
			case ($id === Block::DOUBLE_STONE_SLAB and $meta == StoneSlab::SANDSTONE):
			case ($id === Block::GLOWSTONE):
			case ($id === Block::END_STONE):
			case ($id === Block::PLANKS and $meta == Planks::BIRCH):
			case ($id === Block::LOG and $meta == Planks::BIRCH):
			case ($id === Block::BIRCH_FENCE_GATE):
			case ($id === Block::FENCE and $meta = Planks::BIRCH):
			case ($id === Block::BIRCH_STAIRS):
			case ($id === Block::WOODEN_SLAB and ($meta & 0x07) == Planks::BIRCH):
			case ($id === Block::BONE_BLOCK):
			case ($id === Block::END_BRICKS):
				return new Color(247, 233, 163);
			case ($id === Block::BED_BLOCK):
			case ($id === Block::COBWEB):
				return new Color(199, 199, 199);
			case ($id === Block::LAVA):
			case ($id === Block::STILL_LAVA):
			case ($id === Block::FLOWING_LAVA):
			case ($id === Block::TNT):
			case ($id === Block::FIRE):
			case ($id === Block::REDSTONE_BLOCK):
				return new Color(255, 0, 0);
			case ($id === Block::ICE):
			case ($id === Block::PACKED_ICE):
			case ($id === Block::FROSTED_ICE):
				return new Color(160, 160, 255);
			case ($id === Block::IRON_BLOCK):
			case ($id === Block::IRON_DOOR_BLOCK):
			case ($id === Block::IRON_TRAPDOOR):
			case ($id === Block::IRON_BARS):
			case ($id === Block::BREWING_STAND_BLOCK):
			case ($id === Block::ANVIL):
			case ($id === Block::HEAVY_WEIGHTED_PRESSURE_PLATE):
				return new Color(167, 167, 167);
			case ($id === Block::SAPLING):
			case ($id === Block::LEAVES):
			case ($id === Block::LEAVES2):
			case ($id === Block::TALL_GRASS):
			case ($id === Block::DEAD_BUSH):
			case ($id === Block::RED_FLOWER):
			case ($id === Block::DOUBLE_PLANT):
			case ($id === Block::BROWN_MUSHROOM):
			case ($id === Block::RED_MUSHROOM):
			case ($id === Block::WHEAT_BLOCK):
			case ($id === Block::CARROT_BLOCK):
			case ($id === Block::POTATO_BLOCK):
			case ($id === Block::BEETROOT_BLOCK):
			case ($id === Block::CACTUS):
			case ($id === Block::SUGARCANE_BLOCK):
			case ($id === Block::PUMPKIN_STEM):
			case ($id === Block::MELON_STEM):
			case ($id === Block::VINE):
			case ($id === Block::LILY_PAD):
				return new Color(0, 124, 0);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_WHITE):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_WHITE):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_WHITE):
			case ($id === Block::SNOW_LAYER):
			case ($id === Block::SNOW_BLOCK):
				return new Color(255, 255, 255);
			case ($id === Block::CLAY_BLOCK):
			case ($id === Block::MONSTER_EGG):
				return new Color(164, 168, 184);
			case ($id === Block::DIRT):
			case ($id === Block::FARMLAND):
			case ($id === Block::STONE and $meta == Stone::GRANITE):
			case ($id === Block::STONE and $meta == Stone::POLISHED_GRANITE):
			case ($id === Block::SAND and $meta == 1):
			case ($id === Block::RED_SANDSTONE):
			case ($id === Block::RED_SANDSTONE_STAIRS):
			case ($id === Block::STONE_SLAB2 and ($meta & 0x07) == StoneSlab::RED_SANDSTONE):
			case ($id === Block::LOG and $meta == Planks::JUNGLE):
			case ($id === Block::PLANKS and $meta == Planks::JUNGLE):
			case ($id === Block::JUNGLE_FENCE_GATE):
			case ($id === Block::FENCE and $meta == Planks::JUNGLE):
			case ($id === Block::JUNGLE_STAIRS):
			case ($id === Block::WOODEN_SLAB and ($meta & 0x07) == Planks::JUNGLE):
				return new Color(151, 109, 77);
			case ($id === Block::STONE):
			case ($id === Block::STONE_SLAB and ($meta & 0x07) == StoneSlab::STONE):
			case ($id === Block::COBBLESTONE):
			case ($id === Block::COBBLESTONE_STAIRS):
			case ($id === Block::STONE_SLAB and ($meta & 0x07) == StoneSlab::COBBLESTONE):
			case ($id === Block::COBBLESTONE_WALL):
			case ($id === Block::MOSS_STONE):
			case ($id === Block::STONE and $meta == Stone::ANDESITE):
			case ($id === Block::STONE and $meta == Stone::POLISHED_ANDESITE):
			case ($id === Block::BEDROCK):
			case ($id === Block::GOLD_ORE):
			case ($id === Block::IRON_ORE):
			case ($id === Block::COAL_ORE):
			case ($id === Block::LAPIS_ORE):
			case ($id === Block::DISPENSER):
			case ($id === Block::DROPPER):
			case ($id === Block::STICKY_PISTON):
			case ($id === Block::PISTON):
			case ($id === Block::PISTON_ARM_COLLISION):
			case ($id === Block::MOVINGBLOCK):
			case ($id === Block::MONSTER_SPAWNER):
			case ($id === Block::DIAMOND_ORE):
			case ($id === Block::FURNACE):
			case ($id === Block::STONE_PRESSURE_PLATE):
			case ($id === Block::REDSTONE_ORE):
			case ($id === Block::STONE_BRICK):
			case ($id === Block::STONE_BRICK_STAIRS):
			case ($id === Block::STONE_SLAB and ($meta & 0x07) == StoneSlab::STONE_BRICK):
			case ($id === Block::ENDER_CHEST):
			case ($id === Block::HOPPER_BLOCK):
			case ($id === Block::GRAVEL):
			case ($id === Block::OBSERVER):
				return new Color(112, 112, 112);
			case ($id === Block::WATER):
			case ($id === Block::STILL_WATER):
			case ($id === Block::FLOWING_WATER):
				return new Color(64, 64, 255);
			case ($id === Block::WOOD and $meta == Planks::OAK):
			case ($id === Block::PLANKS and $meta == Planks::OAK):
			case ($id === Block::FENCE and $meta == Planks::OAK):
			case ($id === Block::OAK_FENCE_GATE):
			case ($id === Block::OAK_STAIRS):
			case ($id === Block::WOODEN_SLAB and ($meta & 0x07) == Planks::OAK):
			case ($id === Block::NOTEBLOCK):
			case ($id === Block::BOOKSHELF):
			case ($id === Block::CHEST):
			case ($id === Block::TRAPPED_CHEST):
			case ($id === Block::CRAFTING_TABLE):
			case ($id === Block::WOODEN_DOOR_BLOCK):
			case ($id === Block::BIRCH_DOOR_BLOCK):
			case ($id === Block::SPRUCE_DOOR_BLOCK):
			case ($id === Block::JUNGLE_DOOR_BLOCK):
			case ($id === Block::ACACIA_DOOR_BLOCK):
			case ($id === Block::DARK_OAK_DOOR_BLOCK):
			case ($id === Block::SIGN_POST):
			case ($id === Block::WALL_SIGN):
			case ($id === Block::WOODEN_PRESSURE_PLATE):
			case ($id === Block::JUKEBOX):
			case ($id === Block::WOODEN_TRAPDOOR):
			case ($id === Block::BROWN_MUSHROOM_BLOCK):
			case ($id === Block::STANDING_BANNER):
			case ($id === Block::WALL_BANNER):
			case ($id === Block::DAYLIGHT_SENSOR):
			case ($id === Block::DAYLIGHT_SENSOR_INVERTED):
				return new Color(143, 119, 72);
			case ($id === Block::QUARTZ_BLOCK):
			case ($id === Block::STONE_SLAB and ($meta & 0x07) == StoneSlab::QUARTZ):
			case ($id === Block::QUARTZ_STAIRS):
			case ($id === Block::STONE and $meta == Stone::DIORITE):
			case ($id === Block::STONE and $meta == Stone::POLISHED_DIORITE):
			case ($id === Block::SEA_LANTERN):
				return new Color(255, 252, 245);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_ORANGE):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_ORANGE):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_ORANGE):
			case ($id === Block::PUMPKIN):
			case ($id === Block::JACK_O_LANTERN):
			case ($id === Block::HARDENED_CLAY):
			case ($id === Block::WOOD and $meta == Planks::ACACIA):
			case ($id === Block::PLANKS and $meta == Planks::ACACIA):
			case ($id === Block::FENCE and $meta == Planks::ACACIA):
			case ($id === Block::ACACIA_FENCE_GATE):
			case ($id === Block::ACACIA_STAIRS):
			case ($id === Block::WOODEN_SLAB and ($meta & 0x07) == Planks::ACACIA):
				return new Color(216, 127, 51);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_MAGENTA):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_MAGENTA):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_MAGENTA):
			case ($id === Block::PURPUR_BLOCK):
			case ($id === Block::PURPUR_STAIRS):
			case ($id === Block::STONE_SLAB2 and ($meta & 0x07) == Stone::PURPUR_BLOCK):
				return new Color(178, 76, 216);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_LIGHT_BLUE):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_LIGHT_BLUE):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_LIGHT_BLUE):
				return new Color(102, 153, 216);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_YELLOW):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_YELLOW):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_YELLOW):
			case ($id === Block::HAY_BALE):
			case ($id === Block::SPONGE):
				return new Color(229, 229, 51);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_LIME):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_LIME):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_LIME):
			case ($id === Block::MELON_BLOCK):
				return new Color(229, 229, 51);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_PINK):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_PINK):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_PINK):
				return new Color(242, 127, 165);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_GRAY):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_GRAY):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_GRAY):
			case ($id === Block::CAULDRON_BLOCK):
				return new Color(76, 76, 76);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_LIGHT_GRAY):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_LIGHT_GRAY):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_LIGHT_GRAY):
			case ($id === Block::STRUCTURE_BLOCK):
				return new Color(153, 153, 153);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_CYAN):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_CYAN):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_CYAN):
			case ($id === Block::PRISMARINE and $meta == Prismarine::NORMAL):
				return new Color(76, 127, 153);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_PURPLE):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_PURPLE):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_PURPLE):
			case ($id === Block::MYCELIUM):
			case ($id === Block::REPEATING_COMMAND_BLOCK):
			case ($id === Block::CHORUS_PLANT):
			case ($id === Block::CHORUS_FLOWER):
				return new Color(127, 63, 178);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_BLUE):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_BLUE):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_BLUE):
				return new Color(51, 76, 178);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_BROWN):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_BROWN):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_BROWN):
			case ($id === Block::SOUL_SAND):
			case ($id === Block::WOOD and $meta == Planks::DARK_OAK):
			case ($id === Block::PLANKS and $meta == Planks::DARK_OAK):
			case ($id === Block::FENCE and $meta == Planks::DARK_OAK):
			case ($id === Block::DARK_OAK_FENCE_GATE):
			case ($id === Block::DARK_OAK_STAIRS):
			case ($id === Block::WOODEN_SLAB and ($meta & 0x07) == Planks::DARK_OAK):
			case ($id === Block::COMMAND_BLOCK):
				return new Color(102, 76, 51);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_GREEN):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_GREEN):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_GREEN):
			case ($id === Block::END_PORTAL_FRAME):
			case ($id === Block::CHAIN_COMMAND_BLOCK):
				return new Color(102, 127, 51);
			case ($id === Block::WOOL and $meta == Color::COLOR_DYE_RED):
			case ($id === Block::CARPET and $meta == Color::COLOR_DYE_RED):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == Color::COLOR_DYE_RED):
			case ($id === Block::RED_MUSHROOM_BLOCK):
			case ($id === Block::BRICK_BLOCK):
			case ($id === Block::STONE_SLAB and ($meta & 0x07) == StoneSlab::BRICK):
			case ($id === Block::BRICK_STAIRS):
			case ($id === Block::ENCHANTING_TABLE):
			case ($id === Block::NETHER_WART_BLOCK):
			case ($id === Block::NETHER_WART_PLANT):
				return new Color(153, 51, 51);
			case ($id === Block::WOOL and $meta == 0):
			case ($id === Block::CARPET and $meta == 0):
			case ($id === Block::STAINED_HARDENED_CLAY and $meta == 0):
			case ($id === Block::DRAGON_EGG):
			case ($id === Block::COAL_BLOCK):
			case ($id === Block::OBSIDIAN):
			case ($id === Block::END_PORTAL):
				return new Color(25, 25, 25);
			case ($id === Block::GOLD_BLOCK):
			case ($id === Block::LIGHT_WEIGHTED_PRESSURE_PLATE):
				return new Color(250, 238, 77);
			case ($id === Block::DIAMOND_BLOCK):
			case ($id === Block::PRISMARINE and $meta == Prismarine::DARK):
			case ($id === Block::PRISMARINE and $meta == Prismarine::BRICKS):
			case ($id === Block::BEACON):
				return new Color(92, 219, 213);
			case ($id === Block::LAPIS_BLOCK):
				return new Color(74, 128, 255);
			case ($id === Block::EMERALD_BLOCK):
				return new Color(0, 217, 58);
			case ($id === Block::PODZOL):
			case ($id === Block::WOOD and $meta == Planks::SPRUCE):
			case ($id === Block::PLANKS and $meta == Planks::SPRUCE):
			case ($id === Block::FENCE and $meta == Planks::SPRUCE):
			case ($id === Block::SPRUCE_FENCE_GATE):
			case ($id === Block::SPRUCE_STAIRS):
			case ($id === Block::WOODEN_SLAB and ($meta & 0x07) == Planks::SPRUCE):
				return new Color(129, 86, 49);
			case ($id === Block::NETHERRACK):
			case ($id === Block::NETHER_QUARTZ_ORE):
			case ($id === Block::NETHER_BRICK_FENCE):
			case ($id === Block::NETHER_BRICK_BLOCK):
			case ($id === Block::MAGMA):
			case ($id === Block::NETHER_BRICK_STAIRS):
			case ($id === Block::STONE_SLAB and ($meta & 0x07) == StoneSlab::NETHER_BRICK):
				return new Color(112, 2, 0);
			default:
				return new Color(0, 0, 0, 0);
		}
	}

	/**
	 * @param int $v Color hash
	 * @param int $value colorization value
	 *
	 * @return int
	 */
	public static function colorizeMapColor(int $v, int $value) : int{
		$short1 = 220;

		if($value == 3){
			$short1 = 135;
		}

		if($value == 2){
			$short1 = 255;
		}

		if($value == 1){
			$short1 = 220;
		}

		if($value == 0){
			$short1 = 180;
		}
		$i = ($v >> 16 & 255) * $short1 / 255;
		$j = ($v >> 8 & 255) * $short1 / 255;
		$k = ($v & 255) * $short1 / 255;
		return -16777216 | $i << 16 | $j << 8 | $k;
	}
}