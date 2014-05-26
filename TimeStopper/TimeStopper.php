<?php

/*
__PocketMine Plugin__
class=PEMapModder\TimeStopper\Main
name=TimeStopper
author=PEMapModder
version=0
apiversion=12
(strictly 12 only)
*/

namespace PEMapModder\TimeStopper;

class Main implements \Plugin{
	public $pdir;
	public function __construct(ServerAPI $api, $s = 0){
		$this->api = $api;
	}
	public function init(){
		@mkdir($this->pdir = $this->api->plugin->configPath($this)."players/");
		$this->api->console->register("tstpr", "[time|off] move/dispose/locate your time stopper", array($this, "cmd"));
		$this->api->console->alias("ts", "tstpr");
		\DataPacketSendEvent::register(array($this, "onDPSend"));
	}
	public function onDPSend($evt){
		if($evt->getPacket()->pid() === \ProtocolInfo::SET_TIME_PACKET and $this->getStopper($evt->getPlayer()->iusername) !== "nowhere"){
			$evt->setCancelled(true);
		}
	}
	public function cmd($cmd, $arg, $isr){
		if(is_string($isr)){
			return "Please run this command in-game.";
		}
		if(!isset($arg[0])){
			return "Your time stopper is at ".$this->getStopper($isr->iusername);
		}
		return "Your time stopper is moved to ".($t = $this->setStopper($isr->iusername, $arg[0])).".";
	}
	public function setStopper($iname, $v){
		if($v === false or @strtolower($v) === "off"){
			return $this->rmStopper($iname);
		}
		file_put_contents($this->pdir."$iname.txt", (int) $v);
		return (int) $v;
	}
	public function rmStopper($iname){
		@unlink($this->pdir."$iname.txt");
		return "off";
	}
	public function getStopper($iname){
		return ($t = @file_get_contents($this->pdir."$iname.txt")) === false ? "nowhere";"$t";
	}
	public function __destruct(){
	}
}
