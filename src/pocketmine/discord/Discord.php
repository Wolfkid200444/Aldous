<?php

 /*
 *             _     _                 
 *       /\   | |   | |                
 *      /  \  | | __| | ___  _   _ ___ 
 *     / /\ \ | |/ _` |/ _ \| | | / __|
 *    / ____ \| | (_| | (_) | |_| \__ \
 *   /_/    \_\_|\__,_|\___/ \__,_|___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Implasher
 * @link https://github.com/Implasher/Aldous
 *
 */

declare(strict_types=1);
 
namespace pocketmine\discord;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\Server;

class Discord {
	
    public static $webhook, $username;
    public static $start, $stop; 
    public static $joined, $quit, $death;
    public static $chat;
	
    public static function setVars(){
        $discordConfig = new Config(\pocketmine\RESOURCE_PATH .  "discord.yml", Config::YAML);
        foreach($discordConfig->getAll() as $item){
            if(Server::getInstance()->getAldousProperty("discord.active", true) && !isset($item) || $item === ""){
              \GlobalLogger::get()->error("Something is wrong in Discord configuration. Please make sure it's correct rights!");
              Server::getInstance()->setEnabled(false);
              return;
            }
        }

        if($this->getAldousProperty("discord.active", true)){
           static::$webhook = $discordConfig->get('discord.webhook');
           static::$username = $discordConfig->get('discord.username');
           static::$start = $discordConfig->get('load.start');
           static::$stop = $discordConfig->get('load.shutdown');
           static::$joined = $discordConfig->get('message.join');
           static::$quit = $discordConfig->get('message.quit');
           static::$death = $discordConfig->get('message.death');
           static::$chat = $discordConfig->get('community.chat');

           if($discordConfig->get('discord.webhook') === "0"){
              static::$username = static::$username;
           }elseif($discordConfig->get('discord.username') !== "0"){
              static::$username = $discordConfig->get('discord.username');
           }elseif($discordConfig->get('discord.webhook') === "0"){
              static::$webhook = static::$webhook;
           }elseif($discordConfig->get('discord.webhook') !== "0") {
              static::$chat = $discordConfig->get('discord.webhook');
           }
        }
    }
   
    public static function notify($player, $result){
        if($player === "LOG"){
           return;
        }elseif($player === "CONSOLE"){
           $player = new ConsoleCommandSender();
        }else{
           $exact = Server::getPlayerExact($player);
           if($exact === null) {
              return;
           }else{
              $player = $exact;
           }
        }

        if($result['success']){
           $this->sendMessage("[Aldous] Successfully sent message to Discord.");
        }else{
           $this->sendMessage("[Aldous] Failed to send message to Discord.");
        }
    }

    public static function sendMessage($webhook, $message, string $player = 'LOG', $username = null){
        if(!isset($username)){
           $username = static::$username;
        }
		
        $curl_opts = [
			"content" => $message, 
			"username" => $username
        ];
        $curls = serialize($curl_opts);
        Server::getInstance()->getAsyncPool()->submitTask(new DiscordAsyncTask($player, $webhook, $curls));
    }
}
