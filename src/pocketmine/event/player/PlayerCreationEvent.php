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

namespace pocketmine\event\player;

use pocketmine\event\Event;
use pocketmine\network\mcpe\PlayerNetworkSession;
use pocketmine\network\NetworkInterface;
use pocketmine\Player;
use pocketmine\PlayerParameters;

/**
 * Allows the creation of players overriding the base Player class
 */
class PlayerCreationEvent extends Event{
	/** @var PlayerNetworkSession */
	private $networkSession;
	/** @var PlayerParameters */
	private $parameters;
	/** @var Player::class */
	private $baseClass;
	/** @var Player::class */
	private $playerClass;


	/**
	 * @param PlayerNetworkSession $networkSession
	 * @param PlayerParameters     $parameters
	 * @param string               $baseClass Class that is an instanceof \pocketmine\Player
	 * @param string               $playerClass Class that is an instanceof $baseClass
	 */
	public function __construct(PlayerNetworkSession $networkSession, PlayerParameters $parameters, $baseClass, $playerClass){
		$this->networkSession = $networkSession;
		$this->parameters = $parameters;

		if(!is_a($baseClass, Player::class, true)){
			throw new \RuntimeException("Base class $baseClass must extend " . Player::class);
		}

		$this->baseClass = $baseClass;

		if(!is_a($playerClass, $baseClass, true)){
			throw new \RuntimeException("Class $playerClass must extend " . $baseClass);
		}

		$this->playerClass = $playerClass;

	}

	/**
	 * @return PlayerNetworkSession
	 */
	public function getNetworkSession() : PlayerNetworkSession{
		return $this->networkSession;
	}

	/**
	 * Returns a PlayerParameters object containing information used to construct the Player.
	 * @return PlayerParameters
	 */
	public function getParameters() : PlayerParameters{
		return $this->parameters;
	}

	/**
	 * @deprecated
	 * @return NetworkInterface
	 */
	public function getInterface() : NetworkInterface{
		return $this->networkSession->getInterface();
	}

	/**
	 * @deprecated
	 * @return string
	 */
	public function getAddress() : string{
		return $this->networkSession->getIp();
	}

	/**
	 * @deprecated
	 * @return int
	 */
	public function getPort() : int{
		return $this->networkSession->getPort();
	}

	/**
	 * @return Player::class
	 */
	public function getBaseClass(){
		return $this->baseClass;
	}

	/**
	 * @param Player::class $class
	 */
	public function setBaseClass($class){
		if(!is_a($class, $this->baseClass, true)){
			throw new \RuntimeException("Base class $class must extend " . $this->baseClass);
		}

		$this->baseClass = $class;
	}

	/**
	 * @return Player::class
	 */
	public function getPlayerClass(){
		return $this->playerClass;
	}

	/**
	 * @param Player::class $class
	 */
	public function setPlayerClass($class){
		if(!is_a($class, $this->baseClass, true)){
			throw new \RuntimeException("Class $class must extend " . $this->baseClass);
		}

		$this->playerClass = $class;
	}
}
