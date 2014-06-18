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
		$this->api->console->register("show", "<player|all> show an invisible player or attempt to resend all players in your world to you", array($this, "showCmd"));
		$this->api->console->register("realhealth", "Send you real health to you", array($this, "rhCmd"));
		$this->api->ban->cmdWhitelist("show");
		$this->api->ban->cmdWhitelist("realhealth");
	}
	public function showCmd($c, $a, $p){
		if(!isset($a[0])){
			return "Usage: /show <player|all> show an invisible player or attempt to resend all players in your world to you";
		}
		
	}
	public function rhCmd($c, $a, $p){
		
	}
	public function __destruct(){
	}
}
