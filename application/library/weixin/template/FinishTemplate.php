<?php
namespace app\library\weixin\template;
class FinishTemplate extends Template{
	
	public function getData(){
		
		if(!isset($this->params['order_sn']) && !is_array($this->params['order_sn'])){
			return ['errcode' => -101 , 'message' => '没有订单号'];
		}
		
		$order_sn = $this->params['order_sn'];
		
		$order = [];
		
		if(!isset($this->params['order'])){
			$order = M("order")->alias("o")
			->join("order_goods og","og.order_id = o.id")
			->join("goods g","g.goods_id = og.goods_id")
			->group("o.id")
			->field("o.order_sn,GROUP_CONCAT(g.name) as goods_name,o.date_add , o.date_received, o.date_send")
			->where(['o.order_sn' => $order_sn])
			->find();
		}else{
			$order = $this->params['order'];
		}
		
		$this->url = "http://" . get_domain() . "/wap/order/order_detail?order_sn=" . $order_sn;
		return ['errcode' => 0,'message' => 'af', 
				'content' => ['first' => '订单收货通知',
				'keyword1' => [ "value" => $order_sn, 'color' => "#173177"],
				'keyword2' => [ "value" => $order['goods_name'], 'color' => "#173177"],
				'keyword3' => [ "value" => date("Y-m-d H:i", $order['date_add']), 'color' => "#173177"],
				'keyword4' => [ "value" => date("Y-m-d H:i", $order['date_send']), 'color' => "#173177"],
				'keyword5' => [ "value" => date("Y-m-d H:i", $order['date_received']), 'color' => "#173177"],
				'remark' => [ "value" => '感谢您的支持与厚爱']]
		];
	}
	
	public function get_template(){
		return "finish";
	}
}