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

namespace pocketmine\network\mcpe\handler;

use pocketmine\entity\Skin;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\network\mcpe\PlayerNetworkSession;
use pocketmine\network\mcpe\ProcessLoginTask;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Player;
use pocketmine\PlayerParameters;
use pocketmine\Server;
use pocketmine\utils\UUID;

class LoginSessionHandler extends SessionHandler{

	/** @var Server */
	private $server;
	/** @var PlayerNetworkSession */
	private $session;

	public function __construct(Server $server, PlayerNetworkSession $session){
		$this->server = $server;
		$this->session = $session;
	}

	public function handleLogin(LoginPacket $packet) : bool{
		if(!$this->checkProtocolVersion($packet->protocol)){
			return true;
		}

		if(!Player::isValidUserName($packet->username)){
			$this->session->serverDisconnect("disconnectionScreen.invalidName");

			return true;
		}

		$skin = new Skin(
			$packet->clientData["SkinId"],
			base64_decode($packet->clientData["SkinData"] ?? ""),
			base64_decode($packet->clientData["CapeData"] ?? ""),
			$packet->clientData["SkinGeometryName"] ?? "",
			base64_decode($packet->clientData["SkinGeometry"] ?? "")
		);

		if(!$skin->isValid()){
			$this->session->serverDisconnect("disconnectionScreen.invalidSkin");

			return true;
		}

		$params = new PlayerParameters();
		$params->setUsername($packet->username);
		$params->setUuid(UUID::fromString($packet->clientUUID));
		$params->setXuid($packet->xuid ?? "");
		$params->setLocale($packet->locale);
		$params->setClientId($packet->clientId);
		$params->setSkin($skin);

		//TODO: add more stuff from loginpacket

		$ev = new PlayerPreLoginEvent($this->session, $params);

		if(!$this->server->isWhitelisted($params->getUsername())){
			$ev->setKickFlag(PlayerPreLoginEvent::REASON_WHITELIST);
		}
		if($this->server->getNameBans()->isBanned($params->getUsername()) or $this->server->getIPBans()->isBanned($this->session->getIp())){
			$ev->setKickFlag(PlayerPreLoginEvent::REASON_BANNED);
		}
		if(count($this->server->getOnlinePlayers()) >= $this->server->getMaxPlayers()){
			$ev->setKickFlag(PlayerPreLoginEvent::REASON_SERVER_FULL);
		}

		$this->server->getPluginManager()->callEvent($ev);
		if(!$ev->canJoin()){
			$message = $ev->getKickMessage();
			if($message === null){
				if($ev->hasKickFlag(PlayerPreLoginEvent::REASON_SERVER_FULL)){
					$message = "disconnectionScreen.serverFull";
				}elseif($ev->hasKickFlag(PlayerPreLoginEvent::REASON_BANNED)){
					$message = "You are banned";
				}elseif($ev->hasKickFlag(PlayerPreLoginEvent::REASON_WHITELIST)){
					$message = "Server is white-listed";
				}elseif($ev->hasKickFlag(PlayerPreLoginEvent::REASON_PLUGIN)){
					$message = "Plugin reason";
				}else{
					$message = "disconnectionScreen.noReason";
				}
			}

			$this->session->serverDisconnect($message);

			return true;
		}

		$this->session->setPlayerParams($params);


		if(!$packet->skipVerification){
			$this->server->getAsyncPool()->submitTask(new ProcessLoginTask($this->session, $packet));
		}else{
			$this->session->onClientAuthenticated($packet, null, false);
		}

		return true;
	}

	private function checkProtocolVersion(int $protocolVersion) : bool{
		if($protocolVersion !== ProtocolInfo::CURRENT_PROTOCOL){
			$pk = new PlayStatusPacket();
			$pk->protocol = $protocolVersion;
			if($protocolVersion < ProtocolInfo::CURRENT_PROTOCOL){
				$pk->status = PlayStatusPacket::LOGIN_FAILED_CLIENT;
			}else{
				$pk->status = PlayStatusPacket::LOGIN_FAILED_SERVER;
			}

			$this->session->sendDataPacket($pk, true);

			//This pocketmine disconnect message will only be seen by the console (PlayStatusPacket causes the messages to be shown for the client)
			$this->session->serverDisconnect($this->server->getLanguage()->translateString("pocketmine.disconnect.incompatibleProtocol", [$protocolVersion]), false);
			return false;
		}

		return true;
	}
}
