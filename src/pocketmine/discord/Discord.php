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

use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\Server;

class Discord {
	
    private $webhook;
    private $message;
    private $embed;
	
    private $config = true;
	
    public function __construct(string $webhook){
        $this->webhook = $webhook;
        $this->message = new DiscordMessage();
        $this->embed = new DiscordEmbed();
		
        if(!file_exists("discord.yml")){
           copy(\pocketmine\RESOURCE_PATH . "discord.yml", "discord.yml");
        }
        $this->config = new Config("discord.yml", Config::YAML, []);

        $webhook = new DiscordWebhook($this->config->getNested("discord.webhook"));

        if(Server::getInstance()->getAldousProperty("discord.active", true)){
           $this->message->setUsername($this->config->getNested("discord.username"));
           $this->message->setAvatarURL($this->config->getNested("discord.avatar"));

           if(!empty($this->config->getNested("discord.webhook"))){
              self::discordStart();
              self::discordStop();
              self::discordJoin();
              self::discordQuit();
              self::discordDeath();
              self::discordChat();
           }else{
              Server::getInstance()->getLogger()->error("Discord webhook hasn't been set, automatically shutdown in 5 seconds...");
              sleep(5);
              Server::getInstance()->forceShutdown(true);
           }
           if(empty($this->config->getNested("discord.username"))){
              Server::getInstance()->getLogger()->error("Discord username hasn't been set, as webhook is already set or empty. Automatically shutdown in 5 seconds...");
              sleep(5);
              Server::getInstance()->forceShutdown(true);
           }
        }
     }
	
     public static function discordStart(Server $server) : void{
         if(Server::getInstance()->isRunning
			(true)){
            $this->embed->setTitle("Discord Log | Startup");
            $this->embed->setDescription($this->config->getNested("startup-embed.description"));
            $this->embed->setThumbnail($this->config->getNested("startup-embed.thumbnail"));
            $this->embed->setImage($this->config->getNested("startup-embed.image"));
            $this->embed->setColor($this->config->getNested("startup-embed.color"));
            $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
            $this->message->addEmbed($this->embed);
            $webhook->send($this->message);
         }
     }
	
     public static function discordStop(Server $server) : void{
         if(Server::getInstance()->isRunning(false)){
            $this->embed->setTitle("Discord Log | Shutdown");
            $this->embed->setDescription($this->config->getNested("shutdown-embed.description"));
            $this->embed->setThumbnail($this->config->getNested("shutdown-embed.thumbnail"));
            $this->embed->setImage($this->config->getNested("shutdown-embed.image"));
            $this->embed->setColor($this->config->getNested("shutdown-embed.color"));
            $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
            $this->message->addEmbed($this->embed);
            $webhook->send($this->message);
         }
     }
									 
     public static function discordJoin(Player $player) : void{
         // Discord Embed: Player's Join. (PlayerJoinEvent)
         $this->embed->setTitle("Discord Log | Join");
         $this->embed->setDescription($this->config->getNested("join-embed.description", array(
                "%player" => Player::getPlayer()->getName()
            ))); 
         $this->embed->setThumbnail($this->config->getNested("join-embed.thumbnail"));
         $this->embed->setImage($this->config->getNested("join-embed.image"));
         $this->embed->setColor($this->config->getNested("join-embed.color"));
         $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
         $this->message->addEmbed($this->embed);
         $webhook->send($this->message);
    }
								
    public static function discordQuit(Player $player) : void{			  
        // Discord Embed: Player's Quit. (PlayerQuitEvent)
        $this->embed->setTitle("Discord Log | Quit");
        $this->embed->setDescription($this->config->getNested("quit-embed.description", array(
               "%player" => Player::getPlayer()->getName()
           ))); 
        $this->embed->setThumbnail($this->config->getNested("quit-embed.thumbnail"));
        $this->embed->setImage($this->config->getNested("quit-embed.image"));
        $this->embed->setColor($this->config->getNested("quit-embed.color"));
        $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
        $this->message->addEmbed($this->embed);
        $webhook->send($this->message);
    }

    public static function discordDeath(Player $player) : void{
        // Discord Embed: Player's Death. (PlayerDeathEvent)
        $this->embed->setTitle("Discord Log | Death");
        $this->embed->setDescription($this->config->getNested("death-embed.description", array(
               "%player" => Player::getPlayer()->getName()
           ))); 
        $this->embed->setThumbnail($this->config->getNested("death-embed.thumbnail"));
        $this->embed->setImage($this->config->getNested("death-embed.image"));
        $this->embed->setColor($this->config->getNested("death-embed.color"));
        $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
        $this->message->addEmbed($this->embed);
        $webhook->send($this->message);
    }
		
    public static function discordChat(Player $player) : void{
        // Discord Embed: Player's Chat. (PlayerChatEvent)
        $this->embed->setDescription($this->config->getNested("chat-embed.description", array(
               "%player" => Player::getPlayer()->getName(),
               "%message" => Player::getPlayer()->getMessage()
           ))); 
        $this->embed->setThumbnail($this->config->getNested("chat-embed.thumbnail"));
        $this->embed->setImage($this->config->getNested("chat-embed.image"));
        $this->embed->setColor($this->config->getNested("chat-embed.color"));
        $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
        $this->message->addEmbed($this->embed);
        $webhook->send($this->message);
    }
}
