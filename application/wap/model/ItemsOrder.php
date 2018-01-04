<?php
namespace app\app\model;
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

	public function GetInitOrder($customer_id, $sum, $type, $coupon_id = null ,$cart_ids = []){
		
		$res = array(
			'message' => '',
			'errcode' => 0);
		$order_number = $this->GetOrderNumber();

        $time = microtime_float();
        
		$ip = get_client_ip();
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
		//优惠券判断

        if($coupon_id > 0){
            $condition = array(
                    'customer_id'=> $customer_id,
                    'customer_coupon_id' => $coupon_id,
                    'state' => 0,
                    'date_end' => array('gt',time()),
                    'coupon_limit' => array('lt', $sum)
                );
            $counpon = M("customer_coupon")
            ->field("date_end,coupon_limit,coupon_money,state")
            ->where($condition)
            ->find();
            if(!empty($coupon)){
                $sum = $sum - $coupon['coupon_money'];
                $order['coupon_id'] = $coupon_id;
                $order['order_amount'] = $sum;
                $order['coupon_amount'] = $coupon['coupon_money'];
            }
            M("customer_coupon")
            ->where(array("customer_coupon_id" => $coupon_id))
            ->save(array("state"=>1));
        }
		//返回参数
		$return = array(
        		'type' => $type,
        		'order_number' => $order_number
        	);
		switch ($type) {
        	case 0:
        		$res = D("customer")->CalculateMoney($customer_id, -1 * $sum);
        		if($res['errcode'] == 0){
        			$order['cash_amount'] = $sum;
        			$order['pay_name'] = "余额支付";
        			$order['pay_id'] = 0;
        			$order['date_pay'] = microtime_float();
        		}else{
        			return $res;
        		}
        		break;
        	case 1:
        		$order['pay_name'] = "支付宝支付";
        		$order['pay_id'] = 1;
                $return['partner'] = C("alipay.partner");
                $return['seller_email'] = C("alipay.seller_email");
        		$return['notify_url'] = C("alipay.notify_url");
        		$return['public_key'] = C("alipay.ali_public_key");
        		$return['private_key'] = C("alipay.private_key");
        		$return['count'] = $sum;
        		break;
        	case 2:
        		$order['pay_name'] = "微信支付";
        		$order['pay_id'] = 2;
        		
                vendor("Weixin.WxPayHelper");

                $config = C("app_wx");
                $weixin = new \WxPayHelper($config);
                $prepay = $weixin->getPrePayOrder("10M网盘", $order_number, 1);
                
                
                if($prepay['return_code'] != "SUCCESS"){
                    $res['message'] = "微信请求失败";
                    $res['errcode'] = -107;
                    return $res;
                }
                if(isset($prepay['prepay_id'])){
                    $prepay = $weixin->getOrder($prepay['prepay_id']);
                    $prepay['package_name'] = $prepay['package'];
                    $return = array_merge($return, $prepay);
                }
                $return['appid'] = $config['appid'];
                $return['notify_url'] = $config['notify_url'];
        		$return['count'] = $sum * 100;
        		break;
        	default:
        		$res['message'] = '请传入支付方式';
        		$res['errcode'] = -106;
        		return $res;
        		break;
        }

        $res['content'] = array(
        		'order' => $order,
        		'return' => $return
        	);

        return $res;

	}
}