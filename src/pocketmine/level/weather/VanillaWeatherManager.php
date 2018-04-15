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

namespace pocketmine\level\weather;

class VanillaWeatherManager extends WeatherManager{

	/** @var int */
	private $tickRate = 1;

	public function tick(int $tickDiff = 1) : bool{
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
	 * Returns a the chance of a lightning bolt spawning under the current weather conditions.
	 * Larger return value = lower chance of lightning strike.
	 *
	 * @return int
	 */
	public function getLightningStrikeChance() : int{
		$lightningLevel = $this->getRealLightningLevel();
		return (int) (((1 - $lightningLevel) * 100000 + $lightningLevel * 3000) / $this->tickRate);
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
