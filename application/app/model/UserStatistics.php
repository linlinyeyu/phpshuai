<?php
namespace app\app\model;
use think\Model;
class UserStatistics extends Model{
/**
 * 登录记录
 * @param unknown $customer_id
 * @param unknown $oauth_type
 * @param unknown $islogin
 */
	public function record($customer_id,$oauth_type, $islogin){
		$terminal = I("terminal");
		if(!isset($terminal)){
				$terminal = 2;
		}
		$statistics = array(
				'customer_id' => $customer_id,
				'oauth_type' => $oauth_type,
				'islogin' => $islogin,
				'date_add' => time(),
				'ip' => get_client_ip(),
				'terminal' => $terminal
			);
		$this->add($statistics);
	}

}