<?php

namespace pocketmine\event\server;

use pocketmine\event\Event;

/**
 * Event called when player get's IP-Banned
 * Class AddressBanEvent
 * @package pocketmine\event\server
 */
class AddressBanEvent extends Event{
	/**
	 * Reason why IP got banned / "None" if no reason given
	 * @var String
	 */
	private $reason;
	/**
	 * IP-Address that got banned
	 * @var String
	 */
	private $ip;

	/**
	 * AddressBanEvent constructor.
	 *
	 * @param String $ip
	 * @param String $reason
	 */
	public function __construct(String $ip, String $reason = "None"){
		$this->ip = $ip;
		if($reason != ""){
			$this->reason = $reason;
		}else{
			$this->reason = "None";
		}


	}

	/**
	 * Returns IP that got banned
	 * @return String
	 */
	public function getIp() : String{
		return $this->ip;
	}


	/**
	 * Returns the reason the Player was banned for / "None" If no reason was given
	 * @return String
	 */
	public function getReason(){
		return $this->reason;
	}


}