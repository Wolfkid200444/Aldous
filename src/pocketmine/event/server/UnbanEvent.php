<?php
namespace pocketmine\event\server;
use pocketmine\event\Event;

/**
 * Event called when player gets unbanned
 * Class UnbanEvent
 * @package pocketmine\event\server
 */
class UnbanEvent extends Event{
    /**
     * Player that gots unbanned -> String
     * @var String
     */
    private $player;
    /**
     * Player unbanned the target player -> String / Player / ConsoleCommandSender
     * @var
     */
    private $unbanner;

    /**
     * UnbanEvent constructor.
     * @param String $player
     * @param $unbanner
     */
    public function __construct(String $player, $unbanner)
    {
        $this->unbanner = $unbanner;
        $this->player = $player;
    }

    /**
     * Returns players name // That got unbanned
     * @return String
     */
    public function getPlayer(): String{
        return $this->player;
    }
    public function getUnbanner(){
        return $this->unbanner;
    }
}