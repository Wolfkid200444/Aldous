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

namespace pocketmine\network\mcpe;

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\NetworkInterface;

/**
 * Session implementations wanting to be attached to a Player must implement this interface
 */
interface PlayerNetworkSession{
	/**
	 * @return string
	 */
	public function getIp() : string;

	/**
	 * @return int
	 */
	public function getPort() : int;

	/**
	 * Returns the last measured latency for this player, in milliseconds. This is measured automatically and reported
	 * back by the network interface.
	 *
	 * @return int
	 */
	public function getPing() : int;

	/**
	 * @return NetworkInterface
	 */
	public function getInterface() : NetworkInterface;

	/**
	 * Called by Player to set the logged-in flag on the network session - TODO REMOVE
	 */
	public function setLoggedIn() : void;

	/**
	 * Returns whether this session is still active, i.e. not disconnected
	 *
	 * @return bool
	 */
	public function isConnected() : bool;

	/**
	 * @param DataPacket $packet
	 * @param bool       $immediateFlush
	 * @param bool       $fireEvent
	 *
	 * @return bool
	 */
	public function sendDataPacket(DataPacket $packet, bool $immediateFlush = false, bool $fireEvent = true) : bool;

	/**
	 * @param string $reason
	 * @param bool   $mcpeDisconnect
	 */
	public function serverDisconnect(string $reason = "", bool $mcpeDisconnect = true) : void;

	/**
	 * Called by the server to notify the session of a pending batch packet which is not yet ready to be sent.
	 * The parameter will usually be an empty object whose buffer will be populated at the end of an AsyncTask.
	 *
	 * @param CompressedPacketBuffer $buffer
	 */
	public function notifyPendingBatch(CompressedPacketBuffer $buffer) : void;

	/**
	 * Sends a prepared batch of packets to the network session. This can be used to send cached packets.
	 *
	 * @param CompressedPacketBuffer $buffer
	 * @param bool                   $immediateFlush Whether to immediately flush this packet onto the network
	 */
	public function sendPreparedBatch(CompressedPacketBuffer $buffer, bool $immediateFlush = false) : void;

	/**
	 * Sends any pending queued batches to the network, if possible. Taking ordering into account, it is possible that
	 * this might send no packets if the session is still waiting for other packets to be asynchronously prepared.
	 *
	 * @param bool $immediateFlush
	 */
	public function flushBatchQueue(bool $immediateFlush = false) : void;
}
