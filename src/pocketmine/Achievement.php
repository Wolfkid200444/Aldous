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

namespace pocketmine;

use pocketmine\lang\TranslationContainer;
use pocketmine\utils\TextFormat;

/**
 * Handles the achievement list and a bit more
 */
abstract class Achievement{
    /**
     * @var array[]
     */
    public static $list = [
        "openInventory" => array([
            "name" => "Inventory Newbie",
            "requires" => []
        ]),
        "mineWood" => [
            "name" => "Gather Wood",
            "requires" => [ 
				"openInventory",
            ]
        ],
        "buildWorkBench" => [
            "name" => "Craft Beginner",
            "requires" => [
                "mineWood"
            ]
        ],
        "buildPickaxe" => [
            "name" => "The New Miner",
            "requires" => [
                "buildWorkBench"
            ]
        ],
        "buildFurnace" => [
            "name" => "The Cook Burner",
            "requires" => [
                "buildPickaxe"
            ]
        ],
        "acquireIron" => [
            "name" => "The Iron Hardware",
            "requires" => [
                "buildFurnace"
            ]
        ],
        "buildHoe" => [
            "name" => "Journey To The Farm",
            "requires" => [
                "buildWorkBench"
            ]
        ],
        "makeBread" => [
            "name" => "Bread Maker",
            "requires" => [
                "buildHoe"
            ]
        ],
        "bakeCake" => [
            "name" => "Cake Baker",
            "requires" => [
                "buildHoe"
            ]
        ],
        "buildBetterPickaxe" => [
            "name" => "The Upgraded Miner",
            "requires" => [
                "buildPickaxe"
            ]
        ],
        "buildSword" => [
            "name" => "Prepare To Combat",
            "requires" => [
                "buildWorkBench"
            ]
        ],
        "diamonds" => [
            "name" => "Diamond Searcher",
            "requires" => [
                "acquireIron"
            ]
        ]

    ];


    /**
     * @param Player $player
     * @param string $achievementId
     *
     * @return bool
     */
    public static function broadcast(Player $player, string $achievementId) : bool{
        if(isset(Achievement::$list[$achievementId])){
            $translation = new TranslationContainer("chat.type.achievement", [$player->getDisplayName(), TextFormat::GREEN . Achievement::$list[$achievementId]["name"] . TextFormat::RESET]);
            if(Server::getInstance()->getConfigBool("announce-player-achievements", true)){
                Server::getInstance()->broadcastMessage($translation);
            }else{
                $player->sendMessage($translation);
            }

            return true;
        }

        return false;
    }

    /**
     * @param string $achievementId
     * @param string $achievementName
     * @param array  $requires
     *
     * @return bool
     */
    public static function add(string $achievementId, string $achievementName, array $requires = []) : bool{
        if(!isset(Achievement::$list[$achievementId])){
            Achievement::$list[$achievementId] = [
                "name" => $achievementName,
                "requires" => $requires
            ];

            return true;
        }

        return false;
    }
}
