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
use pocketmine\PlayerParameters;

/**
 * Called when the player logs in, before things have been set up
 */
class PlayerPreLoginEvent extends Event{
	public const ALLOWED = 0;
	public const REASON_SERVER_FULL = 1;
	public const REASON_BANNED = 2;
	public const REASON_WHITELIST = 3;
	public const REASON_PLUGIN = 4;

	/** @var PlayerNetworkSession */
	protected $networkSession;
	/** @var PlayerParameters */
	protected $loginData;

	/** @var string|null */
	protected $kickMessage;
	/** @var int */
	protected $reason = self::ALLOWED;

	/**
	 * @param PlayerNetworkSession $networkSession
	 * @param PlayerParameters     $loginData
	 */
	public function __construct(PlayerNetworkSession $networkSession, PlayerParameters $loginData){
		$this->networkSession = $networkSession;
	}

	public function getNetworkSession() : PlayerNetworkSession{
		return $this->networkSession;
	}

	/**
	 * Returns data associated with this login attempt, such as UUID, username, XUID, etc.
	 * @return PlayerParameters
	 */
	public function getLoginData() : PlayerParameters{
		return $this->loginData;
	}

	/**
	 * Null will display a generic kick message based on the reason flags set.
	 *
	 * @param string|null $kickMessage
	 */
	public function setKickMessage(?string $kickMessage) : void{
		$this->kickMessage = $kickMessage;
	}

	/**
	 * @return string|null
	 */
	public function getKickMessage() : ?string{
		return $this->kickMessage;
	}

	/**
	 * @return int
	 */
	public function getKickFlags() : int{
		return $this->reason;
	}

	/**
	 * @param int $flag
	 *
	 * @return bool
	 */
	public function hasKickFlag(int $flag) : bool{
		return ($this->reason & $flag) !== 0;
	}

	/**
	 * @param int  $flag
	 * @param bool $on
	 */
	public function setKickFlag(int $flag, bool $on = true) : void{
		if($on){
			$this->reason |= $flag;
		}else{
			$this->reason &= ~$flag;
		}
	}

	public function canJoin() : bool{
		return $this->reason === self::ALLOWED; //No kick flags set
	}
}
