<?php
/*
__PocketMine Plugin__
author=PEMapModder
name=DST_API
description=DynamicSignTechnology API
apiversion=10,11
version=0.0.0.0
class=DSTAPI
*/
/*
@Copyright 2014 PEMapModder
@author PEMapModder
Do not redistribute this plugin or any modifications of it.
However, you can make plugins that call function in this plugin by:
    ServerAPI::request()->api->plugin->get("DST_API")->registerDS(...)
*/
/*
ChangeLog
Edition	|	Version	|	Build	|	Testant	|	Description
0(alpha)|	0		|	0		|	0		|	Initial commit: only functions
						1		|	0		|	Changed to an API-like thing
*/
class DSTAPI implements Plugin{
	////API////
	private $api,$b,$c,$p,$t,$pn,$s;
	////DB////
	private $cfg;
	private $db;
	////RAM////
	private $signsUpdate=array();
/*	public $dummyDsFullChange=array(
		"pos"=>new Position(0,0,0,LevelAPI::get("world")),
		"status"=>0,
		"partialLines"=>false,
		"texts"=>array(
			array(
				"line0",
				"line1",
				"line2",
				"line3"
			),
			array(
				"line0",
				"line1",
				"line2",
				"line3"
			)
		)
	);
	public $dummyDsPartialChange=array(
		"pos"=>new Position(1,1,1,LevelAPI::get("world")),
		"status"=>0,
		"partialLines"=>true,
		"texts"=>array(
			array(
				"line0",
				"line1",
				"line2",
				"line3"
			),
			array("newLine0","newLine1")
		)
	);
*/
	public function __construct(ServerAPI $a,$s=0){//**PMStuff
		$this->api=$a;
		$this->b=$a->block;
		$this->c=$a->console;
		$this->p=$a->player;
		$this->t=$a->tile;
		$this->pn=$a->plugin;
	}
	public function init(){//**PMStuff
		$this->s=ServerAPI::request();
		$this->initCmds();
		$this->initEvts();
		$this->s->schedule(20,array($this,"secondTick"));
		$this->cfg=new Config($this->pn->configPath($this)."config.yml",CONFIG_YAML,array(
			
		));
		$this->s->addHandler("dst.isloaded",array($this,"iAmHere"));
		$this->db=new Config($this->pn->configPath($this)."database.yml",CONFIG_YAML,array(
			"DSs"=>array()
		));
		$dss=$this->db->get("DSs");
		if($dss!==array())$this->importDSs($dss);
		$this->s->dhandle("dst.inited",$this);
	}
	public function iAmHere(){
		return "loaded";
	}
	public function __destruct(){//**PMStuff
		$this->save();
	}
	public function initCmds(){//**PMStuff
		
	}
	public function initEvts(){//**PMStuff
		$this->s->addHandler("player.block.touch",array($this,"evH"));
		$this->s->addHandler("dst.signdestroy",array($this,"evH"),1);
	}
	public function secondTick(){//**PMStuff
		$this->updateSigns();
	}
	public function importDss($dss){//**I/O utils
		foreach($dss as $ds){
			$p=$ds["pos"];
			$o=$ds;
			$o["pos"]=new Position($p[0],$p[1],$p[2],$p[3]);
			$this->signsUpdate[]=$o;
		}
	}
	public function save(){//**I/O utils
		$out=array();
		foreach($this->signsUpdate as $ds){
			$arr=$ds;
			$arr["pos"]=array($ds["pos"]->x,$ds["pos"]->y,$ds["pos"]->z,$ds["pos"]->level);
			$out[]=$arr;
		}
		$this->db->set("DSs",$out);
	}
	public function updateSigns(){//**DST
		foreach($this->signsUpdate as $ds){
			if(!$ds["partialLines"]){
				$ds["status"]++;
				$l=$ds["texts"][$ds["status"]];
				$this->t->get($ds["pos"])->setText($l[0],$l[1],$l[2],$l[3]);
				continue;
			}
			$ds["status"]++;
			$l=$ds["texts"][0];
			foreach($ds["texts"][1] as $line=>$text){
				$l[($ds["status"]+$line)%4]=$text;
			}
			$this->t->get($ds["pos"])->setText($l[0],$l[1],$l[2],$l[3]);
		}
	}
	public function registerDS(Position $pos,$extras,$isPartial=false){//**DST
		$b=$pos->level->getBlock($pos);
		if(!($b instanceof SignBlock))return "INVALID_PARAMS:arg0 is not sign";//dont blame me when you provide invalid params
		if($isPartial===false and count($extras)!==4)
			return "INVALID_PARAMS:arg2 does not match arg1";
		if(count($extras)>4)
			return "INVALID_PARAMS:arg1 too large";
		if(!isset($this->t->get($pos)->data["Text1"]))return "$pos not a texts-defined tile";
		$texts=array($this->t->get($pos)->getText());
		foreach($extras as $extra){
			$texts[]=$extra;
		}
		$this->signsUpdate[]=array("pos"=>$pos,"status"=>0,"partialLines"=>$isPartial,"texts"=>$texts);
		return true;
	}
	public function evH($d,$e){//
		switch($e){
			case "player.block.touch":
				if($d["type"]==="break" and ($d["target"] instanceof SignPostBlock)){
					foreach($this->signsUpdate as $key=>$ds){
						if($ds["pos"]->x===$d["target"]->x and
								$ds["pos"]->y===$d["target"]->y and
								$ds["pos"]->z===$d["target"]->z and
								$ds["pos"]->level===$d["target"]->level){
							return $this->onDestroySign($ds["pos"],$d["player"]);
						}
					}
				}
			break;
			case "dst.signdestroy":
				$this->checkPower($d["player"]);
			break;
		}
	}
	public function onDestroySign(Position $pos,Player $player){//**DST
		$handled=$this->s->handle("dst.signdestroy",array("pos"=>$pos,"player"=>$player));
		if($handled!==true)return $handled;
		
	}
	public function checkPower(Position $pos,Player $player){
		if($this->checkPP()===false)return in_array($player->username, explode("\n", file_get_contents(FILE_PATH."ops.txt")));
		return $this->s->dhandle("get.player.permission",$player->username);
	}
	public function checkPP(){
		foreach($this->pn->getList() as $p){
			if($p["name"]==="PermissionsPlus" and $p["author"]==="Omattyao")return true;
		}
		return false;
	}
}
