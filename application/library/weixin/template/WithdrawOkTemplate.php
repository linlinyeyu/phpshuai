<?php
namespace app\library\weixin\template;
class WithdrawOkTemplate extends Template{
	
	public function getData(){
		$this->url = "http://" . get_domain() . "/wap/commission/exchange_record";
		
		return ['errcode' => 0,'message' => 'af', 
				'content' => ['first' => $this->params['title'],
				'money' => [ "value" => $this->params['money'], 'color' => "#173177"],
				'timet' => [ "value" => date("Y-m-d H:i:s", $this->params['date_add']), 'color' => "#173177"],
				'remark' => [ "value" => $this->params['content']]]
		];
	}
	
	public function get_template(){
		return "withdraw_ok";
	}
}