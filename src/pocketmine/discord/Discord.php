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

use pocketmine\Server;

class Discord {
	
    public $webhook, $message;
    private $embed;
	
    public function __construct($webhook, $message, $embed){
        $config = new Config(\pocketmine\RESOURCE_PATH .  "discord.yml", Config::YAML, []);

        $this->webhook = new DiscordWebhook($config->getNested("discord.webhook"));
        $this->message = new DiscordMessage();
 
        if(Server::getInstance()->getAldousProperty("discord.active", true)){
           $this->message->setUsername($config->getNested("discord.username"));
           $this->message->setAvatarURL($config->getNested("discord.avatar");

           // Discord Embed: Server Startup.
           if(Server::getInstance()->setEnabled()){
              $this->embed = new DiscordEmbed();
              $this->embed->setDescription($config->getNested("startup-embed.description"));
              $this->embed->setThumbnail("startup-embed.thumbnail");
              $this->embed->setImage($config->getNested("startup-embed.image");
              $this->embed->setColor($config->getNested("startup-embed.color"));
              $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
              $this->message->addEmbed($this->embed);
           }

           // Discord Embed: Server Shutdown.
           if(sleep(2) > !Server::getInstance()->setEnabled()){
              $this->embed = new DiscordEmbed();
              $this->embed->setDescription($config->getNested("shutdown-embed.description"));
              $this->embed->setThumbnail("shutdown-embed.thumbnail");
              $this->embed->setImage($config->getNested("shutdown-embed.image");
              $this->embed->setColor($config->getNested("shutdown-embed.color"));
              $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
              $this->message->addEmbed($this->embed);
           }

           if(sleep(2) < Server::getInstance()->setEnabled()){
              // Discord Embed: Player's Join.
              $this->embed = new DiscordEmbed();
              $this->embed->setTitle($config->getNested("join-embed.title");
              $this->embed->setDescription($config->getNested("join-embed.description", array(
                     "%player" => Player::getName()
                 ))); 
              $this->embed->setThumbnail("join-embed.thumbnail");
              $this->embed->setImage($config->getNested("join-embed.image");
              $this->embed->setColor($config->getNested("join-embed.color"));
              $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
              $this->message->addEmbed($this->embed);
								  
              // Discord Embed: Player's Quit.
              $this->embed = new DiscordEmbed();
              $this->embed->setTitle($config->getNested("quit-embed.title");
              $this->embed->setDescription($config->getNested("quit-embed.description", array(
                     "%player" => Player::getName()
                 ))); 
              $this->embed->setThumbnail("quit-embed.thumbnail");
              $this->embed->setImage($config->getNested("quit-embed.image");
              $this->embed->setColor($config->getNested("quit-embed.color"));
              $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
              $this->message->addEmbed($this->embed);
								  
              // Discord Embed: Player's Death.
              $this->embed = new DiscordEmbed();
              $this->embed->setTitle($config->getNested("death-embed.title");
              $this->embed->setDescription($config->getNested("death-embed.description", array(
                     "%player" => Player::getName()
                 ))); 
              $this->embed->setThumbnail("death-embed.thumbnail");
              $this->embed->setImage($config->getNested("death-embed.image");
              $this->embed->setColor($config->getNested("death-embed.color"));
              $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
              $this->message->addEmbed($this->embed);
		
              // Discord Embed: Player's Chat.
              $this->embed = new DiscordEmbed();
              $this->embed->setDescription($config->getNested("chat-embed.description", array(
                     "%player" => Player::getName(),
                     "%message" => Player::getMessage()
                 ))); 
              $this->embed->setThumbnail("chat-embed.thumbnail");
              $this->embed->setImage($config->getNested("chat-embed.image");
              $this->embed->setColor($config->getNested("chat-embed.color"));
              $this->embed->setFooter("Made with using Aldous", "https://cdn.discordapp.com/attachments/505849614121828367/522254943609159680/Aldous.png");
              $this->message->addEmbed($this->embed);
            }
        }
    }
}
