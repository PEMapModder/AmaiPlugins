<?php

/*
__PocketMine Plugin__
class=LagFixer
name=LagFixer
author=PEMapModder
version=alpha 0
apiversion=12
*/

class LagFixer implements Plugin{
	public function __construct(ServerAPI $api, $s=0){
		$this->api = $api;
	}
	public function init(){
		$this->api->console->register("show", "<player|-all> show an invisible player or attempt to resend all players in your world to you", array($this, "showCmd"));
		$this->api->console->register("realhealth", "Send you real health to you", array($this, "rhCmd"));
		$this->api->console->alias("rh", "realhealth");
		$this->api->ban->cmdWhitelist("show");
		$this->api->ban->cmdWhitelist("realhealth");
	}
	public function showCmd($c, $a, $issuer){
		if(!($issuer instanceof Player)){
			return "Please run this command in-game.";
		}
		if(!isset($a[0])){
			return "Usage: /show <player|all> show an invisible player or attempt to resend all players in your world to you";
		}
		if($a[0] !== "-all"){
			$player = $this->api->player->get($a[0]);
			if(!($player instanceof Player)){
				return "Player $a[0] not found!";
			}
			if($player->entity->eid === $issuer->entity->eid){
				return "You should be unable to see yourself in the first place!";
			}
			$pk = new AddPlayerPacket;
			$pk->clientID = 0;
			$pk->username = $player->username;
			$e = $player->entity;
			$pk->eid = $e->eid;
			$pk->x = $e->x;
			$pk->y = $e->y;
			$pk->z = $e->z;
			$pk->pitch = $e->pitch;
			$pk->yaw = $e->yaw;
			$pk->unknown1 = 0;
			$pk->unknown2 = 0;
			$pk->metadata = $e->getMetadata();
			if($player->entity->level->getName() !== $issuer->entity->level->getName()){
				$pk->x = 512;
				$pk->y = 512;
				$pk->z = 512;
			}
			$issuer->dataPacket($pk);
			$pk = new PlayerEquipmentPacket;
			$pk->eid = $e->eid;
			$pk->item = $player->getSlot($player->slot)->getID();
			$pk->meta = $player->getSlot($player->slot)->getMetadata();
			$pk->slot = 0;
			$issuer->dataPacket($pk);
			$player->sendArmor($issuer);
			return "Packet adding $player has been sent to you.";
		}
		foreach($this->api->player->getAll() as $player){
			if($player->eid === $issuer->eid){
				continue;
			}
			if(!($player instanceof Player)){
				return "Player $a[0] not found!";
			}
			$pk = new AddPlayerPacket;
			$pk->clientID = 0;
			$pk->username = $player->username;
			$e = $player->entity;
			$pk->eid = $e->eid;
			$pk->x = $e->x;
			$pk->y = $e->y;
			$pk->z = $e->z;
			$pk->pitch = $e->pitch;
			$pk->yaw = $e->yaw;
			$pk->unknown1 = 0;
			$pk->unknown2 = 0;
			$pk->metadata = $e->getMetadata();
			if($player->entity->level->getName() !== $issuer->entity->level->getName()){
				$pk->x = 512;
				$pk->y = 512;
				$pk->z = 512;
			}
			$issuer->dataPacket($pk);
			$pk = new PlayerEquipmentPacket;
			$pk->eid = $e->eid;
			$pk->item = $player->getSlot($player->slot)->getID();
			$pk->meta = $player->getSlot($player->slot)->getMetadata();
			$pk->slot = 0;
			$issuer->dataPacket($pk);
			$player->sendArmor($issuer);
		}
		return "Packets adding all players have been resent to you.";
	}
	public function rhCmd($c, $a, $p){
		if(!($p instanceof Player)){
			return "Please run this command in-game.";
		}
		$health = $p->entity->getHealth();
		$pk = new SetHealthPacket;
		$pk->health = $health;
		$p->dataPacket($pk);
		$health /= 2;
		return "You real health ($health hearts) has been updated.";
	}
	public function __destruct(){
	}
}
