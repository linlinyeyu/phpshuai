<?php
namespace app\app\model;
use think\Model;
class Coupon extends Model{
	const PAY = 1;

	const WEIXIN_REGISTER = 2;
	
	const QQ_REGISTER = 3;

	const PHONE_REGISTER = 4;
	
	const ACTIVITY = 5;
	
	const WEIXIN_GZ_REGISTER = 6;

	 function trans_coupon($coupon_id,$customer_id , $type = 0){
		$coupon = $this->where(['coupon_id' => $coupon_id])->find();
		if(empty($coupon)){
			return;
		}
		if($coupon['type'] == 0){
			$count = M("customer_coupon")->where(['coupon_id'=> $coupon_id])->count();
			if($count >= $coupon['use_total']){
				return;
			}
		}

		$customer_coupon = [];
		$customer_coupon['customer_id'] = $customer_id;
		$customer_coupon['coupon_id'] = $coupon['coupon_id'];
		$customer_coupon['coupon_name'] = $coupon['coupon_name'];
		$customer_coupon['coupon_limit'] = $coupon['coupon_limit'];
		$customer_coupon['coupon_money'] = $coupon['coupon_money'];
		$customer_coupon['state'] = 0;
		$customer_coupon['date_add'] = time();
		$customer_coupon['type'] = $type;
		$customer_coupon['goods_type'] = $coupon['goods_type'];
		$customer_coupon['goods_id'] = $coupon['goods_id'];
		$validate = 0;
		if($coupon['validate_type'] == 0){
			$validate = $coupon['date_expire'] + time();
		}else{
			if($coupon['validate_time'] < time()){
				return;
			}
			$validate = $coupon['validate_time'];
		}
		$start_time = 0;
		if($coupon['start_type'] == 0){
			$start_time = time();
		}else{
			$start_time = $coupon['start_time'];
		}
		$customer_coupon['date_end'] = $validate;
		$customer_coupon['date_start'] = $start_time;
		M("customer_coupon")->add($customer_coupon);
		return true;
		
	}
}