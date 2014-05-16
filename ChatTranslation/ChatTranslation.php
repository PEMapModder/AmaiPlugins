<?php

/*
__PocketMine Plugin__
class=ChatTranslation
name=ChatTranslation
author=PEMapModder
version=alpha 0
apiversion=12
*/

class ChatTranslation implements Plugin{
	public $tran = array();
	public function __construct(ServerAPI $api, $s = 0){}
	public function __destruct(){}
	public function init(){
		$s = ServerAPI::request();
		$s->api->console->register("lang", "[language name abbr] Select your language", array($this, "onCmd"));
		DataPacketSendEvent::register(array($this, "onPkSent"));
		$this->dir = $s->api->configPath($this)."players/";
		@mkdir($this->dir);
		$this->tdir = dirname($this->dir)."/translations/";
		foreach(scandir($this->tdir) as $file){
			if(preg_match("#tran\\-[a-zA-Z_]{2,}\\.yml\$#i", $file) and is_file($this->tdir.$file) and strtolower($file) !== "tran-demo.yml){
				$this->tran[strtolower(substr($file, 5, -4))] = $s->api->plugin->readYAML($this->tdir.$file);
			}
		}
		if(!is_file($this->logPath = dirname($this->dir)."/logs.log"))
			file_put_contents($this->logPath, "# ChatTranslation warnings/errors logs".PHP_EOL);
	}
	public function onCmd($c, $arg, $isr){
		if(!isset($arg[0])){
			$out = "Available languages: ".implode(", ", array_merge(array("en"), array_keys($this->tran)))."\nYour language priority list:\n";
			foreach($this->getLangPriorityList($isr) as $i=>$l)
				$out .= ($i + 1).": $l\n";
			return $out;
		}
		$lang = array_shift($arg);
		$priority = isset($arg[0]) ? ((int) array_shift($arg)) - 1 : 0;
		$list = $this->getLangPriorityList($isr);
		if(!in_array(array_keys($this->tran)))
			return "Language doesn't exist!";
		if(in_array(strtolower($lang), $list))
			return "$lang is already in your language list.";
		$array = array_merge(array_slice($list, 0, $priority), array(strtolower($lang)), array_slice($list, $priority));
		$this->setLangPriorityList($isr, $list);
		return "Your language list has been updated: $l inserted after item #$. Use /$c to check.";
	}
	public function onPkSent(DataPacketSendEvent $evt){
		if(!(($pk = $evt->getPacket()) instanceof MessagePacket)){
			return;
		}
		$msg = $pk->message;
		$p = $evt->getPlayer();
		$this->translate($msg, $p);
	}
	public function getLangPriorityList($p){
		$path = $this->getPath($p);
		$yaml = @file_get_contents($path);
		$list = $yaml === false ? array(0 => "en") : yaml_parse($yaml);
		return $list;
	}
	public function setLangPriorityList($p, $list){
		file_put_contents($this->getPath($p), yaml_emit($list);
	}
	public function getPath($p){
		return $this->dir."$p.yml";
	}
	public function translate($msg, $p){
		foreach($this->getLangPriorityList($p) as $lang){
			$regex = "#";
			foreach(explode(" ", $msg) as $word){
				$psbls = preg_quote( /*str_replace(
					array("\\", "(", ")", "[", "]", "-", ".", "?", "\$", "+", "*", "^", "|"),
					array("\\\\", "\\(", "\\)", "\\[", "\\]", "\\-", "\\.", "\\?", "\\\$", "\\+", "\\*", "\\^", "\\|"),*/ $word);
				foreach($this->allOnline() as $n){
					$psbls = str_replace($n, "($n|@randplayer[0-9])", $psbls);
				}
				if(strpos($word, $p->username) !== false)
					$psbls = str_replace($p->username, "($p|@targplayer)", $psbls);
				$regex .= "$psbls ";
			}
			$regex = substr($regex, 0, -1)."#i";
			$results = preg_grep($regex, array_keys($this->tran[$lang]));
			if(count($results) > 1)
				file_put_contents($this->logPath, "Warning at ".date(DATE_ATOM).": Multiple results (".implode(", ", $results)." from RegExp $regex for message $msg". PHP_EOL, FILE_APPEND);
		}
	}
	public function allOnline(){
		$list = array();
		foreach(ServerAPI::request()->clients as $p){
			$list[] = $p->username;
		}
		return $list;
	}
}
