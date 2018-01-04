<?php
namespace app\library\weixin\template;
class Refund3Template extends Template{
	
	public function getData(){
		
		if(!isset($this->params['order_sn']) && !is_array($this->params['order_sn'])){
			return ['errcode' => -101 , 'message' => '没有订单号'];
		}
		
		$order_sn = $this->params['order_sn'];
		
		$order = [];
		if(!isset($this->params['order'])){
			$order = M("order")
			->alias("o")
			->where(['order_sn' => $order_sn])
			->join("order_goods og","og.order_id = o.id")
			->join("goods g","g.goods_id = og.goods_id")
			->join("order_return orr","orr.order_id = o.id")
			->field("GROUP_CONCAT(g.name) as goods_name , orr.price,o.order_sn")
			->group("o.id")
			->find();
			if(empty($order)){
				return ['errcode' => -107 , 'message' => '找不到相关订单'];
			}
		}else{
			$order = $this->params['order'];
		}
		
		$this->url = "http://" . get_domain() . "/wap/order/order_detail?order_sn=" . $order_sn;
		return ['errcode' => 0,'message' => 'af', 
				'content' => ['first' => ['value' => $this->params['title'] , 'color' => "#173177"],
				'orderProductPrice' => [ "value" => $order['price'], 'color' => "#173177"],
				'orderProductName' => [ "value" => $order['goods_name'], 'color' => "#173177"],
				'orderName' => [ "value" => $order_sn, 'color' => "#173177"],
				'remark' => [ "value" => $this->params['content']]]
		];
	}
	
	public function get_template(){
		return "refund3";
	}
}