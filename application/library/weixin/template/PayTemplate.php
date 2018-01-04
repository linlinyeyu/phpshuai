<?php
namespace app\library\weixin\template;
class PayTemplate extends Template{
	
	public function getData(){
		if(!isset($this->params['order_sns']) && !is_array($this->params['order_sns'])){
			return ['errcode' => -101 , 'message' => '没有订单号'];
		}
		
		$order_sns = $this->params['order_sns'];
		
		$amount = M("order")->where(['order_sn' => ['in', $order_sns]])
		->sum("order_amount");
		
		$this->url = "http://" . get_domain() . "/wap/order/my_order";
		return ['errcode' => 0,'message' => 'af', 
				'content' => ['first' => ['value' =>'订单支付成功'],
				'keyword1' => [ "value" => join(",", $order_sns), 'color' => "#173177"],
				'keyword2' => [ "value" => "支付成功", 'color' => "#173177"],
				'keyword3' => [ "value" => date("Y-m-d H:i:s"), 'color' => "#173177"],
				'keyword4' => [ "value" => "零美云合", 'color' => "#173177"],
				'keyword5' => [ "value" => $amount . "元", 'color' => "#173177"],
				'remark' => [ "value" => isset($this->params['content']) ? $this->params['content'] : '感谢您的惠顾']]
		];
	}
	
	public function get_template(){
		return "pay";
	}
}