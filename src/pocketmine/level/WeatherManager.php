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
	private $rainCycleTime = 0;
	/** @var int */
	private $lightningCycleTime = 0;

	/** @var int */
	private $tickRate = 1;

	public function __construct(Level $level){
		$this->level = $level;
	}

	public function tick(int $tickDiff = 1) : bool{
		$oldRainLevel = $this->rainLevel;
		$oldLightningLevel = $this->getRealLightningLevel();

		if($this->rainCycleTime > 0){
			$this->rainCycleTime -= $tickDiff * $this->tickRate;
		}else{
			if($this->rainLevel > 0){
				$this->rainCycleTime = $this->generateClearWeatherTime();
				$this->rainLevel = 0.0;
			}else{
				$this->rainCycleTime = $this->generateRainTime();
				$this->rainLevel = mt_rand() / mt_getrandmax();
			}
		}

		if($this->lightningCycleTime > 0){
			$this->lightningCycleTime -= $tickDiff * $this->tickRate;
		}else{
			if($this->lightningLevel > 0){
				$this->lightningCycleTime = $this->generateClearWeatherTime();
				$this->lightningLevel = 0.0;
			}else{
				$this->lightningCycleTime = $this->generateLightningTime();
				$this->lightningLevel = mt_rand() / mt_getrandmax();
			}
		}

		//TODO: events


		if($oldRainLevel != $this->rainLevel){
			$this->level->broadcastLevelEvent(null, $this->rainLevel > 0.0 ? LevelEventPacket::EVENT_START_RAIN : LevelEventPacket::EVENT_STOP_RAIN, (int) ($this->rainLevel * 65535.0));
		}

		$newLightningLevel = $this->getRealLightningLevel();
		if($oldLightningLevel != $newLightningLevel){
			$this->level->broadcastLevelEvent(null, $newLightningLevel > 0.0 ? LevelEventPacket::EVENT_START_THUNDER : LevelEventPacket::EVENT_STOP_THUNDER, (int) ($newLightningLevel * 65535.0));
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
	protected function generateRainTime() : int{
		return mt_rand(0, 11999) + 12000;
	}

	/**
	 * @return int
	 */
	protected function generateLightningTime() : int{
		return mt_rand(0, 11999) + 3600;
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

	public function getRealLightningLevel() : float{
		return $this->rainLevel * $this->lightningLevel;
	}

	public function isLightning() : bool{
		return $this->getRealLightningLevel() > 0;
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
	public function getRainCycleTime() : int{
		return $this->rainCycleTime;
	}

	/**
	 * Sets the time until the next weather change.
	 * @param int $ticks
	 */
	public function setRainCycleTime(int $ticks) : void{
		$this->rainCycleTime = $ticks;
	}

	public function getLightningCycleTime() : int{
		return $this->lightningCycleTime;
	}

	public function setLightningCycleTime(int $ticks) : void{
		$this->lightningCycleTime = $ticks;
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
