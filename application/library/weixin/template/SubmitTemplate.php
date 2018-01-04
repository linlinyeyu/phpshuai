<?php
namespace app\library\weixin\template;
class SubmitTemplate extends Template{
	
	public function getData(){
		if(!isset($this->params['order_sn']) || empty($this->params['order_sn'])){
			return ['errcode' => -102,'message' => '没有订单号'];
		}
		
		$order_sn = $this->params['order_sn'];
		
		$this->url = "http://" . get_domain() . "/wap/order/my_order";
		
		
		
		return ['errcode' => 0,'message' => 'af', 
				'content' => ['first' => '订单提交成功',
				'keyword1' => [ "value" => '零美云合', 'color' => "#173177"],
				'keyword2' => [ "value" => date('Y-m-d H:i:s'), 'color' => "#173177"],
				'keyword3' => [ "value" => $this->params['goods_name'], 'color' => "#173177"],
				'keyword4' => [ "value" => $this->params['order_amount']. '元', 'color' => "#173177"],
				'remark' => [ "value" => $this->params['content'], 'color' => "#173177"]]
		];
	}
	
	public function get_template(){
		return "submit";
	}
}