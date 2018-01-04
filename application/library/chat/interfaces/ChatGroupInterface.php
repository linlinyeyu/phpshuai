<?php 
namespace app\library\chat\interfaces;
interface ChatGroupInterface {

	public function getAllGroups();

	/**
	 * 获取传入的id的群组详细信息，若找不到对应群组则返回null
	 * */
	public function getOneGroup($group_id);

	/**
	 * 获取传入的id的群组详细信息，若为找不到对应群组，则相应位置返回null
	 * */
	public function getGroupDetails($group_ids);

	/**
	 * 创建群，传入一个群组实体，若有额外的参数限制，将此限制放入extra参数内
	 * */
	public function createGroup($group);

	/**
	 * 编辑群，传入一个群组实体，若有额外的参数限制，将此限制放入extra参数内
	 * */
	public function editGroup($group_id,$group);

	/**
	 * 删除群
	 * */
	public function deleteGroup($group_id);

	/**
	 * 获取群内所有用户
	 * */
	public function getAllUsersFromGroup($group_id);

	/**
	 * 为群添加一个用户
	 * */
	public function addOneUserToGroup($user_id, $group_id);

	/**
	 * 为群添加多个用户
	 * */
	public function addUsersToGroup($user_ids, $group_id);

	/**
	 * 为群移除一个用户
	 * */
	public function removeOneUserFromGroup($user_id, $group_id);

	/**
	 * 为群移除多个用户
	 * */
	public function removeUsersFromGroup($user_ids , $group_id);

	/**
	 * 获取用户所在的群组
	 * */
	public function getUserGroups($user_id);

	/**
	 * 更换群组拥有者(更改后加入原拥有者为普通成员)
	 * */
	public function transferOwner($group);

	/**
	 * 更换群组拥有者(更改后删除原拥有者)
	 * 
	 * @param group
	 * @return
	 */
	public function transferOwnerDeleteOwner($group);

}
