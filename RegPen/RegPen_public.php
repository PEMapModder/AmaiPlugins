<?php

/*
__PocketMine Plugin__
class=RegPenPublic
name=RegPen - A plugin for preparing player infractions for sharing
author=PEMapModder
apiversion=12,13
version=beta 0
*/

class RegPenPublic implements Plugin{
	const FILE_SPLITER="\n~~~~~~~~~~~~~~~~\n";
	public function __construct(ServerAPI$a, $s=0){}
	public function __destruct(){}
	public function init(){
		$this->dir=FILE_PATH."RegPen/";
		@mkdir($this->dir);
		$this->opmeDir=FILE_PATH."Op applications/";
		@mkdir($this->opmeDir);
		$ban=$this->getBanPath();
		if(!file_exists($ban))
			file_put_contents($ban, "");
		$banip=$this->getBanIpPath();
		if(!file_exists($banip))
			file_put_contents($banip, "");
		$c=ServerAPI::request()->api->console;
		$c->register("regpen", "<target> [count=0] <reason> [-i|--incognito]", array($this, "cmd"));
		$c->register("infract", "<target> <message> [-h|--hide] [-i|--incognito]", array($this, "cmd"));
		$c->register("opme", "<reasons ...> Submit an application form of op/staff to the owner. (Only owner can read it, don't worry.)", array($this, "opMeCmd"));
		ServerAPI::request()->addHandler("player.connect", array($this, "onConnect"));
	}
	public function opMeCmd($c, $reasons, $issuer){
		$dateStr=date("M j, Y H:i:s");
		if(is_string($issuer))
			return FORMAT_LIGHT_PURPLE."Hey, you don't need to apply for op!";
		$spl=self::FILE_SPLITER;
		$draft="Op application of $issuer, generated with /opme from plugin [RegPen by PEMapModder]:$spl".
				"Application first submitted at $dateStr\nThe following are records of the reusage of $issuer on this command.\n$spl".
				"Reasons given:\n";
		$path=$this->getOpAppPath($issuer);
		if(file_exists($path))
			$draft=file_get_contents($path);
		$draft=explode($spl, $draft);
		$reason=implode(" ", $reasons);
		#record the time
		$cnt=count(explode("\n", $draft[1]))-1;
		$draft[1].="Submission #$cnt submitted by $issuer when he has been online for ".($seconds=$this->updateSession("$issuer"))." seconds.";
		$draft[2].="At submission #$cnt, $issuer, who has been online for $seconds, claims, \"$reason\"\n";
		file_put_contents($path, implode($spl, $draft));
	}
	public function cmd($action, $args, $issuer){
		$server=ServerAPI::request();
		$api=$server->api;
		$serverName=$api->getProperty("server-name");
		$dateStr=date("M j, Y H:i:s");
		if($issuer=="console")
			$issuer="the owner";
		$p=array_shift($args);
		if(!(($target=$api->player->get($p)) instanceof Player))
			return "Player $p not found.";
		$content=@file_get_contents($this->getPath($target));
		if($content===false){
			$ip=$target->ip;
			$split=self::FILE_SPLITER;
			$content="---RegPen log---$split".#0
				"IP:$ip".$split.#1
				"Names:\n$split".#2
				"RegPens:\n$split".#3
				"Infractions:\n$split";#4
		}
		$content=explode(self::FILE_SPLITER, $content);
		if(strpos("\n".$target->username."\n", $content[2]) === false)
			$content[2] .= $target->username;
		if($action==="regpen"){
			// RegPen
			#support multiple issues
			$cnt=1;
			if(is_numeric($args[0]))
				$cnt=(int) array_shift($args);
			#must supply reason
			if(count($args)==0)
				return "Please give a reason.";
			#write down the main line
			$content[3].="by $issuer on $dateStr at $serverName:".implode(" ", $args)."\n";
			if($cnt>1){
				for($multiples=2; $multiples<=$times; $i++)
					$content[3].="by $issuer on $dateStr at $serverName: Multiple issue (#$multiples) of RegPen, total $cnt, same reason as above";
			}
			#count how many issues of RegPen received accumulatively
			$total=count(explode("\n", $content[3]))-2;
			#allow incognito issue of RegPen (only incognito to the target)
			if(in_array("-i", $args) or in_array("--incognito", $args))
				$issuer="incognito staff";
			#schedule kicking player
			$server->schedule(120, array($target, "close"), "Kicked for RegPen issued by $issuer");
			#count 
			if($total==4)
				file_put_contents($this->getBanPath(), $target->iusername.PHP_EOL, FILE_APPEND);
			elseif($total>=5)
				file_put_contents($this->getBanIpPath(), $target->ip.PHP_EOL, FILE_APPEND);
			$target->sendChat("You are given $cnt issue(s) of RegPen from $issuer.\n--------\nYou totally received $total issue(s) of RegPen.\n--------\nYou are being kicked in 6 seconds.\n--------\nReason: ".implode(" ", $args));
			file_put_contents($this->getPath($target), implode(self::FILE_SPLITER, $content));
			return "$cnt issue(s) of RegPen issue to $target";
		}
		else{
			$content[4].="by $issuer on $dateStr at $serverName: ".implode(" ", $args)."\n";
			if(in_array("-i", $args) or in_array("--incognito", $args))
				$issuer="incognito staff";
			if(!in_array("-h", $args) and !in_array("--hide", $args))
				$target->sendChat("You are given an infraction from $issuer about the following:\n--------\n".implode(" ", $args));
		}
		file_put_contents($this->getPath($target), implode(self::FILE_SPLITER, $content));
	}
	public function onConnect($p){
		if(in_array($p->iusername, explode(PHP_EOL, file_get_contents($this->getBanPath()))) or # ign ban
				in_array($p->ip, explode(PHP_EOL, file_get_contents($this->getBanIpPath())))){ # ip ban
			console("$p or his ip address is banned in the RegPen community.");
			return false;
		}
	}
	protected function getOpAppPath($n){
		return $this->opmeDir . strtolower("$n") . ".txt";
	}
	protected function getPath(Player $p){
		return FILE_PATH."RegPen/".$p->ip.".txt";
	}
	protected function getBanPath(){
		return FILE_PATH."RegPen/ban-list.txt";
	}
	protected function getBanIpPath(){
		return FILE_PATH."RegPen/ban-ip-list.txt";
	}
}

////git////
class RegPenGit{
	public static function dir(){
		return substr(FILE_PATH, 3)."RegPen"; // windows only
	}
	public static function drive(){
		return substr(FILE_PATH, 0, 2);
	}
	public static function toDir(){
		exec(self::drive());
		exec("cd ".self::dir());
	}
	public static function addAndCommit($msg="RegPen update"){
		self::toDir();
		exec("git add -A");
		exec("git commit -m $msg");
	}
}
