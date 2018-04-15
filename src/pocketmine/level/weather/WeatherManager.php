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

abstract class WeatherManager{

	/** @var float */
	protected $rainLevel = 0.0;
	/** @var float */
	protected $lightningLevel = 0.0;
	/** @var int */
	protected $rainCycleTime = 0;
	/** @var int */
	protected $lightningCycleTime = 0;

	/**
	 * Called every tick by the Level to update the weather.
	 *
	 * @param int $tickDiff
	 */
	abstract public function tick(int $tickDiff = 1) : void;

	/**
	 * Returns the current rain level as a float between 0.0 and 1.0.
	 *
	 * @return float
	 */
	public function getRainLevel() : float{
		return $this->rainLevel;
	}

	/**
	 * Returns whether the rain strength is currently greater than zero.
	 *
	 * @return bool
	 */
	public function isRaining() : bool{
		return $this->rainLevel > 0;
	}

	/**
	 * Sets the current rain level as a float between 0.0 and 1.0.
	 *
	 * @param float $level 0.0 - 1.0
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setRainLevel(float $level) : void{
		if($level < 0.0 or $level > 1.0){
			throw new \InvalidArgumentException("Rain level must be in range 0.0 - 1.0");
		}

		$this->rainLevel = $level;
	}

	/**
	 * Returns the current base lightning level as a float between 0.0 and 1.0. This does not represent the visible
	 * lightning level in the world.
	 *
	 * @return float
	 */
	public function getLightningLevel() : float{
		return $this->lightningLevel;
	}

	/**
	 * Returns the base lightning level multiplied by the rain level. This is used to produce a lightning level that
	 * makes sense for lightning strikes.
	 *
	 * @return float
	 */
	public function getRealLightningLevel() : float{
		return $this->rainLevel * $this->lightningLevel;
	}

	/**
	 * Returns whether the current real (not base) lightning level is greater than zero.
	 *
	 * @return bool
	 */
	public function isLightning() : bool{
		return $this->getRealLightningLevel() > 0;
	}

	/**
	 * Sets the current base lightning level as a float between 0.0 and 1.0.
	 *
	 * @param float $level 0.0 - 1.0
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setLightningLevel(float $level) : void{
		if($level < 0.0 or $level > 1.0){
			throw new \InvalidArgumentException("Lightning level must be in range 0.0 - 1.0");
		}

		$this->lightningLevel = $level;
	}

	/**
	 * Returns a the chance of a lightning bolt spawning under the current weather conditions.
	 * Larger return value = lower chance of lightning strike.
	 *
	 * @return int
	 */
	abstract public function getLightningStrikeChance() : int;


	/**
	 * Returns the time until the next rain level change.
	 *
	 * @return int
	 */
	public function getRainCycleTime() : int{
		return $this->rainCycleTime;
	}

	/**
	 * Sets the time until the next rain level change.
	 *
	 * @param int $ticks
	 */
	public function setRainCycleTime(int $ticks) : void{
		$this->rainCycleTime = $ticks;
	}

	/**
	 * Returns the time until the next base lightning level change.
	 *
	 * @return int
	 */
	public function getLightningCycleTime() : int{
		return $this->lightningCycleTime;
	}

	/**
	 * Sets the time until the next base lightning level change.
	 *
	 * @param int $ticks
	 */
	public function setLightningCycleTime(int $ticks) : void{
		$this->lightningCycleTime = $ticks;
	}
}
