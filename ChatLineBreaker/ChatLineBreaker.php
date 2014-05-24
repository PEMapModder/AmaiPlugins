<?php

/*
__PocketMine Plugin__
class=pemapmodder\clb\ChatLineBreaker
name=ChatLineBreaker
author=PEMapModder
version=3.1
apiversion=12
*/

namespace pemapmodder\clb;

class ChatLineBreaker implements \Plugin{
	const MAGIC_PREFIX = "\x00\xffCLBDB>";
	const MAGIC_SUFFIX = "=CLBDB\xff\x00";
	const CORRUPTION_PREFIX = "prefix";
	const CORRUPTION_SUFFIX = "suffix";
	const CORRUPTION_API = "unsupported api";
	const CURRENT_VERSION = "\x01";
	const INITIAL_RELEASE = "";
	const DB_LANG_UPDATE = "\x01";
	public $api;
	public $database = array();
	public $testing = array();
	public $config; // made them public to thank the public Player::$data :)
	public function __construct(\ServerAPI $api, $s = null){
		$this->api = $api;
	}
	public function init(){
		\console(FORMAT_LIGHT_PURPLE."Loading ChatLineBreaker", false);
		$this->server = \ServerAPI::request();
		$time = microtime(true);
		$this->api->console->register("clb", "<cal|set|view|tog|help> Calibrate/set/view your CLB settings; Toggle using CLB", array($this, "onCmd"));
		\DataPacketSendEvent::register(array($this, "onSend"), \EventPriority::LOW);
		$this->api->addHandler("player.chat", array($this, "onChat"), 50);
		echo ".";
		$this->server->addHandler("clb.player.length.get", array($this, "getLength"), 50);
		$this->server->addHandler("clb.player.enable.get", array($this, "isEnabled"), 50);
		$this->server->addHandler("clb.data.get", array($this, "getData"), 50);
		$this->server->addHandler("clb.player.length.set", array($this, "eventSetLength"), 50);
		$this->server->addHandler("clb.player.enable.set", array($this, "eventSetEnabled"), 50);
		$this->server->addHandler("clb.db.reload", array($this, "load"), 50);
		$this->server->addHandler("clb.config.reload", array($this, "config"), 50);
		$this->server->addHandler("clb.db.save", array($this, "save"), 50);
		echo ".";
		$this->path = $this->api->plugin->configPath($this)."players.dat";
		$this->cfgPath = $this->api->plugin->configPath($this)."config.";
		if(is_file($this->cfgPath."json")){
			$this->cfgPath .= "json";
			$this->type = CONFIG_JSON;
			console("JSON", true, true, 2);
		}
		else{
			$this->cfgPath .= "yml";
			$this->type = CONFIG_YAML;
			console("YML", true, true, 2);
		}
		$this->config();
		$this->langPath = $this->api->plugin->configPath($this)."texts.lang";
		$this->lang = new Lang($this->langPath);
		echo ".";
		$this->load();
		$time *= -1;
		$time += microtime(true);
		$time *= 1000;
		echo " Done! ($time ms)".PHP_EOL;
	}
	public function eventSetLength($data){
		if(!isset($data["cid"]) or !isset($data["length"])){
			return false;
		}
		$this->setLength($data["cid"], $data["length"]);
		return true;
	}
	public function eventSetEnabled($data){
		if(!isset($data["cid"]) or !isset($data["bool"])){
			return false;
		}
		$this->setEnabled($data["cid"], $data["bool"]);
		return true;
	}
	public function onChat($data){
		$p = $data["player"];
		if(!in_array($p->CID, $this->testing)){
			return;
		}
		$msg = $data["message"];
		$cid = $p->data->get("lastID");
		if(!is_numeric($msg)){
			$p->sendChat($this->lang["calibrate.response.not.numeric"]);
			return false;
		}
		$l = (int) $msg;
		if($l <= 5){
			$issuer->sendChat(str_replace("@char", "$l", $this->lang["calibrate.response.too.low"]));
			return false;
		}
		if($l >= 0b10000000){
			$issuer->sendChat(str_replace("@char", "$l", $this->lang["calibrate.response.too.high"]));
			return false;
		}
		$this->setLength($cid, $l);
		$p->sendChat(str_replace("@char", "$l", $this->lang["calibrate.response.succeed"]));
		unset($this->testing[array_search($p->CID, $this->testing)]);
		return false;
	}
	public function onQuit($p){
		if(in_array($p->CID, $this->testing)){
			unset($this->testing[array_search($p->CID, $this->testing)]);
		}
	}
	public function onSend(\DataPacketSendEvent $evt){
		if(!(($pk = $evt->getPacket()) instanceof \MessagePacket)){
			return;
		}
		if($evt->getPacket()->source === "clb.followup.linebreak"){
			$evt->getPacket()->source = "";
			return;
		}
		$packets = $this->processMessage($evt->getPlayer()->data->get("lastID"), $pk->message, $evt->getPlayer()); // thanks for making it public property, shoghicp!
		if($packets === false)
			return;
		// I made it use client ID because the line break length should depend on the device not the player or IP
		$evt->setCancelled(true);
		foreach($packets as $pk){
			$evt->getPlayer()->dataPacket($pk);
		}
		if(defined("DEBUG") and DEBUG >= 2){
			// var_export($pk);
		}
	}
	public function onCmd($cmd, $args, $issuer){
		if($issuer === "console"){
			return $this->lang["cmd.console.reject"]; // lol
		}
		if($issuer === "rcon"){
			return "Did you expect we can modify your RCon client preferences for you? We are not hackers!"; // lol * 2
		}
		$cmd = array_shift($args);
		$output = "[CLB] ";
		$cid = $issuer->data->get("lastID");
		switch($cmd){
			case "cal":
			case "calibrate":
				$msgs = $this->getTesterMessage();
				$output .= array_shift($msgs);
				foreach($msgs as $key=>$value){
					$this->api->schedule(40 * ($key + 1), array($issuer, "sendChat"), $value, false, "ChatLineBreaker"); // why did you add this 5th arg...
				}
				$this->testing[] = $issuer->CID;
				break;
			case "set":
				$l = (int) array_shift($args);
				if($l <= 5){
					$output .= $this->lang["calibrate.response.not.numeric"]."\n";
					break;
				}
				if($l >= 0b10000000){
					$output .= str_replace("@char", "$l", $this->lang["calibrate.response.too.low"])."\n";
					break;
				}
				$this->setLength($cid, $l);
				$output .= "Your CLB length is now $l.\n";
				break;
			case "check":
			case "view":
				$l = $this->getLength($cid);
				$output .= $this->lang["view.".($this->isEnabled($cid) ? "on":"off")];
				$output .= str_replace("@length", "$l", $this->lang["view.length"]);
				break;
			case "tog":
			case "toggle":
				$this->setEnabled($cid, ($b = !$this->isEnabled($cid)));
				$output .= $this->lang["toggle.".($b ? "on":"off")];
				break;
			case "help":
				$output .= "Showing help for /clb\n";
			default:
				$output .= "\"/clb\" ChatLineBreaker (CLB) settings panel.\n";
				$output .= "CLB is a tool for breaking chat lines into pieces automatically to suit your device length.\n";
				$output .= "\"/clb cal\" or \"/clb calibrate\": Use the CLB linebreak tester to calibrate your CLB length.\n";
				$output .= "\"/clb set <length>\": (Not recommended) Set your CLB length to the defined length.\n";
				$output .= "\"/clb view\" or \"/clb check\" to check if CLB is enabled for you.\n";
				$output .= "\"/clb tog\" or \"/clb toggle\" to toggle your CLB.\n";
		}
		if(defined("DEBUG") and DEBUG >= 2){
			var_export($output);
		}
		return $output;
	}
	public function getLength($cid){
		if(isset($this->database[$cid])){
			return $this->database[$cid][1];
		}
		return $this->config->get("default-length");
	}
	public function setLength($cid, $length){
		$this->database[$cid] = array($this->isEnabled($cid), $length);
	}
	public function isEnabled($cid){
		if(isset($this->database[$cid])){
			return $this->database[$cid][0];
		}
		return $this->config->get("default-enable");
	}
	public function setEnabled($cid, $bool){
		$this->database[$cid] = array($bool, $this->getLength($cid));
	}
	public function getTesterMessage(){
		$numbers = "";
		for($i = 1; $i < 10; $i++){
			$numbers .= "$i";
		}
		for($i = 11; $i < 100; $i+= 3){
			$numbers .= "$i,";
		}
		return array($this->lang["calibrate.instruction.close.screen"],
			$this->lang["calibrate.instruction.next.message"],
			$numbers,
			$this->lang["calibrate.instruction.hyphens.separator"],
			$this->lang["calibrate.instruction.ask.number"],
			$this->lang["calibrate.instruction.require.type.chat"]);
	}
	public function getData($cid){
		return $this->database[$cid];
	}
	public function processMessage($clientID, $message, \Player $p){
		if(!$this->isEnabled($clientID)){
			return false;
		}
		$wrapped = explode("\n", wordwrap($message, $this->getLength($clientID), "\n"));
		if(count($wrapped) === 1){
			return false;
		}
		$packets = array();
		foreach($wrapped as $wrap){
			$pk = new \MessagePacket;
			$pk->source = "clb.followup.linebreak";
			$pk->message = $wrap;
			$packets[] = $pk;
		}
		return $packets;
	}
	public function save(){
		\console("[INFO] Saving CLB database...", true, true, 2);
		$time = microtime(true);
		$buffer = self::MAGIC_PREFIX;
		$buffer .= self::CURRENT_VERSION;
		foreach($this->database as $cid=>$data){
			$buffer .= \Utils::writeLong($cid);
			$ascii = $data[1];
			if($data[0]){
				$ascii |= 0b10000000;
			}
			$buffer .= chr($ascii);
		}
		$buffer .= self::MAGIC_SUFFIX;
		file_put_contents($this->path, $buffer, LOCK_EX);
		\console("Done!", true, true, 2);
	}
	public function load(){
		\console("[INFO] Loading CLB database...", true, true, 2);
		$time = 0 - microtime(true);
		$str = @file_get_contents($this->path);
		if($str === false){
			$this->save();
			return true;
		}
		$isOld = (strlen($str) % 9) === 0;
		if(!$isOld){
			if(substr($str, 0, strlen(self::MAGIC_PREFIX)) !== self::MAGIC_PREFIX){
				if($this->api->dhandle("clb.db.corrupt", self::CORRUPTION_PREFIX) !== false){
					$this->database = array();
					$this->save();
					trigger_error("CLB database corrupted. Component corrupted: ".self::CORRUPTION_PREFIX, E_USER_ERROR);
				}
			}
			if(substr($str, -1 * strlen(self::MAGIC_SUFFIX)) !== self::MAGIC_SUFFIX){
				if($this->api->dhandle("clb.db.corrupt", self::CORRUPTION_SUFFIX) !== false){
					$this->database = array();
					$this->save();
					trigger_error("CLB database corrupted. Component corrupted: ".self::CORRUPTION_SUFFIX, E_USER_ERROR);
				}
			}
			$str = substr($str, strlen(self::MAGIC_PREFIX), -1 * strlen(self::MAGIC_SUFFIX));
			$api = substr($str, 0, 1);
			if($api > self::CURRENT_VERSION){
				if($this->api->dhandle("clb.db.corrupt", self::CORRUPTION_API) !== false){
					$this->database = array();
					$this->save();
					trigger_error("CLB database corrupted. Component corrupted: ".self::CORRUPTION_API, E_USER_ERROR);
				}
			}
			$str = substr($str, 1);
		}
		for($i = 0; $i < strlen($str); $i+= 9){
			$cur = substr($str, $i, 9);
			$key = \Utils::readLong(substr($cur, 0, 8));
			$number = ord(substr($str, 8));
			$bool = ($number & 0b10000000) !== 0;
			$length = $number & 0b01111111;
			$this->database[$key] = array($bool, $length);
		}
		$time += microtime(true);
		$time *= 1000;
		\console("Done! ($time ms)", true, true, 2);
		if(defined("DEBUG") and DEBUG >= 2){
			var_export($this->database);
		}
		return false;
	}
	public function config(){
		$this->config = new \Config($this->cfgPath, $this->type, array(
			"default-enable" => true,
			"default-length" => 50,
		));
	}
	public function __destruct(){
		$this->save();
	}
}

