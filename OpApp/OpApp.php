<?php

namespace PEMapModder\OpApp;

/*
__PocketMine Plugin__
class=PEMapModder\OpApp\Main
name=OpApp
version=0
apiversion=12
author=PEMapModder
*/

class Main implements \Plugin{
	private $s=array();
	private $a;
	private $c;
	private $n, $i, o;
	public function __construct(\ServerAPI $a, $s=0){
		$this->a=$a;
	}
	public function init(){
		$this->a->addHandler("player.spawn", array($this, "onJoin"));
		$this->a->addHandler("player.quit", array($this, "onQuit"));
		$this->a->console->register("opapp", "<details> submit an op application form", array($this, "onCmd"));
		$f=$this->a->plugin->configPath($this);
		$e=PHP_EOL;
		$this->c=new \Config($f."config.yml", \CONFIG_YAML, array(
			"app-format"=>"name: %name".$e."ip: %ip".$e."applications:$e %appsdetail".$e."kicks:$e %kicksdetail",
			"app-detail-format"=>"    online minutes: %onlinemins".$e."    message: %msg".$e,
			"kick-detail-format"=>"    kicked by: %kicker".$e."    reason: %reason".$e."date: %date(d-m-y h:m:s)".$e
		);
	}
	public function onJoin($p){
		$this->s[$p->CID]=microtime(true);
	}
	public function onQuit($p){
		if(!isset($this->s[$p->CID]))
			return;
		$this->update($p->iusername, $p->ip, $this->s[$p->CID]);
	}
	public function onCmd($c, $d, $i){
	}
	public function update($n, $a, $t){
		
	}
	public function export(\Player $p){
		
	}
}
