<?php

/*
__PocketMine Plugin__
class=TheEndPlugin
name=The End
author=PEMapModder
version=alpha 0.0
apiversion=12
*/

define("ENDSTONE_SUBST", SANDSTONE);
//enderman AS zombie WHERE eid=32
//enderdragon AS spider[] WHERE eid[]=33[]

class TheEndPlugin implements Plugin{
	private $api;
	//PocketMine stuff
	public function __construct(ServerAPI $a, $s=FALSE){
		$this->api = $api;
	}
	public function __destruct(){
		
	}
	public function init(){
		
	}
	//TODO terrain generator
	public function generateTheEnd(Level $level, $seed = FALSE){
		// generate empty world
		$name = $level->getName()."_ext_the_end";
		$lg = new SuperflatGenerator(array(
				"preset" => "2"
		));
		$wg = new WorldGenerator($lg, $name, $seed);
		$wg->generate();
		$wg->close();
		$this->api->level->loadLevel($name);
		$end = $this->api->level->get($name);
		// populate empty world
		$centre=$this->randPos(new Position(128, 48, 128, $end), new Vector3(64, 24, 64));
		$block=BlockAPI::get(ENDSTONE_SUBST);
		$radius=mt_rand(16, 24);
		$this->genHemiSph($centre, $block, $radius, 50);
		$this->terrainizeTheEnd($centre, $radius, $block);
		$this->populateTheEnd($centre, $radius);
		$this->initTheEndDb($end);
	}
	public function terrainizeTheEnd(Position $centre, $radius, Block $block){
		
	}
	public function populateTheEnd(Position $centre, $radius){
		
	}
	public function initTheEndDb(Level $level){
		$endMobs = new Config(FILE_PATH."worlds/".$level->getName()."endermobs.yml", CONFIG_YAML, array(
			"endermen" => array(),
			"enderdragon" => "empty"
		));
	}
	
	//WorldEdit
	public function randPos(Position $principal, Vector3 $maxVar){
		return new Position(
			$principal->x + mt_rand(-1 * abs($maxVar->x), abs($maxVar->x)),
			$principal->y + mt_rand(-1 * abs($maxVar->y), abs($maxVar->y)),
			$principal->z + mt_rand(-1 * abs($maxVar->z), abs($maxVar->z)),
			$principal->level);
	}
	public function genHemiSph(Position $centre, Block $material, $radius, $vertPerct){
		$startX = $centre->x - $radius;
		$startY = $centre->y - $radius;
		$startZ = $centre->z - $radius;
		$endX = $centre->x + $radius;
		$endZ = $centre->z + $radius;
		$endY = $startY + $radius * 2 * $vertPerct / 100;
		for($x=$startX; $x<=$endX; $x++){
			for($y=$startY; $y<=$endY; $y++){
				for($z=$startZ; $z<=$endZ; $z++){
					$pos = new Position($x, $y, $z, $centre->level);
					if($pos->distance($centre)<=$radius){
						$centre->level->setBlock($pos, $material);
					}
				}
			}
		}
		return true;
	}
	
	//TODO mobs
	public function initMobs(Level $level){
		$this->spawnEnderman($level, FALSE);
		$this->spawnDragon();
	}
	
	//TODO enderman
	public function spawnEndermen(Level $level, $param1=FALSE){
		if($param1 === FALSE){ // populate endermen
			
		}
		else{
			if($param1 instanceof Vector3){
				$spawnPos = $param1;
				$count = mt_rand(2, 4);
			}
			elseif(is_int($param1)){
				$count = $param1;
				//TODO $spawnPos
			}
		}
	}
	public function spawnOneEnderman(Position $pos){
		$enderman = $this->api->entity->add($pos->level, ENTITY_MOB, MOB_ZOMBIE);
		if(!file_exists(FILE_PATH."worlds/".$pos->level->getName()."endermobs.yml"))return false;
		$save = new Config(FILE_PATH."worlds/".$pos->level->getName()."endermobs.yml", CONFIG_YAML);
		$eid = $enderman->eid;
		$endermen = $save->get("endermen");
		$endermen[] = $eid;
	}
	public function updateAllEndermen(Level $level){
		
	}
	public function moveEnderman($eid, Vector3 $vectors){
		
	}
	
	//TODO enderdragon
	public function spawnEnderdragon(Level $level, $pos=FALSE){
		
	}
	public function updateDragon(Level $level){
		
	}
	public function moveDragon(Level $level, Vector3 $vectors){
		
	}
	
}
