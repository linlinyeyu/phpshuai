<?php
namespace app\app\model;
use think\Model;
class CustomerAddress extends Model{
	
	public function GetAllAddress($customer_id){
		if(empty($customer_id)){
			return;
		}

		return $this
		->where(array("customer_id"=>$customer_id))
		->field('customer_address_id, name,phone,province_name,city_name,area_name,postcode,address,is_default')
		->select();
	}

	public function GetOneAddress($customer_address_id){
		if(empty($customer_address_id)){
			return;
		}
		return $this
		->where(array(
			"customer_address_id" => $customer_address_id))
		->find();
	}
}