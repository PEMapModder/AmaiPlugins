<?php
/*
__PocketMine Plugin__
class=CiapPlugin
name=CIAP Loader
description=ConfigIsAlsoPlugin
author=PEMapModder
apiversion=12
version=alpha 0.0
*/

/*
CHANGLOG:
Edition alpha - Proof of Theory
	Version 0 -> Initial build
		Commit #01d3739 => Initial commit for initialization on GitHub (no code)
		Commit #8d1548d => Added the initial framework of loading CIAPs
Edition beta - Public pre-release
Edition gamma - Public release
*/

class CiapPlugin implements Plugin{
	public $c=array(), $cmds=array();
	public function __construct(ServerAPI $a, $s=0){
		$this->d=$a->plugin->configPath($this);
		$this->pd=$this->d."plugins/";
		$this->a=$a;
		$this->p=$a->plugin;
	}
	public function __destruct(){
	}
	public function init(){
		$this->s=ServerAPI::request();
		$list=scandir($this->pd);
		foreach($list as $f){
			if(is_file($this->pd.$f) and strtolower(substr($f, 5))=="ciap-" and strtolower(substr($f, -4))==".yml"){
				$this->load($this->pd.$f);
			}
		}
		console(FORMAT_GREEN."[INFO] Success on loading all CIAPs at folder ".$this->pd.".".FORMAT_RESET);
	}
	public function load($file){
		console("[INFO] Parsing plugin ".FORMAT_LIGHT_PURPLE.$file.FORMAT_RESET."... ", false);
		$c=yaml_parse(preg_replace("#^([ ]*)([a-zA-Z_]{1}[^\:]*)\:#m", "$1\"$2\":", str_replace("\t", "    "/*four spaces*/, file_get_contents($file)))); // RegExp by @PocketMine and by @shoghicp
		foreach(array("name", "author", "version-code", "min-api", "target-api") as $key){
			if(!isset($c[$key])){
				console(" Failure!".PHP_EOL."[ERROR] $key is not given in CIAP plugin at $file!");
				return false;
			}
			if($key!=="name" and $key!=="author" and !is_numeric($c[key])){
				console("Failure!".PHP_EOL."[ERROR] $key must be an integral value, ".$c[$key]." given, at $file");
				return false;
			}
		}
		$this->initializeCiap($c["name"]);
		$this->ciaps[$c["name"]]=$c;
		console(FORMAT_GREEN."Success loading!");
	}
	public function initializeCiap($name){
		$p=&$this->ciaps[$name];
		if(isset($p["commands"])){
			foreach($p["commands"] as $cmd=>$actions){
				$this->a->console->register($cmd, $actions["description"], array($this, "cmdHandler"));
				if($actions["allow-non-op-use"]===true)
					$this->a->ban->cmdWhitelist($cmd);
				$this->cmds[$cmd]=$actions["actions"];
			}
		}
	}
	public function cmdHandler($cmd, $args, $issuer){
		$this->
	}
}
