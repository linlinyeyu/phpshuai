<?php
namespace app\library\order;
class AccountOrderHelper extends OrderHelper{
	public $pay_id = 1;
	
	public $pay_name = "余额支付";
	
	public function pay_params($customer_id){
		$this->account_pay($customer_id);
	}
}