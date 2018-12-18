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

    private $player, $webhook;
    private $curls;

    public function __construct($player, $webhook, $curls){
        $this->player = $player;
        $this->webhook = $webhook;
        $this->curls = $curls;
    }

    public function onRun() : void{
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->webhook);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(unserialize($this->curls)));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        $curl_error = curl_error($curl);

        $response_json = json_decode((string)$response, true);
        $success = false;
        $error = "An error occured while responses to the Discord.";

        if($curl_error != '') {
           $error = $curl_error;
        }elseif(curl_getinfo($curl, CURLINFO_HTTP_CODE) != 204) {
           $error = $response_json['message'];
        }elseif(curl_getinfo($curl, CURLINFO_HTTP_CODE) == 204 || $response === '') {
           $success = true;
        }

        $result = ['Response' => $response, 'Error' => $error, 'success' => $success];
        $this->setResult($result, true);
    }

    public function onCompletion(): void{
        Discord::$notify($this->player, $this->getResult());
    }
}
