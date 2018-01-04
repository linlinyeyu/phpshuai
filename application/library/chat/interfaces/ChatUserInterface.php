<?php 

namespace app\library\chat\interfaces;
interface ChatUserInterface {
	
	public function createMember($member);
	
	
	/**
	 * 添加好友关系
	 *
	 * @param $member_id
	 * @param $friend_id
	 * */
	public function addFriends($member_id , $friend_id);
	
	
	/**
	 * 删除好友关系
	 * 
	 * @param $member_id 
	 * @param $friend_id
	 * */
	public function deleteFriend($member_id, $friend_id);
	
	
	/**
	 * 加入黑名单
	 *
	 * @param $member_id
	 * @param $block_id
	 * */
	public function blockMember($member_id, $block_id);
}
