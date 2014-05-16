<?php
/*
__PocketMine Plugin__
author=PEMaapModder
name=DST_SignSigner
version=alpha 0.0.0.0
class=DSTSignSignerLoadable
apiversion=11
*/
class DSTSignSignerPlugin implements Plugin{
	private $dst,$pn,$s,$c,$ct,$p;
	private $queue;
	public function __construcct(ServerAPI $api,$server=false){
		$this->pn=$api->plugin;
		$this->c=$api->console;
		$this->ct=$api->chat;
		$this->t=$api->tile
		$this->p=$api->player;
	}
	public function init(){
		if(ServerAPI::request()->handle("dst.isloaded")!=="loaded")
			$this->pn->load("https://github.com/pemapmodder/DST/raw/master/_DST_API.php");
		ServerAPI::request()->addHandler("dst.inited",array($this,"yourInitFunction"));
	}
	public function yourInitFunction(Plugin $dst){
		$this->dst=$dst;
		$this->s=ServerAPI::request();
		$this->s->addHandler("tile.update",array($this,"evRed"));
		$this->s->addHandler("dst.signdestroy",array($this,"evRed"));
		$this->s->addHandler("player.block.touch",array($this,"evRed"),6);
		$this->s->addHandler("player.join",array($this,"evRed"));
		$this->s->api->console->register("sss","signs a sign",array($this,"queueSign"));
	}
	public function evRed($d,$e){
		switch($e){
			case "tile.update":
				if(isset($d->class) and $d->class===TILE_SIGN)
					$this->onSignUpdate($d,new Position($d->x,$d->y,$d->z,$d->level),array($d->data["Text1"],$d->data["Text2"],$d->data["Text3"],$d->data["Text4"]);
				break;
			case "dst.signdestroy":
				$this->onDstDestroyed($data["pos"],$data["player"]);
				break;
			case "player.block.touch":
				if($d["type"]==="place")
					$this->useItem(($t=$d["target"])->x,$t->y,$t->z,$d["item"],$t,$d["player"]);
				break;
			case "player.join":
				$this->queue[$d->username]=false;
				break;
		}
	}
	private function onSignUpdate(Tile $tile, Position $pos,$lines){
		
	}
	private function onDstDestroyed(){
		
	}
	private function useItem($x,$y,$z,$i,$b,$p){
		if($b instanceof SignBlock){
			if($this->queue[$p->username]!==false){
				$this->dst->registerDS($b,array("Signed by:","$p"),true);
				$this->queue[$p->username]=false;
			}
		}
	}
	public function queueSign($c,$a,$s){
		if($this->queue[$s->username]===true)
			return "You have already used this command.";
		$this->queue[$s->username]=true;
	}
	public function __destruct(){
		
	}
}
