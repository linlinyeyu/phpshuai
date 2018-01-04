<?php
namespace app\admin\model;
use think\Model;
class ItemsOrder extends Model{
	
	public function GetOrderNumber(){
		$order_number = "";
       	$count = 0;
       	do{
       		$order_number = date("YmdHis")."".rand(10000,99999);
       		$count = $this->where(array("order_number" => $order_number))->count();
       	}while($count > 0);
       	return $order_number;
	}

	public function GetInitOrder($customer_id, $sum, $type,$time,$ip, $coupon_id = null ,$cart_ids = []){
		
		$res = array(
			'message' => '',
			'errcode' => 0);
		$order_number = $this->GetOrderNumber();

       // $time = microtime_float();
        
		//$ip = get_client_ip();
		$order = array(
        		'order_number' => $order_number,
        		'customer_id' => $customer_id,
        		'order_amount' => $sum,
        		'goods_amount' => $sum,
        		'coupon_amount' => 0,
        		'date_add' => $time,
                'number' => get_microtime_time(),
        		'date_upd' => $time,
        		'ip' => $ip,
                'ip_area' => get_location($ip),
        		'state' => 0,
        		'is_pay' => 0,
                'cart_ids' => join(",", $cart_ids)
        	);

		switch ($type) {
        	case 0:
        		if($res['errcode'] == 0){
        			$order['cash_amount'] = $sum;
        			$order['pay_name'] = "余额支付";
        			$order['pay_id'] = 0;
        			$order['date_pay'] = microtime_float();
        		}else{
        			return $res;
        		}
        		break;
        	default:
        		$res['message'] = '请传入支付方式';
        		$res['errcode'] = -106;
        		return $res;
        		break;
        }


        return $order;

	}
}