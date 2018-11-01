<?php

namespace pocketmine\event\server;

use pocketmine\event\Event;
use pocketmine\Player;

/**
 * Event called when player gets banned
 * Class BanEvent
 * @package pocketmine\event\server
 */
class BanEvent extends Event{
	/**
	 * May be String / Player depends on player been online
	 * @var
	 */
	private $player;
	/**
	 * Reason of the ban
	 * @var String
	 */
	private $reason;

	/**
	 * BanEvent constructor.
	 *
	 * @param        $player
	 * @param String $reason
	 */
	public function __construct($player, string $reason = "generic reason"){
		$this->player = $player;
		$this->reason = $reason;
	}

	/**
	 * Returns the PlayerName / PlayerClass of player got banned
	 * @return null|Player
	 */
	public function getPlayer(){
		return $this->player;
	}

	/**
	 * Returns Ban Reason
	 * @return String
	 */
	public function getReason() : string{
		return $this->reason;
	}
}