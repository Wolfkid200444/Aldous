<?php

namespace pocketmine\event\server;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\Event;
use pocketmine\Player;

/**
 * Event called when player gets unbanned
 * Class UnbanEvent
 * @package pocketmine\event\server
 */
class UnbanEvent extends Event{
	/** @var string */
	private $player;
	/** @var ConsoleCommandSender|Player */
	private $unbanner;

	/**
	 * UnbanEvent constructor.
	 *
	 * @param String $player
	 * @param        $unbanner
	 */
	public function __construct(string $player, $unbanner){
		$this->unbanner = $unbanner;
		$this->player = $player;
	}

	/**
	 * Returns players name // That got unbanned
	 * @return string
	 */
	public function getPlayer() : string{
		return $this->player;
	}

	public function getUnbanner(){
		return $this->unbanner;
	}
}