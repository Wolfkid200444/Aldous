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

namespace pocketmine;

use pocketmine\entity\Skin;
use pocketmine\utils\UUID;

class PlayerParameters{
	/** @var string */
	private $username;
	/** @var UUID */
	private $uuid;
	/** @var string */
	private $xuid;
	/** @var int */
	private $clientId;
	/** @var Skin */
	private $skin;
	/** @var string */
	private $locale;

	/**
	 * @return string
	 */
	public function getUsername() : string{
		return $this->username;
	}

	/**
	 * @param string $username
	 */
	public function setUsername(string $username) : void{
		$this->username = $username;
	}

	/**
	 * @return UUID
	 */
	public function getUuid() : UUID{
		return $this->uuid;
	}

	/**
	 * @param UUID $uuid
	 */
	public function setUuid(UUID $uuid) : void{
		$this->uuid = $uuid;
	}

	/**
	 * @return string
	 */
	public function getXuid() : string{
		return $this->xuid;
	}

	/**
	 * @param string $xuid
	 */
	public function setXuid(string $xuid) : void{
		$this->xuid = $xuid;
	}

	/**
	 * @return int
	 */
	public function getClientId() : int{
		return $this->clientId;
	}

	/**
	 * @param int $clientId
	 */
	public function setClientId(int $clientId) : void{
		$this->clientId = $clientId;
	}

	/**
	 * @return Skin
	 */
	public function getSkin() : Skin{
		return $this->skin;
	}

	/**
	 * @param Skin $skin
	 */
	public function setSkin(Skin $skin) : void{
		$this->skin = $skin;
	}

	/**
	 * @return string
	 */
	public function getLocale() : string{
		return $this->locale;
	}

	/**
	 * @param string $locale
	 */
	public function setLocale(string $locale) : void{
		$this->locale = $locale;
	}
}
