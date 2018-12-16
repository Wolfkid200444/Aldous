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

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\plugin\PharPluginLoader;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ExtractPluginCommand extends VanillaCommand{

    public function __construct(string $name){
        parent::__construct(
            $name,
            "Extract from PHAR to ZIP",
            '/extractplugin <plugin-name>',
            ["ep"], [[
                new CommandParameter("plugin", CommandParameter::ARG_TYPE_RAWTEXT, false)
            ]]
        );

        $this->setPermission("aldous.command.extractphar");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$this->testPermission($sender)){
            return true;
        }

        if(count($args) < 1){
            throw new InvalidCommandSyntaxException();
        }

        $pluginName = trim(implode(" ", $args));
        if($pluginName === "" or !(($plugin = Server::getInstance()->getPluginManager()->getPlugin($pluginName)) instanceof Plugin)){
            $sender->sendMessage(TextFormat::RED . "An invalid plugin name, please check the name case.");
            return true;
        }
        $description = $plugin->getDescription();

        if(!($plugin->getPluginLoader() instanceof PharPluginLoader)){
            $sender->sendMessage(TextFormat::RED . "The plugin " . $description->getName() . " is not in the PHAR structure.");
            return true;
        }

        $folderPath = Server::getInstance()->getPluginPath() . "Aldous" . DIRECTORY_SEPARATOR . $description->getFullName() . DIRECTORY_SEPARATOR;
        if(file_exists($folderPath)){
            $sender->sendMessage("The plugin already exists, overwriting...");
        }else{
            @mkdir($folderPath);
        }

        $reflection = new \ReflectionClass("pocketmine\\plugin\\PluginBase");
        $file = $reflection->getProperty("file");
        $file->setAccessible(true);
        $pharPath = str_replace("\\", "/", rtrim($file->getValue($plugin), "\\/"));

        foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pharPath)) as $fInfo){
            $path = $fInfo->getPathname();
            @mkdir(dirname($folderPath . str_replace($pharPath, "", $path)), 0755, true);
            file_put_contents($folderPath . str_replace($pharPath, "", $path), file_get_contents($path));
        }
        $sender->sendMessage("The source plugin " . $description->getFullName() . " has been successfully created on " . $folderPath);

        return true;
    }
}
