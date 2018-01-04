<?php
namespace app\library\chat\factory;
use app\library\chat\hx\EasemobChatClient;
class ChatClient{
	
	private static $map = [];
	
	public static function getInstance($type = 1){
		if(!isset($map[$type])){
			$client = null;
			switch ($type){
				case 1:
					$client = new EasemobChatClient();
					break;
				default:
					$client = new EasemobChatClient();
					break;
			}
			$map[$type] = $client;
		}
		
		return $map[$type];
	}
}