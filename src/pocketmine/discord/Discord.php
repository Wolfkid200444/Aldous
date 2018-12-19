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
	
    private $webhook, $message;
    private $config = new Config(\pocketmine\RESOURCE_PATH . "discord.yml", Config::YAML);
    private $embed;
	
    public function __construct($webhook, $message, $embed){
        $this->webhook = new DiscordWebhook($this->config->getNested("discord.webhook"));
        $this->message = new DiscordMessage();
 
        if(Server::getInstance()->getAldousProperty("discord.active", true)){
           $this->message->setUsername($this->config->getNested("discord.username"));
           $this->message->setAvatarURL($this->config->getNested("discord.avatar"));

           $this->discordJoin();
           $this->discordQuit();
           $this->discordDeath();
           $this->discordChat();
           
           if(empty($this->config->getNested("discord.webhook")) && empty($this->config->getNested("discord.username"))){
               Server::getInstance()->logger->error("Please enter the Discord webhook and username correctly! Stopping the server...");
               Server::getInstance()->forceShutdown();
           }

           // Discord Embed: Server Startup.
           if(Server::getInstance()->setEnabled()){
              $this->embed = new DiscordEmbed();
              $this->embed->setTitle("Discord Log | Startup");
              $this->embed->setDescription($this->config->getNested("startup-embed.description"));
              $this->embed->setThumbnail($this->config->getNested("startup-embed.thumbnail"));
              $this->embed->setImage($this->config->getNested("startup-embed.image"));
              $this->embed->setColor($this->config->getNested("startup-embed.color"));
              $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
              $this->message->addEmbed($this->embed);
           }

           // Discord Embed: Server Shutdown.
           if(Server::getInstance()->shutdown()){
              $this->embed = new DiscordEmbed();
              $this->embed->setTitle("Discord Log | Shutdown");
              $this->embed->setDescription($this->config->getNested("shutdown-embed.description"));
              $this->embed->setThumbnail($this->config->getNested("shutdown-embed.thumbnail"));
              $this->embed->setImage($this->config->getNested("shutdown-embed.image"));
              $this->embed->setColor($this->config->getNested("shutdown-embed.color"));
              $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
              $this->message->addEmbed($this->embed);
           }
        }
     }
									 
     public function discordJoin(Player $player) : void{
         // Discord Embed: Player's Join.
         $this->embed = new DiscordEmbed();
         $this->embed->setTitle("Discord Log | Join");
         $this->embed->setDescription($this->config->getNested("join-embed.description", array(
                "%player" => Player::getPlayer()->getName()
            ))); 
         $this->embed->setThumbnail($this->config->getNested("join-embed.thumbnail"));
         $this->embed->setImage($this->config->getNested("join-embed.image"));
         $this->embed->setColor($this->config->getNested("join-embed.color"));
         $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
         $this->message->addEmbed($this->embed);
    }
								
    public function discordQuit(Player $player) : void{			  
        // Discord Embed: Player's Quit.
        $this->embed = new DiscordEmbed();
        $this->embed->setTitle("Discord Log | Quit");
        $this->embed->setDescription($this->config->getNested("quit-embed.description", array(
               "%player" => Player::getPlayer()->getName()
           ))); 
        $this->embed->setThumbnail($this->config->getNested("quit-embed.thumbnail"));
        $this->embed->setImage($this->config->getNested("quit-embed.image"));
        $this->embed->setColor($this->config->getNested("quit-embed.color"));
        $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
        $this->message->addEmbed($this->embed);
    }

    public function discordDeath(Player $player) : void{
        // Discord Embed: Player's Death.
        $this->embed = new DiscordEmbed();
        $this->embed->setTitle("Discord Log | Death");
        $this->embed->setDescription($this->config->getNested("death-embed.description", array(
               "%player" => Player::getPlayer()->getName()
           ))); 
        $this->embed->setThumbnail($this->config->getNested("death-embed.thumbnail"));
        $this->embed->setImage($this->config->getNested("death-embed.image"));
        $this->embed->setColor($this->config->getNested("death-embed.color"));
        $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
        $this->message->addEmbed($this->embed);
    }
		
    public function discordChat(Player $player) : void{
        // Discord Embed: Player's Chat.
        $this->embed = new DiscordEmbed();
        $this->embed->setDescription($this->config->getNested("chat-embed.description", array(
               "%player" => Player::getPlayer()->getName(),
               "%message" => Player::getPlayer()->getMessage()
           ))); 
        $this->embed->setThumbnail($this->config->getNested("chat-embed.thumbnail"));
        $this->embed->setImage($this->config->getNested("chat-embed.image"));
        $this->embed->setColor($this->config->getNested("chat-embed.color"));
        $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
        $this->message->addEmbed($this->embed);
    }
}
