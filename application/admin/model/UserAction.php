<?php 
namespace app\admin\model;

use think\Model;

class UserAction extends Model
{	

	public function saveAction($user_id , $comment){
		$data = [];
		$data['user_id'] = $user_id;
		$user = M("user")->where(['id' => $user_id])->getField("username");
		if(empty($user)){
			$user = "未知";
		}
		$data['user_name'] = $user;
		$data['controller'] = CONTROLLER_NAME;
		$data['action'] = ACTION_NAME;
		$data['date_add'] = time();
		$data['comment'] = date('Y-m-d H:i:s') . "  " . $data['user_name'] . ":" .$comment;
		$data['ip'] = \app\library\IpHelper::get_client_ip();
		$this->add($data);
	}	

}