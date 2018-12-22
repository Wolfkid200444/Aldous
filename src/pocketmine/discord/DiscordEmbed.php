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

class DiscordEmbed {

    protected $data = [];
    
    public function asArray() : array{
        return $this->data;
    }
    
    public function setAuthor(string $name, string $url = null, string $iconURL = null) : void{
        if(!isset($this->data["author"])){
           $this->data["author"] = [];
        }
        $this->data["author"]["name"] = $name;
        if($url !== null){
           $this->data["author"]["url"] = $url;
        }
        if($iconURL !== null){
           $this->data["author"]["icon_url"] = $iconURL;
        }
    }
    
    public function setTitle(string $title) : void{
        $this->data["title"] = $title;
    }
    
    public function setDescription(string $description) : void{
        $this->data["description"] = $description;
    }
    
    public function setColor(int $color):void{
        $this->data["color"] = $color;
    }
    
    public function addField(string $name, string $value, bool $inline = false) : void{
        if(!isset($this->data["fields"])){
           $this->data["fields"] = [];
        }
        $this->data["fields"][] = [
            "name" => $name,
            "value" => $value,
            "inline" => $inline,
        ];
    }
    
    public function setThumbnail(string $url) : void{
        if(!isset($this->data["thumbnail"])){
           $this->data["thumbnail"] = [];
        }
        $this->data["thumbnail"]["url"] = $url;
    }
     
    public function setImage(string $url) : void{
        if(!isset($this->data["image"])){
           $this->data["image"] = [];
        }
        $this->data["image"]["url"] = $url;
    }
      
    public function setFooter(string $text, string $iconURL = null) : void{
        if(!isset($this->data["footer"])){
           $this->data["footer"] = [];
        }
        $this->data["footer"]["text"] = $text;
        if($iconURL !== null){
           $this->data["footer"]["icon_url"] = $iconURL;
        }
    }
}
