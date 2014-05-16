<?php

/*
__PocketMine Plugin__
class=IntroducerPlugin
name=Introducer
author=PEMapModder
description=Customizable AI player that is only visible to a player, always next to him. Servers as an introducer to the server.
version=alpha 0.0
apiversion=12
*/

class IntroducerPlugin implements Plugin{
	public static $config=false;
	public function __construct(ServerAPI $api, $server=false){
		$this->api=$api;
		$this->ent=$api->entity->add($api->level->getDefault(), ENTITY_PLAYER, null, array("x"=>128, "y"=>1024, "z"=>128, "player"=>null)); // placeholder for general eid
		$this->publicEid=$this->ent->eid;
		$api->schedule(20, array($api->entity->get($this->publicEid), "setPosition"), new Vector3(128, 1024, 128), true);
	}
	public function __destruct(){
	}
	public function init(){
		$this->initConfig();
		$this->api->addHandler("player.spawn", array($this, "initBot"));
		$this->api->addHandler("player.quit", array($this, "fnlzBot"));
		$this->api->addHandler("player.move", array($this, "moveBot"));
		$this->api->addHandler("player.teleport", array($this, "tpBot"));
		$this->api->addHandler("player.interact", array($this, "saveTheBot"), 0x1000);
		$this->api->addHandler("player.death", array($this, "freezeBot"));
		$this->api->addHandler("player.respawn", array($this, "preFreeBot"));
	}
	public function initConfig(){
		$ext="yml";
		$dir=$this->api->plugin->configPath($this);
		if(file_exists($dir."txt"))
			$ext="txt";
		self::$config=new Config($dir."lang.$ext", CONFIG_YAML, array(
				"Bot name"=>"Introducer",
				"scheduled chats"=>array(
						array(3600, "You have continuously played for a whole hour. Do you think you should put down your phone and rest for a while?"),
				),
		));
	}
	public function saveTheBot($data){
		console("[DEBUG] saveTheBot(", false, true, 2);
		var_dump($data);
		console(") called.", true, true, 2);
		if($data["eid"]===$this->publicEid){ // this is quite convenient :D
			$data["entity"]->player->sendChat("Hey, don't attack your introducer!");
			return false;
		}
	}
	public function initBot(Player $player){
		$this->bots[$player->iusername]=new Introducer($player, array(), $this->publicEid);
	}
	public function fnlzBot(Player $player){
		if(!isset($this->bots[$player->iusername]))return;
		$this->bots[$player->iusername]->finalize();
	}
	public function moveBot(Entity $ent){// uh-oh environmental update
		if(!isset($this->bots[$ent->player->iusername]))return;
		$this->bots[$ent->player->iusername]->envUpdate();
	}
	public function tpBot($data){
		if(!isset($this->bots[$data["player"]->iusername]))return;
		$this->bots[$data["player"]->iusername]->teleport($data["target"]);
	}
	public function freezeBot($data){
		if(!isset($this->bots[$data["player"]->iusername]))return;
		$this->bots[$data["player"]->iusername]->hibernate();
	}
	public function preFreeBot($data){
		if(!isset($this->bots[$data->iusername]))return $this->initBot($data);
		$bot=$this->bots[$data->iusername];
		$this->api->schedule(1, array($this, "freeBot"), $bot);
	}
	public function freeBot($bot){
		$bot->unhibernate();
		$bot->teleport($bot->p->entity);
	}
}

