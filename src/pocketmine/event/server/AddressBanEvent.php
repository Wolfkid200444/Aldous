<?php

namespace pocketmine\event\server;

use pocketmine\event\Event;

/**
 * Event called when player get's IP-Banned
 * Class AddressBanEvent
 * @package pocketmine\event\server
 */
class AddressBanEvent extends Event{
	/** @var string */
	private $reason;
	/** @var string */
	private $ip;

	/**
	 * AddressBanEvent constructor.
	 *
	 * @param String $ip
	 * @param String $reason
	 */
	public function __construct(string $ip, string $reason = "generic reason"){
		$this->ip = $ip;
		$this->reason = $reason;
	}

	/**
	 * Returns IP that got banned
	 * @return String
	 */
	public function getIp() : string{
		return $this->ip;
	}


	/**
	 * Returns the reason the Player was banned
	 * @return String
	 */
	public function getReason(){
		return $this->reason;
	}


}