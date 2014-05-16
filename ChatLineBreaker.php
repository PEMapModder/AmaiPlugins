<?php

/*
__PocketMine Plugin__
class=pemapmodder\clb\ChatLineBreaker
name=ChatLineBreaker
author=PEMapModder
version=0.0
apiversion=12
*/

namespace pemapmodder\clb;

class ChatLineBreaker implements \Plugin{
public $api;
public function __construct(\ServerAPI $api, $s = null){
$this->api = $api;
}
public function init(){
\DataPacketSendEvent::register(array($this, "onSend"), \EventPriority::LOW);
}
public function onSend(\DataPacketSendEvent $evt){
if(!(($pk = $evt->getPacket()) instanceof \MessagePacket)){
return;
}
$pk->message = $this->processMessage($pk->getPlayer()->data->get("lastID"), $pk->message); // thanks for making it private property! 😍
}
public function __destruct(){
}
}
