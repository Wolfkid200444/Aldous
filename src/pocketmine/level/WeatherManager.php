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

namespace pocketmine\level;

use pocketmine\network\mcpe\protocol\LevelEventPacket;

class WeatherManager{

	/**
	 * @var Level
	 */
	private $level;

	/** @var float */
	private $rainLevel = 0.0;
	/** @var float */
	private $lightningLevel = 0.0;
	/** @var int */
	private $weatherCycleTime = 0;

	/** @var int */
	private $tickRate = 1;

	public function __construct(Level $level){
		$this->level = $level;
	}

	public function tick(int $tickDiff = 1) : bool{
		if($this->weatherCycleTime > 0){
			$this->weatherCycleTime -= $tickDiff * $this->tickRate;
			return false;
		}

		$oldRainLevel = $this->rainLevel;
		$oldLightningLevel = $this->lightningLevel;

		//TODO: events
		if($this->rainLevel > 0){
			$this->weatherCycleTime = $this->generateClearWeatherTime();
			$this->rainLevel = 0.0;
			$this->lightningLevel = 0.0;
		}else{
			$this->weatherCycleTime = $this->generateRainyWeatherTime();
			$weatherStrengthBase = lcg_value();

			if(lcg_value() < 0.1){ //lightning 10% chance
				$this->rainLevel = 1.0;
				$this->lightningLevel = $weatherStrengthBase * 0.4 + 0.3;
			}else{
				$this->rainLevel = $weatherStrengthBase * 0.5 + 0.3;
				$this->lightningLevel = 0.0;
			}
		}

		if($oldRainLevel != $this->rainLevel){
			$this->level->broadcastLevelEvent(null, $this->rainLevel > 0.0 ? LevelEventPacket::EVENT_START_RAIN : LevelEventPacket::EVENT_STOP_RAIN, (int) ($this->rainLevel * 65535.0));
		}
		if($oldLightningLevel != $this->lightningLevel){
			$this->level->broadcastLevelEvent(null, $this->rainLevel > 0.0 ? LevelEventPacket::EVENT_START_THUNDER : LevelEventPacket::EVENT_STOP_THUNDER, (int) ($this->rainLevel * 65535.0));
		}

		return true;
	}

	/**
	 * Generates a random duration for clear weather to last for (in ticks).
	 * @return int
	 */
	protected function generateClearWeatherTime() : int{
		return mt_rand(0, 167999) + 12000;
	}

	/**
	 * Generates a random duration for rainy weather to last for (in ticks).
	 * @return int
	 */
	protected function generateRainyWeatherTime() : int{
		return mt_rand(0, 11999) + 12000;
	}

	/**
	 * @return float
	 */
	public function getRainLevel() : float{
		return $this->rainLevel;
	}

	public function isRaining() : bool{
		return $this->rainLevel > 0;
	}

	/**
	 * @param float $level
	 */
	public function setRainLevel(float $level) : void{
		if($level < 0.0 or $level > 1.0){
			throw new \InvalidArgumentException("Rain level must be in range 0.0 - 1.0");
		}

		$this->rainLevel = $level;
	}

	/**
	 * @return float
	 */
	public function getLightningLevel() : float{
		return $this->lightningLevel;
	}

	public function isLightning() : bool{
		return $this->lightningLevel > 0;
	}

	/**
	 * @param float $level
	 */
	public function setLightningLevel(float $level) : void{
		if($level < 0.0 or $level > 1.0){
			throw new \InvalidArgumentException("Lightning level must be in range 0.0 - 1.0");
		}

		$this->lightningLevel = $level;
	}

	/**
	 * Returns the time until the next weather change.
	 * @return int
	 */
	public function getWeatherCycleTime() : int{
		return $this->weatherCycleTime;
	}

	/**
	 * Sets the time until the next weather change.
	 * @param int $ticks
	 */
	public function setWeatherCycleTime(int $ticks) : void{
		$this->weatherCycleTime = $ticks;
	}

	public function getTickRate() : int{
		return $this->tickRate;
	}

	public function setTickRate(int $rate) : void{
		if($rate < 1){
			throw new \InvalidArgumentException("Tick rate must be at least 1");
		}

		$this->tickRate = $rate;
	}
}
