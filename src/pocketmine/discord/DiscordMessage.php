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

class DiscordMessage implements \JsonSerializable {

    protected $data = [];
    
    public function setContent(string $content) : void{
        $this->data["content"] = $content;
    }
    
    public function getContent() : ?string{
        return $this->data["content"];
    }
    
    public function getUsername() : ?string{
        return $this->data["username"];
    }
    
    public function setUsername(string $username) : void{
        $this->data["username"] = $username;
    }
    
    public function getAvatarURL() : ?string{
        return $this->data["avatar_url"];
    }
    
    public function setAvatarURL(string $avatarURL) : void{
        $this->data["avatar_url"] = $avatarURL;
    }
    
    public function addEmbed(DiscordEmbed $embed) : void{
        if(!empty(($array = $embed->asArray()))){
           $this->data["embeds"][] = $arr;
        }
    }
    
    public function jsonSerialize(){
        return $this->data;
    }
}
