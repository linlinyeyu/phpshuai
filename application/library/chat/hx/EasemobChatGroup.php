<?php
namespace app\library\chat\hx;
use app\library\chat\interfaces\ChatGroupInterface;

class EasemobChatGroup implements ChatGroupInterface{
	
	public function getAllGroups(){
		$response = EasemobHttpUtil::getInstance()->sendHttpRequest(EndPoints::CHATGROUPS_URL, [], "GET");
		
		if(isset($response['data']) ){
			$content = [];
			foreach ($response['data'] as $group){
				$index = strpos($group['owner'], "_");
				
				$owner = $group['owner'];
				
				if($index !== false){
					$owner = substr($owner, $index + 1);
				}
				
				$content[] = [
						'groupid' => $group['groupid'],
						'groupname' => $group['groupname'],
						'owner' =>  $owner,
						'user_count' => $group['affiliations']
				];
			}
			return ['errcode' => 0 ,'message' => '请求成功', 'content' => $content];
		}else{
			if(isset($response['error'])){
				return ['errcode' => -101 , 'message' => $response['error']];
			}
		}
		return ['errcode' => -101 ,'message' => '请求环信失败'];
	}
	
	/**
	 * 获取传入的id的群组详细信息，若找不到对应群组则返回null
	* */
	public function getOneGroup($group_id){
		
	}
	
	/**
	 * 获取传入的id的群组详细信息，若为找不到对应群组，则相应位置返回null
	* */
	public function getGroupDetails($group_ids){
		
		
	}
	
	/**
	 * 创建群，传入一个群组实体，若有额外的参数限制，将此限制放入extra参数内
	* */
	public function createGroup($group){
		$data = [];
		if(!isset($group['groupname'])){
			return ['errcode' => -101 ,'message' => '请输入名字'];
		}
		
		if(!isset($group['desc'])){
			return ['errcode' => -102 ,'message' => '请输入描述信息'];
		}
		
		if(!isset($group['public'])){
			$group['public'] = true;
		}
		
		if(!isset($group['maxusers'])){
			$group['maxusers'] = 300;
		}
		
		if(!isset($group['owner'])){
			return ['errcode' => -103 ,'message' => '请输入群组拥有者'];
		}
		
		if(!isset($group['approval'])){
			$group['approval'] = false;
		}
		
		$data = [
				'groupname' => $group['groupname'],
				'des' => $group['desc'],
				'public' => $group['public'],
				'maxusers' => $group['maxusers'],
				'approval' => $group['approval'],
				'owner' => $group['owner'],
		];
		
		if(isset($group['members']) && !empty($group['members'])){
			if(is_array($group['members'])){
				$data['members'] = $group['members'];
			}else if(is_string($group['members'])){
				$data['members'] = [$group['members']];
			}
		}
		
		$response = EasemobHttpUtil::getInstance()->sendHttpRequest(EndPoints::CHATGROUPS_URL, $data, "POST");
		
		if(isset($response['data']) && isset($response['data']['groupid'])){
			return ['errcode' => 0 ,'message' => '请求成功' ,'content' => $response['data']['groupid']];
		}else{
			if(isset($response['error'])){
				return ['errcode' => -101 , 'message' => $response['error']];
			}
		}
		return ['errcode' => -101 ,'message' => '请求环信失败'];
		
	}
	
	/**
	 * 编辑群，传入一个群组实体，若有额外的参数限制，将此限制放入extra参数内
	* */
	public function editGroup($group_id,$group){
		if(empty($group_id)){
			return ['errcode' => -101 , 'message' => '请传入group_id'];
		}
		
		$data = [];
		
		if(isset($group['groupname'])){
			$data['groupname'] = str_replace( " ", "+", $group['groupname']) ;
		}
		
		if(isset($group['desc'])){
			$data['description'] = str_replace( " ", "+", $group['desc']) ;
		}
		
		if(isset($group['maxusers'])){
			$data['maxusers'] = $group['maxusers'] ;
		}
		
		if(empty($data)){
			return ['errcode' => -102, 'message' => '请传入信息'];
		}
		
		$url = EndPoints::CHATGROUPS_URL . "/" . $group_id;
		$response = EasemobHttpUtil::getInstance()->sendHttpRequest($url, $data, "PUT");
		
		
		if(isset($response['data']) ){
			return ['errcode' => 0 ,'message' => '请求成功' ];
		}else{
			if(isset($response['error'])){
				return ['errcode' => -101 , 'message' => $response['error']];
			}
		}
		
		return ['errcode' => -101 ,'message' => '请求环信失败'];
		
	}
	
