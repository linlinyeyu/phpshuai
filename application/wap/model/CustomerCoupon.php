<?php
namespace app\app\model;
use think\Model;
class CustomerCoupon extends Model{
	
	public function GetAllCoupon($customer_id, $state = 0){
		if(empty($customer_id) || $customer_id <= 0){
			return;
		}
		$condition = array("customer_id"=> $customer_id);
		if($state == 0){
			$condition['state'] = 0;
			$condition['date_end'] = array("gt",time());
			
		}
		else if($state == 1){
			$condition = "customer_id = $customer_id and (state in (1,2) or date_end < '".time()."')";
		}
		return $this
				->where($condition)
				->select();
		
	}

	public function GetAvaliableCoupon($customer_id, $money){
		if(empty($customer_id) || $customer_id <= 0){
			return;
		}
		$condition = array(
			'customer_id'=> $customer_id,
			"state" => 0, 
			"date_end" => array("gt", time()),
			"coupon_limit" => array("elt", $money)
			);

		return $this
		->where($condition)
		->select();
	}

	public function IsAvaliable($customer_id, $coupon){
		
	}
}