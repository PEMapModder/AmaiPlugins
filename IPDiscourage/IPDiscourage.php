<?php

/*
__PocketMine Plugin__
class=IPDiscourage
name=IPDiscourage
version=0
apiversion=12
author=PEMapModder
*/

class IPDiscourage implements Plugin{
	public function __construct(ServerAPI $api, $server = false){
		$this->a = $a;
	}
	public function init(){
		$this->a->console->register("dipb", "<player|IP> Bans an IP while discouraging them to bypass it using another name by trolling", array($this, "cmd"));
		@touch($this->path);
		$this->banned = explode(PHP_EOL, file_get_contents($this->path));
		$this->config = new Config($this->cPath, CONFIG_YAML, array(
			"ban broadcast with name (not shown to the banned player)" => "@name (IP @ip) has been banned by @banner",
			"ban broadcast without name (used when the banned IP is offline)" => "@ip has been banned by @banner",
			"ban message shown to the banned player (if online)" => "You have been banned by @banner!",
		));
		$this->a->addHandler("console.command", array($this, "onCmd"), 51);
	}
	public function __destruct(){
		file_put_contents($this->path, implode(PHP_EOL, $this->banned));
	}
	public function cmd($c, $a, $banner){
		if(!isset($a[0])){
			return "Usage: /dipb <player|IP> Bans an IP while discouraging them to bypass it using another name by trolling";
		}
		$ip = $a[0];
		if(!preg_match("#[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\$#", $a[0])){
			$p = $this->a->player->get($a[0]);
			if(!($p instanceof Player)){
				return "Player $a[0] not found or $a[0] not an IP";
			}
			$ip = $p->ip;
		}
		$this->banned[] = $ip;
		foreach($this->a->player->getAll() as $p){
			if($p->ip === $ip){
				$player = $p;
			}
		}
		if(isset($player)){
			$msg = str_replace(array("@name", "@ip", "@banner"), array($player->username, $ip, "$banner"), $this->config->get("ban broadcast with name (not shown to the banned player)"));
			$player->sendChat(str_replace("@banner", "$banner", $this->config->get("ban message shown to the banned player (if online)")));
		}
		else{
			$msg = str_replace(array("@ip", "@banner"), array($ip, "$banner"), $this->config->get("ban broadcast without name (used when the banned IP is offline)"));
		}
		$this->a->chat->broadcast($msg);
		return "$ip has banned!";
	}
	public function onJoin($data){
		if(in_array($data->ip, $this->banned)){
			$path = $this->a->plugin->configPath(SimpleAuthAPI::get())."players/".$data->iusername{0}."/".$data->iusername.".yml";
			if(!is_file($path)) return;
			$d = yaml_parse(file_get_contents($path));
			if($d["lastip"] === "trolled") return;
			$d["lastip"] = "trolled";
			$this->a->plugin->writeYAML($path, $d); // avoid IP auth
		}
	}
	public function onCmd($data){
		if($data["issuer"] instanceof Player and in_array($data["issuer"]->ip, $this->banned)){
			return true; // whatever it is
		}
	}
}