class Introducer{
	public $p;
	protected $distance=5, $settings; // settings
	protected $eid, $finalized=false, $hibernated=false; // self config
	const TYPE_INTRO=0;
	const TYPE_HELPER=1;
	const DEFAULT_DISTANCE=5;
	public function __construct(Player $target, array $opts=array(), $eid){
		$this->p=$target;
		$this->eid=$eid;
		if(!isset($opts["type"]))
			$opts["type"]=self::TYPE_INTRO;
		if(isset($opts["distance"])){
			$this->distance=$opts["distance"];
			unset($opts["distance"]);
		}
		else $this->distance=self::DEFAULT_DISTANCE;
		$this->settings=$opts;
		$this->envUpdate();
		$this->init();
	}
	protected function init(){
		console("[DEBUG] Initializing introducer of $this.", true, true, 2);
		$xd=/*cos(deg2rad(floatval($this->p->entity->yaw)))*/1*$this->distance;
		$zd=/*sin(deg2rad(floatval($this->p->entity->pitch)))*/0*$this->distance;
		$pk=new AddPlayerPacket;
		$pk->clientId=0;
		$this->username=IntroducerPlugin::$config->get("Bot name");
		$pk->username=$this->username;
		$pk->eid=$this->eid;
		$pk->x=$this->p->entity->x+$xd;
		$pk->y=$this->p->entity->y;
		$pk->z=$this->p->entity->z+$zd;
		$pk->yaw=180+$this->p->yaw;
		$pk->pitch=180+$this->p->pitch;
		$pk->unknown1=0;
		$pk->unknown2=0;
		$pk->metadata=array(
				0=>array("type"=>0, "value"=>0b00),
				1=>array("type"=>1, "value"=>300),
				16=>array("type"=>0, "value"=>0),
				17=>array("type"=>6, "value"=>array(0, 0, 0))
		);
		$this->p->dataPacket($pk);
		console("[DEBUG] Packet AddPlayerPacket of (x ".$pk->x." y ".$pk->y." z ".$pk->z." yaw ".$pk->yaw." pitch ".$pk->pitch.") sent to $this.", true, true, 2);
		$pk=new SetEntityMotionPacket;
		$pk->eid=$this->eid;
		$pk->speedX=$this->p->entity->speedX;
		$pk->speedY=$this->p->entity->speedY;
		$pk->speedZ=$this->p->entity->speedZ;
		$this->p->dataPacket($pk);
		console("[DEBUG] Packet SetEntityMotionPacket of (speedX ".$pk->speedX." speedY ".$pk->speedY." speedZ ".$pk->speedZ.") sent to $this.", true, true, 2);
		$pk=new PlayerEquipmentPacket;
		$pk->eid=$this->eid;
		$pk->item=PAPER;
		$pk->meta=0;
		$pk->slot=0;
		$this->p->dataPacket($pk);
		console("[DEBUG] Packet PlayerEquipmentPacket of (item ".$pk->item." sent to $this.", true, true, 2);
		$base=0x12a-0x100;
		$pk=new PlayerArmorEquipmentPacket;
		$pk->eid=$this->eid;
		$pk->slots=array(0=>$base, 1=>$base+1, 2=>$base+2, 3=>$base+3);
		$this->p->dataPacket($pk);
		console("[DEBUG] Packet PlayerArmorEquipmentPacket of (slots array:", false, true, 2);
		if(defined("DEBUG") and DEBUG>=2)
			var_dump($pk->slots);
		console(") sent to $this.", true, true, 2);
	}
	public function envUpdate(){
		if($this->hibernated===true)return;
		
	}
	public function teleport($pos){
		if($this->hibernated===true)return;
		$xd=/*cos(deg2rad(floatval($this->p->entity->yaw)))*/1*$this->distance;
		$zd=/*sin(deg2rad(floatval($this->p->entity->pitch)))*/0*$this->distance;
		$pk=new MovePlayerPacket;
		$pk->eid=$this->eid;
		$pk->x=$this->p->entity->x+$xd;
		$pk->y=$this->p->entity->y;
		$pk->z=$this->p->entity->z+$zd;
		$pk->bodyYaw=$this->p->entity->yaw;
		$pk->yaw=$this->p->entity->yaw;
		$pk->pitch=$this->p->entity->pitch;
		$this->p->dataPacket($pk);
		console("[DEBUG] Packet MovePlayerPacket of (x ".$pk->x." y ".$pk->y." z ".$pk->z." bodyYaw ".$pk->bodyYaw." pitch ".$pk->pitch." yaw ".$pk->yaw.") sent to $this.", true, true, 2);
	}
	public function hibernate(){
		$this->hibernated=true;
	}
	public function unhibernate(){
		$this->hibernated=false;
	}
	public function finalize(){
		$this->finalized=true;
	}
	public function tell($msg){
		$this->p->sendChat($msg);
	}
	public function __toString(){
		return $this->p->username;
	}
	public function __destruct(){
		if(!$this->finalized)
			$this->finalize();
	}
}
