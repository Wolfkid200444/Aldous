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
					$player->sendTip("update");
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
						if($k1 >= 0 && $l1 >= -1 && $k1 < 128 && $l1 < 128){
							$i2 = $k1 - $l;
							$j2 = $l1 - $i1;
							$flag1 = $i2 * $i2 + $j2 * $j2 > ($j1 - 2) * ($j1 - 2);
							$k2 = ($j / $i + $k1 - 64) * $i;
							$l2 = ($k / $i + $l1 - 64) * $i;
							$multiset = [];

							if($world->isChunkInUse($k2 >> 4, $l2 >> 4)){
								$k3 = 0;
								$d1 = 0.0;

								$h = $world->getHighestBlockAt((int) floor($k2), (int) floor($l2));

								if($h > 0){
									$block = $world->getBlock($tempVector->setComponents($k2, $h, $l2));
									$d1 += (int) $h / (int) ($i * $i);
									$color = self::getMapColorByBlock($block);
									$multiset[] = $color->toABGR();
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

								$mapcolor = end($multiset);

								/*if($mapcolor == 0){
									$d2 = (int) $k3 * 0.1 + (int) ($k1 + $l1 & 1) * 0.2;
									$i5 = 1;

									if($d2 < 0.5){
										$i5 = 2;
									}

									if($d2 > 0.9){
										$i5 = 0;
									}
								}*/

								$d0 = $d1;

								if($l1 >= 0 && $i2 * $i2 + $j2 * $j2 < $j1 * $j1 && (!$flag1 || ($k1 + $l1 & 1) != 0)){
									$b0 = $data->getColorAt($k1, $l1)->toABGR();
									$b1 = $mapcolor; // TODO: implement color index

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
				$data->setColorAt($x, $y, new Color(0,0,0,0));
			}
		}

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

	public static function getMapColorByBlock(Block $block){
		$meta = $block->getDamage();
		switch($id = $block->getId()){
			case Block::AIR:
				return new Color(0, 0, 0);
			case Block::GRASS:
			case Block::SLIME_BLOCK:
				return new Color(127, 178, 56);
			case Block::SAND:
			case Block::SANDSTONE:
			case Block::SANDSTONE_STAIRS:
				//case Block::STONE_SLAB && ($meta & 0x07) == StoneSlab::SANDSTONE:
				//case Block::DOUBLE_STONE_SLAB && $meta == StoneSlab::SANDSTONE:
			case Block::GLOWSTONE:
			case Block::END_STONE:
				//case Block::PLANKS && $meta == Planks::BIRCH:
				//case Block::LOG && $meta == Planks::BIRCH:
			case Block::BIRCH_FENCE_GATE:
				//case Block::FENCE && $meta = Planks::BIRCH:
			case Block::BIRCH_STAIRS:
				//case Block::WOODEN_SLAB && ($meta & 0x07) == Planks::BIRCH:
			case Block::BONE_BLOCK:
			case Block::END_BRICKS:
				return new Color(247, 233, 163);
			case Block::BED_BLOCK:
			case Block::COBWEB:
				return new Color(199, 199, 199);
			case Block::LAVA:
			case Block::STILL_LAVA:
			case Block::TNT:
			case Block::FIRE:
			case Block::REDSTONE_BLOCK:
				return new Color(255, 0, 0);
			case Block::ICE:
			case Block::PACKED_ICE:
			case Block::FROSTED_ICE:
				return new Color(160, 160, 255);
			case Block::IRON_BLOCK:
			case Block::IRON_DOOR_BLOCK:
			case Block::IRON_TRAPDOOR:
			case Block::IRON_BARS:
			case Block::BREWING_STAND_BLOCK:
			case Block::ANVIL:
			case Block::HEAVY_WEIGHTED_PRESSURE_PLATE:
				return new Color(167, 167, 167);
			case Block::SAPLING:
			case Block::LEAVES:
			case Block::LEAVES2:
			case Block::TALL_GRASS:
			case Block::DEAD_BUSH:
			case Block::RED_FLOWER:
			case Block::DOUBLE_PLANT:
			case Block::BROWN_MUSHROOM:
			case Block::RED_MUSHROOM:
			case Block::WHEAT_BLOCK:
			case Block::CARROT_BLOCK:
			case Block::POTATO_BLOCK:
			case Block::BEETROOT_BLOCK:
			case Block::CACTUS:
			case Block::SUGARCANE_BLOCK:
			case Block::PUMPKIN_STEM:
			case Block::MELON_STEM:
			case Block::VINE:
			case Block::LILY_PAD:
				return new Color(0, 124, 0);
			//case Block::WOOL && $meta == Color::COLOR_DYE_WHITE:
			//case Block::CARPET && $meta == Color::COLOR_DYE_WHITE:
			//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_WHITE:
			case Block::SNOW_LAYER:
			case Block::SNOW_BLOCK:
				return new Color(255, 255, 255);
			case Block::CLAY_BLOCK:
			case Block::MONSTER_EGG:
				return new Color(164, 168, 184);
			case Block::DIRT:
			case Block::FARMLAND:
				//case Block::STONE && $meta == Stone::GRANITE:
				//case Block::STONE && $meta == Stone::POLISHED_GRANITE:
				//case Block::SAND && $meta == 1:
			case Block::RED_SANDSTONE:
			case Block::RED_SANDSTONE_STAIRS:
				//case Block::STONE_SLAB2 && ($meta & 0x07) == StoneSlab::RED_SANDSTONE://slab2
				//case Block::LOG && $meta == Planks::JUNGLE:
				//case Block::PLANKS && $meta == Planks::JUNGLE:
			case Block::JUNGLE_FENCE_GATE:
				//case Block::FENCE && $meta == Planks::JUNGLE:
			case Block::JUNGLE_STAIRS:
				//case Block::WOODEN_SLAB && ($meta & 0x07) == Planks::JUNGLE:
				return new Color(151, 109, 77);
			case Block::STONE:
				//case Block::STONE_SLAB && ($meta & 0x07) == StoneSlab::STONE:
			case Block::COBBLESTONE:
			case Block::COBBLESTONE_STAIRS:
				//case Block::STONE_SLAB && ($meta & 0x07) == StoneSlab::COBBLESTONE:
			case Block::COBBLESTONE_WALL:
			case Block::MOSS_STONE:
				//case Block::STONE && $meta == Stone::ANDESITE:
				//case Block::STONE && $meta == Stone::POLISHED_ANDESITE:
			case Block::BEDROCK:
			case Block::GOLD_ORE:
			case Block::IRON_ORE:
			case Block::COAL_ORE:
			case Block::LAPIS_ORE:
			case Block::DISPENSER:
			case Block::DROPPER:
			case Block::STICKY_PISTON:
			case Block::PISTON:
			case Block::PISTON_ARM_COLLISION:
			case Block::MOVINGBLOCK:
			case Block::MONSTER_SPAWNER:
			case Block::DIAMOND_ORE:
			case Block::FURNACE:
			case Block::STONE_PRESSURE_PLATE:
			case Block::REDSTONE_ORE:
			case Block::STONE_BRICK:
			case Block::STONE_BRICK_STAIRS:
				//case Block::STONE_SLAB && ($meta & 0x07) == StoneSlab::STONE_BRICK:
			case Block::ENDER_CHEST:
			case Block::HOPPER_BLOCK:
			case Block::GRAVEL:
			case Block::OBSERVER:
				return new Color(112, 112, 112);
			case Block::WATER:
			case Block::STILL_WATER:
				return new Color(64, 64, 255);
			//case Block::WOOD && $meta == Planks::OAK:
			//case Block::PLANKS && $meta == Planks::OAK:
			//case Block::FENCE && $meta == Planks::OAK:
			case Block::OAK_FENCE_GATE:
			case Block::OAK_STAIRS:
				//case Block::WOODEN_SLAB && ($meta & 0x07) == Planks::OAK:
			case Block::NOTEBLOCK:
			case Block::BOOKSHELF:
			case Block::CHEST:
			case Block::TRAPPED_CHEST:
			case Block::CRAFTING_TABLE:
			case Block::WOODEN_DOOR_BLOCK:
			case Block::BIRCH_DOOR_BLOCK:
			case Block::SPRUCE_DOOR_BLOCK:
			case Block::JUNGLE_DOOR_BLOCK:
			case Block::ACACIA_DOOR_BLOCK:
			case Block::DARK_OAK_DOOR_BLOCK:
			case Block::SIGN_POST:
			case Block::WALL_SIGN:
			case Block::WOODEN_PRESSURE_PLATE:
			case Block::JUKEBOX:
			case Block::WOODEN_TRAPDOOR:
			case Block::BROWN_MUSHROOM_BLOCK:
			case Block::STANDING_BANNER:
			case Block::WALL_BANNER:
			case Block::DAYLIGHT_SENSOR:
			case Block::DAYLIGHT_SENSOR_INVERTED:
				return new Color(143, 119, 72);
			case Block::QUARTZ_BLOCK:
				//case Block::STONE_SLAB && ($meta & 0x07) == StoneSlab::QUARTZ:
			case Block::QUARTZ_STAIRS:
				//case Block::STONE && $meta == Stone::DIORITE:
				//case Block::STONE && $meta == Stone::POLISHED_DIORITE:
			case Block::SEA_LANTERN:
				return new Color(255, 252, 245);
			//case Block::WOOL && $meta == Color::COLOR_DYE_ORANGE:
			//case Block::CARPET && $meta == Color::COLOR_DYE_ORANGE:
			//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_ORANGE:
			case Block::PUMPKIN:
			case Block::JACK_O_LANTERN:
			case Block::HARDENED_CLAY:
				//case Block::WOOD && $meta == Planks::ACACIA:
				//case Block::PLANKS && $meta == Planks::ACACIA:
				//case Block::FENCE && $meta == Planks::ACACIA:
			case Block::ACACIA_FENCE_GATE:
			case Block::ACACIA_STAIRS:
				//case Block::WOODEN_SLAB && ($meta & 0x07) == Planks::ACACIA:
				return new Color(216, 127, 51);
			//case Block::WOOL && $meta == Color::COLOR_DYE_MAGENTA:
			//case Block::CARPET && $meta == Color::COLOR_DYE_MAGENTA:
			//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_MAGENTA:
			case Block::PURPUR_BLOCK:
			case Block::PURPUR_STAIRS:
				//case Block::STONE_SLAB2 && ($meta & 0x07) == Stone::PURPUR_BLOCK://slab2
				return new Color(178, 76, 216);
				//case Block::WOOL && $meta == Color::COLOR_DYE_LIGHT_BLUE:
				//case Block::CARPET && $meta == Color::COLOR_DYE_LIGHT_BLUE:
				//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_LIGHT_BLUE:
				//return new Color(102, 153, 216);
			//case Block::WOOL && $meta == Color::COLOR_DYE_YELLOW:
			//case Block::CARPET && $meta == Color::COLOR_DYE_YELLOW:
			//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_YELLOW:
			case Block::HAY_BALE:
			case Block::SPONGE:
				return new Color(229, 229, 51);
			//case Block::WOOL && $meta == Color::COLOR_DYE_LIME:
			//case Block::CARPET && $meta == Color::COLOR_DYE_LIME:
			//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_LIME:
			case Block::MELON_BLOCK:
				return new Color(229, 229, 51);
				//case Block::WOOL && $meta == Color::COLOR_DYE_PINK:
				//case Block::CARPET && $meta == Color::COLOR_DYE_PINK:
				//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_PINK:
				//return new Color(242, 127, 165);
			//case Block::WOOL && $meta == Color::COLOR_DYE_GRAY:
			//case Block::CARPET && $meta == Color::COLOR_DYE_GRAY:
			//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_GRAY:
			case Block::CAULDRON_BLOCK:
				return new Color(76, 76, 76);
			//case Block::WOOL && $meta == Color::COLOR_DYE_LIGHT_GRAY:
			//case Block::CARPET && $meta == Color::COLOR_DYE_LIGHT_GRAY:
			//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_LIGHT_GRAY:
			case Block::STRUCTURE_BLOCK:
				return new Color(153, 153, 153);
				//case Block::WOOL && $meta == Color::COLOR_DYE_CYAN:
				//case Block::CARPET && $meta == Color::COLOR_DYE_CYAN:
				//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_CYAN:
				//case Block::PRISMARINE && $meta == Prismarine::NORMAL:
				//return new Color(76, 127, 153);
			//case Block::WOOL && $meta == Color::COLOR_DYE_PURPLE:
			//case Block::CARPET && $meta == Color::COLOR_DYE_PURPLE:
			//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_PURPLE:
			case Block::MYCELIUM:
			case Block::REPEATING_COMMAND_BLOCK:
			case Block::CHORUS_PLANT:
			case Block::CHORUS_FLOWER:
				return new Color(127, 63, 178);
				//case Block::WOOL && $meta == Color::COLOR_DYE_BLUE:
				//case Block::CARPET && $meta == Color::COLOR_DYE_BLUE:
				//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_BLUE:
				//return new Color(51, 76, 178);
			//case Block::WOOL && $meta == Color::COLOR_DYE_BROWN:
			//case Block::CARPET && $meta == Color::COLOR_DYE_BROWN:
			//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_BROWN:
			case Block::SOUL_SAND:
				//case Block::WOOD && $meta == Planks::DARK_OAK:
				//case Block::PLANKS && $meta == Planks::DARK_OAK:
				//case Block::FENCE && $meta == Planks::DARK_OAK:
			case Block::DARK_OAK_FENCE_GATE:
			case Block::DARK_OAK_STAIRS:
				//case Block::WOODEN_SLAB && ($meta & 0x07) == Planks::DARK_OAK:
			case Block::COMMAND_BLOCK:
				return new Color(102, 76, 51);
			//case Block::WOOL && $meta == Color::COLOR_DYE_GREEN:
			//case Block::CARPET && $meta == Color::COLOR_DYE_GREEN:
			//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_GREEN:
			case Block::END_PORTAL_FRAME:
			case Block::CHAIN_COMMAND_BLOCK:
				return new Color(102, 127, 51);
			//case Block::WOOL && $meta == Color::COLOR_DYE_RED:
			//case Block::CARPET && $meta == Color::COLOR_DYE_RED:
			//case Block::STAINED_HARDENED_CLAY && $meta == Color::COLOR_DYE_RED:
			case Block::RED_MUSHROOM_BLOCK:
			case Block::BRICK_BLOCK:
				//case Block::STONE_SLAB && ($meta & 0x07) == StoneSlab::BRICK:
			case Block::BRICK_STAIRS:
			case Block::ENCHANTING_TABLE:
			case Block::NETHER_WART_BLOCK:
			case Block::NETHER_WART_PLANT:
				return new Color(153, 51, 51);
			//case Block::WOOL && $meta == 0:
			//case Block::CARPET && $meta == 0:
			//case Block::STAINED_HARDENED_CLAY && $meta == 0:
			case Block::DRAGON_EGG:
			case Block::COAL_BLOCK:
			case Block::OBSIDIAN:
			case Block::END_PORTAL:
				return new Color(25, 25, 25);
			case Block::GOLD_BLOCK:
			case Block::LIGHT_WEIGHTED_PRESSURE_PLATE:
				return new Color(250, 238, 77);
				break;
			case Block::DIAMOND_BLOCK:
				//case Block::PRISMARINE && $meta == Prismarine::DARK:
				//case Block::PRISMARINE && $meta == Prismarine::BRICKS:
			case Block::BEACON:
				return new Color(92, 219, 213);
			case Block::LAPIS_BLOCK:
				return new Color(74, 128, 255);
				break;
			case Block::EMERALD_BLOCK:
				return new Color(0, 217, 58);
			case Block::PODZOL:
				//case Block::WOOD && $meta == Planks::SPRUCE:
				//case Block::PLANKS && $meta == Planks::SPRUCE:
				//case Block::FENCE && $meta == Planks::SPRUCE:
			case Block::SPRUCE_FENCE_GATE:
			case Block::SPRUCE_STAIRS:
				//case Block::WOODEN_SLAB && ($meta & 0x07) == Planks::SPRUCE:
				return new Color(129, 86, 49);
			case Block::NETHERRACK:
			case Block::NETHER_QUARTZ_ORE:
			case Block::NETHER_BRICK_FENCE:
			case Block::NETHER_BRICK_BLOCK:
			case Block::MAGMA:
			case Block::NETHER_BRICK_STAIRS:
				//case Block::STONE_SLAB && ($meta & 0x07) == StoneSlab::NETHER_BRICK:
				return new Color(112, 2, 0);
			default:
				return new Color(0, 0, 0, 0);
		}
	}

	/*public static function colorizeMapColor(Color $color, int $value) : int{
		/*$b1 = (self::getMapColorByBlock($level->getBlock($vec->add(1, 0, 1))))->toABGR();
		$b2 = (self::getMapColorByBlock($level->getBlock($vec->add(-1, 0, -1))))->toABGR();

		$i = 0;
		$j = 0;
		$k = 0;

		foreach([$color->toABGR(), $b1, $b2] as $l){
			$i += ($l & 16711680) >> 16;
			$j += ($l & 65280) >> 8;
			$k += $l & 255;
		}

		return ($i / 9 & 255) << 16 | ($j / 9 & 255) << 8 | $k / 9 & 255;
		 $short1 = 220;

        if ($value == 3)
        {
	        $short1 = 135;
        }

        if ($value == 2)
        {
	        $short1 = 255;
        }

        if ($value == 1)
        {
	        $short1 = 220;
        }

        if ($value == 0)
        {
	        $short1 = 180;
        }
        $v = $color->toABGR();
         $i = ($v >> 16 & 255) * $short1 / 255;
         $j = ($v >> 8 & 255) * $short1 / 255;
         $k = ($v & 255) * $short1 / 255;
        return -16777216 | $i << 16 | $j << 8 | $k;
	}*/
}