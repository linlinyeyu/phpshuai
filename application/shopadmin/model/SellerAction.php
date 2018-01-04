<?php 
namespace app\shopadmin\model;

use think\Model;

class SellerAction extends Model
{	

	public function saveAction($id , $comment){
		$data = [];
		$data['id'] = $id;
		$user = M("seller_user")->where(['id' => $id])->getField("username");
		if(empty($user)){
			$user = "未知";
		}
		$data['seller_id'] = session("sellerid");
		$data['user_name'] = $user;
		$data['controller'] = CONTROLLER_NAME;
		$data['action'] = ACTION_NAME;
		$data['date_add'] = time();
		$data['comment'] = date('Y-m-d H:i:s') . "  " . $data['user_name'] . ":" .$comment;
		$data['ip'] = \app\library\IpHelper::get_client_ip();
		$this->add($data);
	}	

}