class Lang implements \ArrayAccess{
	public $data = array();
	public function __construct($path){
		$this->path = $path;
		$this->default = [
			"calibrate.instruction.close.screen" => "First, please close your chat screen.",
			"calibrate.instruction.next.message" => "Now look at the above message:",
			"calibrate.instruction.hyphens.separator" => "-------------------------",
			"calibrate.instruction.ask.number" => "What is the last number visible? It is your CLB length.",
			"calibrate.instruction.require.type.chat" => "Type your CLB length in chat directly.",
			"calibrate.response.not.numeric" => "Please type in the length!",
			"calibrate.response.too.low" => "I don't believe you! I don't think your device can only show @char characters!",
			"calibrate.response.too.high" => "Sorry, our database does not support numbers larger than 127. I don't believe you have such a mega machine though to show @char characters.",
			"calibrate.response.succeed" => "Your CLB length is now @char.",
			"view.on" => "CLB is enabled for you",
			"view.off" => "CLB is disabled for you",
			"view.length" => "Your CLB length is @length",
			"toggle.on" => "CLB is now enabled for you",
			"toggle.off" => "CLB is now disabled for you",
			"cmd.console.reject" => "Right-click the console or edit start.cmd to change your fonts, not here.",
			
		];
		if(is_file($this->path)){
			$this->data = $this->default;
			$this->load();
		}
		else{
			$this->data = $this->default;
			$this->save();
		}
	}
	public function offsetGet($k){
		if(isset($this->data[$k])){
			return $this->data[$k];
		}
		return $this->data[$k];
	}
	public function offsetSet($k, $v){
		$this->data[$k] = $v;
	}
	public function offsetUnset($k){
		unset($this->data[$k]);
	}
	public function offsetExists($k){
		return isset($this->data[$k]) or isset($this->default[$k]);
	}
	public function save(){
		$output = "";
		foreach($this->data as $key=>$value){
			$k = strpos($key, "=") === false ? $key : (strpos($key, "'=") === false ? "'$key'" : "\"$key\"");
			$output .= "$k=$value";
			$output .= PHP_EOL;
		}
		file_put_contents($this->path, $output);
	}
	public function load(){
		foreach(explode(PHP_EOL, file_get_contents($this->path)) as $key=>$line){
			if(substr($line, 0, 1) === "#" or $line === ""){
				continue;
			}
			if(strpos($line, "=") === false){
				$this->error($key);
				continue;
			}
			if(strpos($line, "\"") === 0){
				$length = strpos($line, "\"", 1);
				if(substr($line, $length + 1, 1) !== "="){
					$this->error($key);
					continue;
				}
				$this->data[strstr(substr($line, 1), "\"=", true)] = substr($line, $length + 2);
				continue;
			}
			if(strpos($line, "'") === 0){
				$length = strpos($line, "'", 1);
				if(substr($line, $length + 1, 1) !== "="){
					$this->error($key);
					continue;
				}
				$this->data[strstr(substr($line, 1), "'=", true)] = substr($line, $length + 2);
				continue;
			}
			$this->data[strstr($line, "=", true)] = substr(strstr($line, "="), 1);
		}
	}
	protected function error($line){
		trigger_error("Syntax error on line $line at {$this->path} lang file", E_USER_WARNING);
	}
}
