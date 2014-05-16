<?php

/*
__PocketMine Plugin__
class=pemapmodder\invpeeper\Main
name=InventoryPeeper
author=PEMapModder
version=0
apiversion=12
*/


namespace pemapmodder\invpeeper;

use pocketmine\Player;
use pocketmine\command\Command as Cmd;
use pocketmine\command\CommandExecutor as CmdExe;
use pocketmine\command\CommandSender as Issuer;
use pocketmine\command\PluginCommand as PCmd;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\plugin\PluginBase as PB;

if(!class_exists("pocketmine\\Server")){
	class Main implements \Plugin{
		public function __construct(\ServerAPI $api, $s=0){
			$this->api=$api;
		}
		public function __destruct(){
		}
		public function init(){
			$this->api->console->register("peepinv", "<player>", array($this, "onCmd"));
		}
		public function onCmd($c, $a, $issuer){
			if(!($issuer instanceof \Player))
				return "Please run this command in-game.";
			if(!isset($args[0])){
				$issuer->sendInventory();
				return "Inventory restored.";
			}
			$ig=strtoupper($issuer->getGamemode());
			eval("\$ig=$ig;");
			if($ig%1===1)
				return "You are in creative.";
			$p=$this->api->player->get($args[0]);
			if(!($p instanceof \Player))
				return "Player not found.";
			$pg=strtoupper($p->getGamemode());
			eval("\$pg=$pg;");
			if($pg%1===1)
				return "Player not found.";
			$hotbar=array();
			foreach($p->hotbar as $slot)
				$hotbar[]=$slot<=-1?-1:$slot+9;
			$pk=new \ContainerSetContentPacket;
			$pk->windowid=0;
			$pk->hotbar=$hotbar;
			$pk->slots=$p->inventory;
			$issuer->dataPacket($pk);
			return "Peeping inventory of $p!";
		}
	}
}
else{
	class Main extends PB implements CmdExe{
		public function onEnable(){
			$cmd=new PCmd("peepinv", $this);
			$cmd->setUsage("<player>");
			$cmd->register($this->getServer()->getCommandMap());
		}
		public function onCommand(Issuer $issuer, Cmd $cmd, $l, array $args){
			if(!($issuer instanceof Player)){
				$issuer->sendMessage("Please run this command in-game.");
				return true;
			}
			if(!isset($args[0])){
				$issuer->sendInventory();
				$issuer->sendMessage("Inventory restored.");
				return true;
			}
			$p=Player::get($args[0]);
			if(!($p instanceof Player))
				return false;
			if($p->getGamemode()%2===1){
				$issuer->sendMessage("Player $p is in creative.");
				return true;
			}
			if($issuer->getGamemode()%2===1){
				$issuer->sendMessage("You are in creative.");
				return true;
			}
			$hotbar=array();
			foreach($p->hotbar as $slot)
				$hotbar[]=$slot<=-1?-1:$slot+9;
			$pk=new ContainerSetContentPacket;
			$pk->windowid=0;
			$pk->slots=$p->inventory;
			$pk->hotbar=$hotbar;
			$issuer->dataPacket($pk);
			$issuer->sendMessage("Peeping inventory of $issuer!");
			return true;
		}
	}
}
