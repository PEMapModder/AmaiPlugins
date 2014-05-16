<?php

/*
__PocketMine Plugin__
class=pemapmodder\clb\ChatLineBreaker
name=ChatLineBreaker
author=PEMapModder
version=3
apiversion=12
*/

namespace pemapmodder\clb;

class ChatLineBreaker implements \Plugin{
	const MAGIC = "CLBDB==>";
	public $api;
	public $database = array();
	public $testing = array();
	public $config; // made them public to thank the public Player::$data :)
	public function __construct(\ServerAPI $api, $s = null){
		$this->api = $api;
	}
	public function init(){
		\console(FORMAT_LIGHT_PURPLE."Loading ChatLineBreaker", false);
		$time = microtime(true);
		\DataPacketSendEvent::register(array($this, "onSend"), \EventPriority::LOW);
		$this->api->addHandler("player.chat", array($this, "onChat"), 50);
		echo ".";
		$this->api->console->register("clb", "<cal|set|view|tog|help> Calibrate/set/view your CLB settings; Toggle using CLB", array($this, "onCmd"));
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
		echo ".";
		$this->load();
		$time *= -1;
		$time += microtime(true);
		$time *= 1000;
		echo " Done! ($time ms)".PHP_EOL;
	}
	public function onChat($data){
		$p = $data["player"];
		if(!in_array($p->CID, $this->testing)){
			return;
		}
		$msg = $data["message"];
		$cid = $p->data->get("lastID");
		if(!is_numeric($msg)){
			$p->sendChat("Please type in the length!");
			return false;
		}
		$l = (int) $msg;
		if($l <= 5){
			$issuer->sendChat("I don't believe you! I don't think your device can only show $l characters!\n");
			return false;
		}
		if($l >= 0b10000000){
			$issuer->sendChat("Our database does not support numbers larger than 127.");
			return false;
		}
		$this->setLength($cid, $l);
		$p->sendChat("Your CLB length is now $l.\n");
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
			return "Right-click the console or edit start.cmd to change your fonts, not here."; // lol
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
					$this->api->schedule(40 * ($key + 1), array($issuer, "sendChat"), $value, false, ""); // why did you add this 5th arg...
				}
				$this->testing[] = $issuer->CID;
				break;
			case "set":
				$l = (int) array_shift($args);
				if($l <= 5){
					$output .= "I don't believe you! I don't think your device can only show $l characters!\n";
					break;
				}
				if($l >= 0b10000000){
					$output .= "Our database does not support numbers larger than 127.";
					break;
				}
				$this->setLength($cid, $l);
				$output .= "Your CLB length is now $l.\n";
				break;
			case "check":
			case "view":
				$l = $this->getLength($cid);
				$b = $this->isEnabled($cid) ? "enabled":"disabled";
				$output .= "CLB is $b for you.\n";
				$output .= "Your CLB length is $l.\n";
				break;
			case "tog":
			case "toggle":
				$this->setEnabled($cid, ($b = !$this->isEnabled($cid)));
				$b = $b ? "enabled":"disabled";
				$output .= "CLB is now $b for you.\n";
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
	private function getLength($cid){
		if(isset($this->database[$cid])){
			return $this->database[$cid][1];
		}
		return $this->config->get("default-length");
	}
	private function setLength($cid, $length){
		$this->database[$cid] = array($this->isEnabled($cid), $length);
	}
	private function isEnabled($cid){
		if(isset($this->database[$cid])){
			return $this->database[$cid][0];
		}
		return $this->config->get("default-enable");
	}
	private function setEnabled($cid, $bool){
		$this->database[$cid] = array($bool, $this->getLength($cid));
	}
	private function getTesterMessage(){
		$numbers = "";
		for($i = 1; $i < 10; $i++){
			$numbers .= "$i";
		}
		for($i = 11; $i < 100; $i+= 2){
			$numbers .= "$i";
		}
		return array("First, please close your chat screen.", "Now look at the above message:", $numbers, "-------------------------\nWhat is the last number visible? It is your CLB length.", "Type your CLB length in chat directly.");
	}
	private function getData($cid){
		return $this->database[$cid];
	}
	private function processMessage($clientID, $message, \Player $p){
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
	private function save(){
		\console("[INFO] Saving CLB database...", true, true, 2);
		$time = microtime(true);
		$buffer = "";
		foreach($this->database as $cid=>$data){
			$buffer .= \Utils::writeLong($cid);
			$ascii = $data[1];
			if($data[0]){
				$ascii |= 0b10000000;
			}
			$buffer .= chr($ascii);
		}
		file_put_contents($this->path, $buffer, LOCK_EX);
		\console("Done!", true, true, 2);
	}
	private function load(){
		\console("[INFO] Loading CLB database...", true, true, 2);
		$time = 0 - microtime(true);
		$str = @file_get_contents($this->path);
		if($str === false){
			$this->save();
			return true;
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
		return false;
	}
	private function config(){
		$this->config = new \Config($this->cfgPath, $this->type, array(
			"default-enable" => true,
			"default-length" => 50,
		));
	}
	public function __destruct(){
		$this->save();
	}
}
