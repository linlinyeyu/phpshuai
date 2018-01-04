<?php
namespace app\app\model;
use think\Model;
class UserStatistics extends Model{

	public function record($customer_id,$oauth_type, $islogin){
		$statistics = array(
				'customer_id' => $customer_id,
				'oauth_type' => $oauth_type,
				'islogin' => $islogin,
				'date_add' => date("Y-m-d"),
				'ip' => get_client_ip()
			);
		$this->add($statistics);
	}

}