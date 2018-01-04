<?php
namespace app\library\handler;

class YuebaoChargeHandler extends Handler{
	public function orderNotify($order){
		//增加奖励金
		M("customer")->where(['customer_id' => $order['customer_id']])->save(['reward_amount' => ['exp','reward_amount +'.$order['order_amount']]]);
		//记录操作
		M("finance")->add(array('customer_id' => $order['customer_id'],'finance_type_id' => 3,'type' => 6,'amount' => $order['order_amount'],'order_sn' => $order['order_sn'],'date_add' => time(),'comments' => "余额宝充值 订单号".$order['order_sn'],'title' => "+".$order['order_amount'],'is_minus' => 1));

		return ['errcode' => 0,'message' => '操作成功'];
	}
}