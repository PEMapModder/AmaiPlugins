<?php
/*
__PocketMine Plugin__
author=YourName
name=PluginName
version=PluginVersion
class=PluginClassName0x0000
apiversion=11
*/
//@link https://github.com/pemapmodder/DST/DST-dummy.php
//Free to clone THIS SINGLE file and modify as anything you want without needing to give me credit
class PluginClassName0x0000 implements Plugin{
	private $dst,$pn,$s;
	public function __construct(ServerAPI $api,$server=false){
		$this->pn=$api->plugin;
	}
	public function init(){
		if(ServerAPI::request()->handle("dst.isloaded",$this)!=="loaded")
			$this->pn->load("http://github.com/pemapmodder/DST/raw/master/_DST_API.php");
		ServerAPI::request()->addHandler("dst.inited",array($this,"yourInitFunction"));
	}
	public function yourInitFunction(Plugin $dst){
		$this->dst=$dst;
		$this->s=ServerAPI::request();
	}
	public function __destruct(){
		
	}
}
