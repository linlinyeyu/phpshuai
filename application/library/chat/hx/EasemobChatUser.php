<?php
namespace app\library\chat\hx;
use app\library\chat\interfaces\ChatUserInterface;

class EasemobChatUser implements ChatUserInterface{
	public function createMember($member){
		if(!isset($member['username']) || empty($member['username'])){
			return ['errcode' => -101 , 'message' =>'请传入用户名'];
		}
		
		if(!isset($member['password']) || empty($member['password'])){
			return ['errcode' => -102 , 'message' => '请传入用户密码'];
		}
		$response = EasemobHttpUtil::getInstance()->sendHttpRequest(EndPoints::USERS_URL, $member, "POST");
		
		if(!isset($response['entities'])){
			if(isset($response['error'])){
				return ['errcode' => -101 , 'message' => $response['error']];
			}
			return ['errcode' => -101 , 'message' => '请求环信失败'];
		}
		
		$content = [];
		foreach ($response['entities'] as $user){
			$content[$member['username']] = $user['uuid'];
		}
		
		return ['errcode' => 0 ,'message' => '请求成功', 'content' => $content];
	}
	
	public function addFriends($member_id , $friend_id){
		$url = EndPoints::USERS_URL . "/" . $member_id . "/contacts/users/" . $friend_id ;
		$response = EasemobHttpUtil::getInstance()->sendHttpRequest($url, [], "POST");
		
		if(!isset($response['entities'])){
			if(isset($response['error'])){
				return ['errcode' => -101 , 'message' => $response['error']];
			}
			return ['errcode' => -101 , 'message' => '请求环信失败'];
		}
		$this->deleteBlockMember($member_id, $friend_id);
		$this->deleteBlockMember($friend_id, $member_id);
		return ['errcode' => 0 ,'message' => '请求成功'];
	}
	
	public function deleteFriend($member_id , $friend_id){
		$url = EndPoints::USERS_URL . "/" . $member_id . "/contacts/users/" . $friend_id ;
		$response = EasemobHttpUtil::getInstance()->sendHttpRequest($url, [], "DELETE");
		
		if(!isset($response['entities'])){
			if(isset($response['error'])){
				return ['errcode' => -101 , 'message' => $response['error']];
			}
			return ['errcode' => -101 , 'message' => '请求环信失败'];
		}
		
		$this->blockMember($member_id, $friend_id);
		$this->blockMember($friend_id, $member_id);
		return ['errcode' => 0 ,'message' => '请求成功'];
		
	}
	
	public function blockMember($member_id, $block_id){
		if(!is_array($block_id)){
			$block_id = [$block_id];
		}
		$url = EndPoints::USERS_URL . "/" . $member_id . "/blocks/users"  ;
		$data = ['usernames' => $block_id];
		$response = EasemobHttpUtil::getInstance()->sendHttpRequest($url, $data, "POST");
		 
		if(!isset($response['entities'])){
		 	if(isset($response['error'])){
		 		return ['errcode' => -101 , 'message' => $response['error']];
		 	}
		 	return ['errcode' => -101 , 'message' => '请求环信失败'];
		 }
		 return ['errcode' => 0 ,'message' => '请求成功'];
	}
	
	public function deleteBlockMember($member_id, $block_id){
		$url = EndPoints::USERS_URL . "/" . $member_id . "/blocks/users/" . $block_id  ;
		
		$response = EasemobHttpUtil::getInstance()->sendHttpRequest($url, [], "DELETE");
		
		if(!isset($response['entities'])){
			if(isset($response['error'])){
				return ['errcode' => -101 , 'message' => $response['error']];
			}
			return ['errcode' => -101 , 'message' => '请求环信失败'];
		}
		return ['errcode' => 0 ,'message' => '请求成功'];
	}
	
}