<?php
namespace app\library\chat\hx;
use app\library\chat\interfaces\ChatClientInterface;
class EasemobChatClient implements ChatClientInterface {
	
	private static $group;
	
	private static $chat;
	
	private static $user;
	
	public function groupManager(){
		if(self::$group == null){
			self::$group = new EasemobChatGroup();
		}
		return self::$group;
	}
	
	public function chatManager(){
		if(self::$chat == null){
			self::$chat = new EasemobChat();
		}
		return self::$chat;
	}
	
	public function userManager(){
		if(self::$user == null){
			self::$user = new EasemobChatUser();
		}
		return self::$user;
	}
}