<?php
namespace app\app\model;
use think\Model;
class Promotion extends Model{
	
	public function GetPromoteUser($customer_id){
        if(empty($customer_id)){
            return;
        }
        $customer = $this
            ->alias("p")
            ->join("customer c","c.promotion_code = p.code", "LEFT")
            ->field("c.uuid as customer_id, c.nickname, c.avater")
            ->where(['p.customer_id' => $customer_id])
            ->find();

        if($customer && $customer['avater']){
            $host = C("image_url");
            $customer['avater'] = $host . $customer['avater'];
        }
        return $customer;
    }
}