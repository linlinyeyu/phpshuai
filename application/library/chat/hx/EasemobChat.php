<?php
namespace app\library\chat\hx;
use app\library\chat\interfaces\ChatInterface;

use app\library\chat\message\ChatMessage;

class EasemobChat implements ChatInterface{
	
	public function sendMessage($chatmessage){
		
		$to = $chatmessage->getTo();
		if(empty($to)){
			return ['errcode' => -101 , 'message' =>'发送目标为空'];
		}
		
		$params = [];
		
		$target_type = "users" ;
		$type = $chatmessage->getTargetType();
		switch ($type){
			case ChatMessage::SINGLE:
				$target_type = "users";
				break;
			case ChatMessage::GROUP:
				$target_type = "chatgroups";
				break;
			case ChatMessage::CHATROOM:
				$target_type = "chatrooms";
				break;
		}
		
		$params['target_type'] = $target_type;
		$params['msg'] = $chatmessage->getMobParams();
		$params['target'] = $to;
		
		$from = $chatmessage->getFrom();
		if(!empty($from)){
			$params['from'] = $from;
		}
		$extra = $chatmessage->getExtras();
		if(!empty($extra)){
			$params['ext'] = $extra;
		}
		return EasemobHttpUtil::getInstance()->sendHttpRequest(EndPoints::MESSAGES_URL, $params, "POST");
	}
}