<?php
namespace app\library\weixin\template;
class RegisterTemplate extends Template{
	
	public function getData(){
		$this->url = "http://" . get_domain() . "/wap/user/goal_customer";
		if(!isset($this->params['customer_id'])){
			return ['errcode' => -101, 'message' => '没有对应的下级用户'];
		}
		
		$customer = M("customer")->where(['customer_id' => $this->params['customer_id']])->field("nickname,date_add")->find();
		
		if(empty($customer)){
			return ['errcode' => -101, 'message' => '没有对应的下级用户'];
		}
		
		return ['errcode' => 0,'message' => 'af', 
				'content' => ['first' => '会员注册通知',
				'keyword1' => [ "value" => $customer['nickname'], 'color' => "#173177"],
				'keyword2' => [ "value" => date("Y年m月d日 H:i", $customer['date_add']), 'color' => "#173177"],
				'remark' => [ "value" => $customer['nickname'] . "通过扫描您的二维码关注了零美云合公众号。"]]
		];
	}
	
	public function get_template(){
		return "register";
	}
}