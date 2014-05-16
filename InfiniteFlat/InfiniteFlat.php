<?php

/*
__PocketMine Plugin__
class=InfiniteFlat
name=InfiniteFlat
author=PEMapModder
version=alpha 0.0.0
apiversion=13
*/

class InfiniteFlat implements Plugin{
	private static $inst;
	public static function request(){
		return self::$inst;
	}
	private static $runtime=false;
	public static function get(){
		return self::$runtime;
	}
	public function __construct(ServerAPI $a, $s=0){
		$this->api=$a;
		$this->server=ServerAPI::request();
		self::$inst=$this;
	}
	public function __destruct(){
	}
	public function init(){
		InfiniteFlatSingleGenerator::initialize();
		$this->dir=$this->api->plugin->configPath($this);
		$noExt=$this->dir."settings.";
		$ext="yml";
		if(file_exists($noExt."txt"))
			$ext="txt";
		if(defined("CONFIG_YAML"))eval("\$yml=CONFIG_YAML;");
		else eval("\$yml=Config::YAML;");
		$c=new Config($noExt.$ext, $yml, self::$defaultSettings);
		self::$runtime=new InfiniteFlatRuntime(array($c->get("World generation efficiency"), $c->get("Overlapping chunks"), $c->get("Number of worlds preloaded for buffer for players near world margin"), $c->get("presets")));
		
	}
	protected static $defaultSettings=array(
			"World generation efficiency"=>true,
			"Overlapping chunks"=>2,
			"Number of worlds preloaded for buffer for players near world margin"=>1,
			"default preset" => 0,
	);
}
class InfiniteFlatRuntime extends Config{
	public function __construct(Array $settings){
		if(defined("CONFIG_YAML"))
			eval("\$yml=CONFIG_YAML;");
		else eval("\$yml=Config::YAML");
		parent::__construct(ServerAPI::request()->api->plugin->configPath(InfiniteFlat::request())."runtime record - DO NOT EDIT", $yml);
		$this->settings=$settings;
	}
}
class InfiniteFlatLevel extends Config{
	public function __construct(String $name, Array $settings){
		if(defined("CONFIG_YAML"))
			eval("\$yml=CONFIG_YAML;");
		else eval("\$yml=Config::YAML");
		parent::__construct(FILE_PATH."infinite worlds/$name.yml", $yml, array("gencfg"=>$settings));
	}
	public function init(){
		$level=null;
		$this->worlds[0][0]=$level;
	}
}
class InfiniteFlatSingleGenerator implements LevelGenerator{
	private $options;
	private $layers=array();
	public function __construct(array $options=array()){
		$random=array_rand(self::getConfig()->get("presets"));
		$this->options=isset($options["preset"])?$options["preset"]:$random;
		$layers=$options["layers"];
		foreach($layers as $layer){
			$id=$layer[0];
			$yTotal=$layer[1];
			for($y=0; $y<$yTotal; $y++){
				$this->layers[]=$id;
			}
		}
	}
	public function init(Level $level, Random $random){
		$this->l=$level;
		$this->random=$random;
	}
	public function getSettings(){
		return $this->options;
	}
	public function getSpawn(){
		return new Vector3(128, 128, 128);
	}
	public function generateChunk($cx, $cz){
		for($my=0; $my<8; $my++){
			$this->chunks[$my]="";
			for($x=0; $x<16; $x++){
				for($z=0; $z<16; $z++){
				$blocks="";
				$metas="";
				for($y=0; $y<16; $y++){}
					
				}
				$this->chunks[$my].=$blocks.hex2bin($metas)."\x00\x00\x00\x00\x00\x00\x00\x00";
			}
		}
		$this->l->setMiniChunk();
	}
	public function populateChunk($cx, $cz){
	}
	public static $config=false, $initialized=false;
	public static function initialize(){
		self::$config=new Config(ServerAPI::request()->api->plugin->configPath(InfiniteFlat::request())."flat generaetor settings.yml", CONFIG_YAML, self::$defaultConfig);
	}
	public static $defaultConfig=array(
			"help"=>array(
					"presets"=>array("Follow the following example",
							array("preset name"=>array(
									"layers"=>array(
											array("ID", "number of layers"),
									),
									"populators"=>array(
											array("generation method (see below)", "extra data", "random count minimum", "random count maximum"),
									),
							)),
							"generation methods listed below",
							array(
									"method name"=>array("explanation", array("extra data")),
									"pillar"=>array("create random pillars on the topmost layer", array("Block ID", "height")),
									"tree"=>array("grow a tree on the topmost layer", array("tree type, 0-3")),
									"area"=>array("change part of the ground into this shape", array("Block ID", "random depth minimum", "random depth maximum", "shape - circle, square", "random side-length/radius minimum", "random size maximum")),
							),
					),
			),
			"presets"=>array(
					"forest"=>array(
							"layers"=>array(
									array(BEDROCK, 1),
									array(STONE, 59),
									array(DIRT, 3),
									array(GRASS, 1),
							),
							"populators"=>array(
									array("tree", array(0), 150, 190),
									array("tree", array(2), 80, 120),
									array("area", array(STILL_WATER, 2, 4, "circle", 4, 7), 6, 10),
							),
					),
					"desert"=>array(
							"layers"=>array(
									array(BEDROCK, 1),
									array(STONE, 58),
									array(SANDSTONE, 2),
									array(SAND, 3),
							),
							"populators"=>array(
									array("pillar", array(CACTUS, 2), 90, 130),
									array("area", array(STILL_WATER, 2, 4, "circle", 4, 7), 6, 10),
							),
					),
			)
	);
	public static function getConfig(){
		if(!self::$initialized)
			self::initialize();
		return self::$config;
	}
}

/*
x x x x i i i i i i i i x x x x
x x x x i i i i i i i i x x x x
x x x x i i i i i i i i x x x x
x x x x i i i i i i i i x x x x
i i i i e e e e e e e e i i i i
i i i i e e e e e e e e i i i i
i i i i e e e e e e e e i i i i
i i i i e e e e e e e e i i i i
i i i i e e e e e e e e i i i i
i i i i e e e e e e e e i i i i
i i i i e e e e e e e e i i i i
i i i i e e e e e e e e i i i i
x x x x i i i i i i i i x x x x
x x x x i i i i i i i i x x x x
x x x x i i i i i i i i x x x x
x x x x i i i i i i i i x x x x
*/
