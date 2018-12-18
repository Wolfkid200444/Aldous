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
 * Set-up wizard used on the first run.
 * Can be disabled with --no-installer
 * Disabled causes IP cannot be checked.
 */
namespace pocketmine;

use pocketmine\lang\Language;
use pocketmine\lang\LanguageNotFoundException;
use pocketmine\utils\Config;
use pocketmine\utils\Internet;

class Installer{
	public const DEFAULT_NAME = "Hello, " . \pocketmine\NAME . "!";
	public const DEFAULT_PORT = 19132;
	public const DEFAULT_PLAYERS = 50;
	public const DEFAULT_GAMEMODE = 0;

	/** @var Language */
	private $lang;

	public function __construct(){

	}

	public function run() : bool{
		$this->message(\pocketmine\NAME . " installer!");
        $this->message("This setup installer will be on English version.");

		try{
			$langs = Language::getLanguageList();
		}catch(LanguageNotFoundException $e){
			$this->error("No language files found, please use provided builds or clone the repository recursively.");
			return false;
		}

		$this->message("Hello, please select a language before get started!");
		foreach($langs as $short => $native){
			$this->writeLine(" $native => $short");
		}

		do{
			$lang = strtolower($this->getInput("Language", "eng"));
			if(!isset($langs[$lang])){
				$this->error("Sorry, couldn't find the language!");
				$lang = null;
			}
		}while($lang === null);

		$config = new Config(\pocketmine\DATA . "aldous.properties", Config::PROPERTIES);
		$config->set("language", $lang);
		$config->save();

		$this->lang = new Language($lang);

		$this->message($this->lang->get("language_has_been_selected"));
        $this->message("Remember this setup installer will be on English version!");

		if(!$this->showLicense()){
			return false;
		}

		if(strtolower($this->getInput("Are you sure to skip the installer?", "n", "y/N")) === "y"){
			return true;
		}

		$this->writeLine();
		$this->welcome();
        $this->ipFunctions();
		$this->generateBaseConfig();
		$this->generateUserFiles();
		$this->networkFunctions();
		$this->endInstaller();

		return true;
	}

	private function showLicense() : bool{
		$this->message($this->lang->translateString("Hello newbie, welcome to ", [\pocketmine\NAME]));
		echo <<<LICENSE

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Lesser General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

LICENSE;
		$this->writeLine();
		if(strtolower($this->getInput($this->lang->get("accept_license"), "n", "y/N")) !== "y"){
			$this->error($this->lang->translateString("you_have_to_accept_the_license", [\pocketmine\NAME]));
			sleep(5);

			return false;
		}

		return true;
	}

	private function welcome(){
		$this->message("You will setup your own server now!");
		$this->message("If you don't want to change the default value, just press Enter!");
		$this->message("You can edit later on aldous.properties file.");
	}
	
    private function ipFunctions(){
        $this->message("Checking the IP in 5 seconds...");
        sleep(5);

        $config = new Config(\pocketmine\DATA . "aldous.properties", Config::PROPERTIES);
        $this->message($this->lang->get("ip_get"));
        $externalIP = Internet::getIP();
        if($externalIP === false){
           $externalIP = "unknown (server offline)";
        }
        $internalIP = gethostbyname(trim(`hostname`));
		$this->error($this->lang->translateString("ip_warning", ["EXTERNAL_IP" => $externalIP, "INTERNAL_IP" => $internalIP]));
		$this->error($this->lang->get("ip_confirm"));
        $this->readLine();
		
        $this->message("Your external IP will saved on aldous.properties file.");
        $this->message("NOTE: Do not change the external IP or it may cause RakLib crash.");
        $config->set("ip", ["EXTERNAL_IP" => $externalIP]);
    }

	private function generateBaseConfig(){
		$config = new Config(\pocketmine\DATA . "aldous.properties", Config::PROPERTIES);

		$config->set("motd", ($name = $this->getInput($this->lang->get("name_your_server"), self::DEFAULT_NAME)));
		$config->set("name", $name);

		$this->message($this->lang->get("port_warning"));

		do{
			$port = (int) $this->getInput($this->lang->get("server_port"), (string) self::DEFAULT_PORT);
			if($port <= 0 or $port > 65535){
				$this->error($this->lang->get("invalid_port"));
				continue;
			}

			break;
		}while(true);
		$config->set("port", $port);

		$this->message($this->lang->get("gamemode_info"));

		do{
			$gamemode = (int) $this->getInput($this->lang->get("default_gamemode"), (string) self::DEFAULT_GAMEMODE);
		}while($gamemode < 0 or $gamemode > 3);
		$config->set("gamemode", $gamemode);

		$config->set("maximum-players", (int) $this->getInput($this->lang->get("max_players"), (string) self::DEFAULT_PLAYERS));

		$this->message($this->lang->get("spawn_protection_info"));

		if(strtolower($this->getInput($this->lang->get("spawn_protection"), "y", "Y/n")) === "n"){
			$config->set("spawn-protection", -1);
		}else{
			$config->set("spawn-protection", 16);
		}

		$config->save();
	}

	private function generateUserFiles(){
		$this->message($this->lang->get("op_info"));

		$op = strtolower($this->getInput($this->lang->get("op_who"), ""));
		if($op === ""){
			$this->error($this->lang->get("op_warning"));
		}else{
			$ops = new Config(\pocketmine\DATA . "ops.txt", Config::ENUM);
			$ops->set($op, true);
			$ops->save();
		}

		$this->message($this->lang->get("whitelist_info"));

		$config = new Config(\pocketmine\DATA . "aldous.properties", Config::PROPERTIES);
		if(strtolower($this->getInput($this->lang->get("whitelist_enable"), "n", "y/N")) === "y"){
			$this->error($this->lang->get("whitelist_warning"));
			$config->set("whitelist", true);
		}else{
			$config->set("whitelist", false);
		}
		$config->save();
	}

	private function networkFunctions(){
		$this->error($this->lang->get("query_warning1"));
		$this->error($this->lang->get("query_warning2"));
		if(strtolower($this->getInput($this->lang->get("query_disable"), "n", "y/N")) === "y"){
			$config->set("query", false);
		}else{
			$config->set("query", true);
		}

		$this->message($this->lang->get("rcon_info"));
		if(strtolower($this->getInput($this->lang->get("rcon_enable"), "n", "y/N")) === "y"){
			$config->set("rcon", true);
			$password = substr(base64_encode(random_bytes(20)), 3, 10);
			$config->set("rcon.password", $password);
			$this->message($this->lang->get("rcon_password") . ": " . $password);
		}else{
			$config->set("rcon", false);
		}
		$config->save();
	}

	private function endInstaller(){
		$this->message("You have finished setup your Aldous server and would be start for 4 seconds...");
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
