<?php

 /*
 *              _     _                 
 *        /\   | |   | |                
 *       /  \  | | __| | ___  _   _ ___ 
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

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class DiscordAsyncTask extends AsyncTask {
 
	protected $webhook;
	protected $message;
	
    public function __construct(DiscordWebhook $webhook, Message $message){
        $this->webhook = $webhook;
        $this->message = $message;
    }
	
    public function onRun(): void{
        $ch = curl_init($this->webhook->getURL());
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->message));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$this->setResult(curl_exec($ch));
		curl_close($ch);
	}
	
	public function onCompletion(): void{
		$response = $this->getResult();
		if($response !== ""){
           Server::getInstance()->logger->error("[Aldous] An error occured while loading the Discord: " . $response);
		}
	}
}
