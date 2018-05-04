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
use pocketmine\network\mcpe\IPlayerNetworkSession;
use pocketmine\network\SourceInterface;
use pocketmine\Player;

/**
 * Allows the creation of players overriding the base Player class
 */
class PlayerCreationEvent extends Event{
	/** @var IPlayerNetworkSession */
	private $networkSession;
	/** @var Player::class */
	private $baseClass;
	/** @var Player::class */
	private $playerClass;

	/**
	 * @param IPlayerNetworkSession $networkSession
	 * @param string                $baseClass Class that is an instanceof \pocketmine\Player
	 * @param string                $playerClass Class that is an instanceof $baseClass
	 */
	public function __construct(IPlayerNetworkSession $networkSession, $baseClass, $playerClass){
		$this->networkSession = $networkSession;

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
	 * @return IPlayerNetworkSession
	 */
	public function getNetworkSession() : IPlayerNetworkSession{
		return $this->networkSession;
	}

	/**
	 * @deprecated
	 * @return SourceInterface
	 */
	public function getInterface() : SourceInterface{
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
