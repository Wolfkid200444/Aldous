<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

/**
 * Network-related classes
 */
namespace pocketmine\network;

/**
 * Source interfaces are network implementations which players can connect through. Network implementations must
 * implement this interface in order to be registered in the server Network and process connections.
 */
interface SourceInterface{

	/**
	 * Performs actions needed to start the interface after it is registered.
	 */
	public function start() : void;

	/**
	 * @param string $name
	 */
	public function setName(string $name) : void;

	/**
	 * Called every tick to process events on the interface.
	 */
	public function process() : void;

	public function shutdown() : void;

	public function emergencyShutdown() : void;

}
