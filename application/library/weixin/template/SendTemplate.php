<?php
namespace app\library\weixin\template;
class SendTemplate extends Template{
	
	public function getData(){
		if(!isset($this->params['order_sn']) || empty($this->params['order_sn'])){
			return ['errcode' => -102,'message' => '没有订单号'];
		}
		
		$order_sn = $this->params['order_sn'];
		
		if(!isset($this->params['express_name'])){
			return ['errcode' => -100 , 'message' => '需传入快递名'];
		}
		
		if(!isset($this->params['express_sn'])){
			return ['errcode' => -100 , 'message' => '需传入快递单号'];
		}
		
		if(!isset($this->params['order'])){
			$order = M("order")
			->alias("o")
			->join("order_goods og","og.order_id = o.id")
			->join("goods g","g.goods_id  = og.goods_id")
			->join("order_address oa","oa.id = o.address_id")
			->where(['o.order_sn' => $order_sn ])
			->field("concat(oa.name,' ',oa.province,oa.city,oa.district, oa.address) as address, o.order_sn, GROUP_CONCAT(g.name) as goods_name ")
			->group("o.id")
			->find();
			if(empty($order)){
				return ['errcode' => -101 , 'message' => '找不到对应订单'];
			}
			$this->params['order'] = $order;
		}
		
		$order = $this->params['order'];
		
		$this->url = "http://" . get_domain() . "/wap/order/order_detail?order_sn=" . $order_sn;
		
		return ['errcode' => 0,'message' => 'af', 
				'content' => ['first' => '您的订单已经发货',
				'keyword1' => [ "value" => $order['goods_name'], 'color' => "#173177"],
				'keyword2' => [ "value" => $this->params['express_name'], 'color' => "#173177"],
				'keyword3' => [ "value" => $this->params['express_sn'], 'color' => "#173177"],
				'keyword4' => [ "value" => $order['address'], 'color' => "#173177"],
				'remark' => [ "value" => '请耐心等待', 'color' => "#173177"]]
		];
	}
	
	public function get_template(){
		return "send";
	}
}