<?php
namespace app\library\handler;
class RechargeHandler extends Handler{
	
	public function orderNotify($order){

        // 资金明细
        M('finance')->add([
            'customer_id' => $order['customer_id'],
            'finance_type_id' => 3,
            'type' => 3,
            'amount' => $order['order_amount'],
            'date_add' => time(),
            'is_minus' => 2,
            'order_sn' => $order['order_sn'],
            'comments' => '充值 '.$order['order_sn'],
            'title' => '+'.$order['order_amount'],
        ]);

		return [
				'errcode' => 0, 
				'message' => 'success', 
				'content' => [
						'customer' => ['account' => ['exp', 'account + '. $order['order_amount']]]
				]];
	}
}