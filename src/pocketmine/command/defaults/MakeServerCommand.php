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

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Server;

class MakeServerCommand extends VanillaCommand{

	public function __construct(string $name){
		parent::__construct($name, "Create the server Software PHAR file", "/makeserver", ["ms"]);
		$this->setPermission("aldous.command.makeserver");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return false;
		}

		$server = $sender->getServer();
		$pharPath = Server::getInstance()->getPluginPath() . "Aldous" . DIRECTORY_SEPARATOR . $server->getName() . ".phar";
		if(file_exists($pharPath)){
			$sender->sendMessage("The PHAR file already exists, overwriting...");
			@unlink($pharPath);
		}
		$sender->sendMessage($server->getName() . " has started to create the server software! This will take 1-2 minutes to create. It may depending with your devices!");
		$phar = new \Phar($pharPath);
		$phar->setMetadata([
			"name" => $server->getName(),
			"version" => $server->getPocketMineVersion(),
			"api" => $server->getApiVersion(),
			"minecraft" => $server->getVersion(),
			"protocol" => ProtocolInfo::CURRENT_PROTOCOL,
			"creationDate" => time()
		]);
		$phar->setStub('<?php require_once("phar://". __FILE__ ."/src/pocketmine/PocketMine.php");  __HALT_COMPILER();');
		$phar->setSignatureAlgorithm(\Phar::SHA1);
		$phar->startBuffering();

		$filePath = substr(\pocketmine\PATH, 0, 7) === "phar://" ? \pocketmine\PATH : realpath(\pocketmine\PATH) . "/";
		$filePath = rtrim(str_replace("\\", "/", $filePath), "/") . "/";

		if(file_exists($filePath . "vendor")){
			foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath . "vendor")) as $file){
				$path = ltrim(str_replace([
					"\\",
					$filePath
				], [
					"/",
					""
				], $file), "/");
				if($path{0} === "." or strpos($path, "/.") !== false or substr($path, 0, 7) !== "vendor/"){
					continue;
				}
				$phar->addFile($file->getPathname(), $path);
			}
		}

		if(file_exists($filePath . "resources")){
			foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath . "resources")) as $file){
				$path = ltrim(str_replace([
					"\\",
					$filePath
				], [
					"/",
					""
				], $file), "/");
				if($path{0} === "." or strpos($path, "/.") !== false or substr($path, 0, 10) !== "resources/"){
					continue;
				}
				$phar->addFile($file->getPathname(), $path);
			}
		}

		/** @var \SplFileInfo $file */
		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath . "src")) as $file){
			$path = ltrim(str_replace([
				"\\",
				$filePath
			], [
				"/",
				""
			], $file), "/");
			if($path{0} === "." or strpos($path, "/.") !== false or substr($path, 0, 4) !== "src/"){
				continue;
			}
			$phar->addFile($file->getPathname(), $path);
		}

		foreach($phar as $file => $finfo){
			/** @var \PharFileInfo $finfo */
			if($finfo->getSize() > (1024 * 512)){
				$finfo->compress(\Phar::GZ);
			}
		}
		$phar->stopBuffering();
		$sender->sendMessage($server->getName() . " has been successfully created on " . $pharPath);

		return true;
	}
}
