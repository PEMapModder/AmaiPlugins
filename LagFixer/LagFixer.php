<?php

/*
__PocketMine Plugin__
class=LagFixer
name=LagFixer
author=PEMapModder
version=alpha 0
apiversion=12
*/

class LagFixer implements Plugin{
	public function __construct(ServerAPI $api, $s=0){
		$this->api = $api;
	}
	public function init(){
	}
	public function __destruct(){
	}
}