	/**
	 * 删除群
	* */
	public function deleteGroup($group_id){
		if(empty($group_id)){
			return ['errcode' => -102 ,'message' => '请传入群组id'];
		}
		
		$url = EndPoints::CHATGROUPS_URL . "/" . $group_id ;
		
		$response = EasemobHttpUtil::getInstance()->sendHttpRequest($url, null, "DELETE");
		if(isset($response['data']) && isset($response['data']['result']) && $response['data']['result'] == true){
			return ['errcode' => 0 ,'message' => '请求成功' ];
		}else{
			if(isset($response['error'])){
				return ['errcode' => -101 , 'message' => $response['error']];
			}
		}
		return ['errcode' => -101 ,'message' => '请求环信失败'];
	}
	
	/**
	 * 获取群内所有用户
	* */
	public function getAllUsersFromGroup($group_id){
		
	}
	
	/**
	 * 为群添加一个用户
	* */
	public function addOneUserToGroup($user_id, $group_id){
		if(empty($user_id)){
			return ['errcode' => -101 ,'message' => '请传入用户id'];
		}
		
		if(empty($group_id)){
			return ['errcode' => -102 ,'message' => '请传入群组id'];
		}
		
		$url = EndPoints::CHATGROUPS_URL . "/" . $group_id . "/users/" . $user_id;
		
		$response = EasemobHttpUtil::getInstance()->sendHttpRequest($url, null, "POST");
		if(isset($response['data']) && isset($response['data']['result']) && $response['data']['result'] == true){
			return ['errcode' => 0 ,'message' => '请求成功' ];
		}else{
			if(isset($response['error'])){
				return ['errcode' => -101 , 'message' => $response['error']];
			}
		}
		return ['errcode' => -101 ,'message' => '请求环信失败'];
		
	}
	
	/**
	 * 为群添加多个用户
	* */
	public function addUsersToGroup($user_ids, $group_id){
		
	}
	
	/**
	 * 为群移除一个用户
	* */
	public function removeOneUserFromGroup($user_id, $group_id){
		if(empty($user_id)){
			return ['errcode' => -101 ,'message' => '请传入用户id'];
		}
		
		if(empty($group_id)){
			return ['errcode' => -102 ,'message' => '请传入群组id'];
		}
		
		$url = EndPoints::CHATGROUPS_URL . "/" . $group_id . "/users/" . $user_id;
		
		$response = EasemobHttpUtil::getInstance()->sendHttpRequest($url, null, "DELETE");
		if(isset($response['data']) && isset($response['data']['result']) && $response['data']['result'] == true){
			return ['errcode' => 0 ,'message' => '请求成功' ];
		}else{
			if(isset($response['error'])){
				return ['errcode' => -101 , 'message' => $response['error']];
			}
		}
		return ['errcode' => -101 ,'message' => '请求环信失败'];
	}
	
	/**
	 * 为群移除多个用户
	* */
	public function removeUsersFromGroup($user_ids , $group_id){
		
	}
	
	/**
	 * 获取用户所在的群组
	* */
	public function getUserGroups($user_id){
		
	}
	
	/**
	 * 更换群组拥有者(更改后加入原拥有者为普通成员)
	* */
	public function transferOwner($group){
		
	}
	
	/**
	 * 更换群组拥有者(更改后删除原拥有者)
	 *
	 * @param group
	 * @return
	*/
	public function transferOwnerDeleteOwner($group){
		
	}
}