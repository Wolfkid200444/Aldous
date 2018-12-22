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

/**
 * Installer used for the first run.
 * Can be disabled with --no-installer
 * Restart the server again will skip the installer.
 */
namespace pocketmine;

use pocketmine\lang\Language;
use pocketmine\lang\LanguageNotFoundException;
use pocketmine\utils\Config;
use pocketmine\utils\Internet;

class Installer {

	public const DEFAULT_NAME = "Hello, " . \pocketmine\NAME . "!";
	public const DEFAULT_PORT = 19132;
	public const DEFAULT_PLAYERS = 50;
	public const DEFAULT_GAMEMODE = 0;

	private $language;
	
    public function run() : bool{
        $this->message("You're now in Aldous' Installer as this is first run.");
        $this->message("The installer is on English version!");
           
        $this->message("Automatically selected English as the base language!");
        $config = new Config(\pocketmine\DATA . "aldous.properties", Config::PROPERTIES);
		$config->set("language", "eng");
		$config->save();
		
		if(strtolower($this->getInput("Do you want to skip the installer?", "n", "y/N")) === "y"){
			$this->message("The installer has been skipped.");
			return true;
		}
		
		$this->writeLine();
		$this->welcomeAldous();
		$this->generateBaseConfig();
		$this->generateUserFiles();
		$this->networkFunctions();
		$this->endInstaller();
		
		if(!$this->showLicense()){
			return false;
		}
		
		return true;
    }
    
    private function showLicense() : bool{
    	$this->message("Hello user, and welcome to Aldous!");
        sleep(2);
        $this->message("This program is free software: you can redistribute it and/or modify");
        $this->message("it under the terms of the GNU Lesser General Public License as published by");
        $this->message("the Free Software Foundation, either version 3 of the License, or");
        $this->message("(at your option) any later version.");
        
        if(strtolower($this->getInput("Do you have read and accept the License?", "n", "y/N")) !== "y"){
			$this->error("You have accepted the License. Move to next step for a seconds...");
			sleep(5);
			return false;
		}
		return true;
    }
    
    private function welcomeAldous(){
		$this->message("You will start to setup your own server now!");
		$this->message("If you don't want to change the default value, just press [Enter] to skip this!");
		$this->message("You can edit later on aldous.properties file.");
		
		sleep(5);
        $config = new Config(\pocketmine\DATA . "aldous.properties", Config::PROPERTIES);
		$this->message("Getting your external IP and internal IP...");
        $externalIP = Internet::getIP();
        if($externalIP === false){
           $externalIP = "Unknown IP detected as offline!";
        }
        $internalIP = gethostbyname(trim(`hostname`));
		$this->error("Your external IP is " . $externalIP);
		$this->error("Your internal IP is " . $internalIP);
		$this->error("Be sure to check it, if you have to forward and you skip that, no external players will be able to join. Press [Enter] to continue.");
        $this->readLine();
        $config->set("ip", $externalIP);
        $config->save();
	}
	
	private function generateBaseConfig(){
		$config = new Config(\pocketmine\DATA . "aldous.properties", Config::PROPERTIES);
		$config->set("motd", $name = $this->getInput("Give a name to your server!"), self::DEFAULT_NAME);
		$config->set("name", $name);

		$this->message("Do not change the default port value if this is your first server.");

		do{
			$port = (int) $this->getInput("Please enter the server port!", (string) self::DEFAULT_PORT);
			if($port <= 0 or $port > 65535){
				$this->error("Server port is invalid! Please enter with the numbers!");
				continue;
			}
			break;
		}while(true);
		$config->set("port", $port);

		$this->message("Choose the gamemode for your server:");
		$this->message("Survival - [0]");
		$this->message("Creative - [1]");
		$this->message("Adventure - [2]");
		$this->message("Spectator - [3]");
		$this->message("Enter with the number to choose the gamemode!");

		do{
			$gamemode = (int) $this->getInput("Like what I mean above the message, choose/set the default gamemode!", (string) self::DEFAULT_GAMEMODE);
		}while($gamemode < 0 or $gamemode > 3);
		$config->set("gamemode", $gamemode);

		$config->set("maximum-players", (int) $this->getInput("Set the maximum number of players!", (string) self::DEFAULT_PLAYERS));

		$this->message("The spawn protection disallows placing/breaking blocks in the spawn zone except for OPs!");

		if(strtolower($this->getInput("Enable the spawn protection?", "y", "Y/n")) === "n"){
			$config->set("spawn-protection", -1);
		}else{
			$config->set("spawn-protection", 16);
		}
		$config->save();
	}

    private function generateUserFiles(){
		$this->message("An OP is the player admin of the server. OPs can run more commands than normal players!");

		$OP = strtolower($this->getInput("Enter the Minecraft username to give OP!"), "");
		if($OP === ""){
			$this->error("Nevermind, you will be able to add an OP user later using command /op <player>");
		}else{
			$OPs = new Config(\pocketmine\DATA . "ops.txt", Config::ENUM);
			$OPs->set($OP, true);
			$OPs->save();
		}

		$this->message("The whitelist mode only allows players in it to join.");

		$config = new Config(\pocketmine\DATA . "aldous.properties", Config::PROPERTIES);
		if(strtolower($this->getInput("Do you want to enable the whitelist mode?", "n", "y/N")) === "y"){
			$this->error("You will have to add the players to the whitelist.");
			$config->set("whitelist", true);
		}else{
			$config->set("whitelist", false);
		}
		$config->save();
	}
	
	private function networkFunctions(){
		$this->error("Query is a protocol used by different tools to get information of your server and players logged in.");
		$this->error("If you disable it, you won't be able to use server lists.");

		if(strtolower($this->getInput("Do you want to disable the Query?", "n", "y/N")) === "y"){
			$config->set("query", false);
		}else{
			$config->set("query", true);
		}

		$this->message("RCON is a protocol to remote connect with the server console using a password.");

		if(strtolower($this->getInput("Do you want to enable the RCON?", "n", "y/N")) === "y"){
			$config->set("rcon", true);
			$password = substr(base64_encode(random_bytes(20)), 3, 10);
			$config->set("rcon.password", $password);
			$this->message($this->lang->get("rcon_password") . ": " . $password);
		}else{
			$config->set("rcon", false);
		}
		$config->save();
	}
	
    private function endWizard(){
		$this->message("You have completed the setup in the installer!");
		$this->message("Aldous will starting for a less seconds...");
		$this->writeLine();
		$this->writeLine();
		sleep(4);
	}
	
	private function writeLine(string $line = ""){
		echo $line . PHP_EOL;
	}

	private function readLine() : string{
		return trim((string) fgets(STDIN));
	}

	private function message(string $message){
		$this->writeLine("[*] " . $message);
	}

	private function error(string $message){
		$this->writeLine("[!] " . $message);
	}

	private function getInput(string $message, string $default = "", string $options = "") : string{
		$message = "[?] " . $message;

		if($options !== "" or $default !== ""){
			$message .= " (" . ($options === "" ? $default : $options) . ")";
		}
		$message .= ": ";

		echo $message;

		$input = $this->readLine();

		return $input === "" ? $default : $input;
	}
}
