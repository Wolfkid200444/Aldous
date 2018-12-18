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

class DiscordWebhook {

	protected $url;
  
	public function __construct(string $url){
      $this->url = $url;
	}
  
	public function getURL(): string{
      return $this->url;
	}
  
	public function send(DiscordMessage $message): void{
      Server::getInstance()->getAsyncPool()->submitTask(new DiscordAsyncTask($message));
	}
}
