<?php
namespace app\app\controller;

class MobileRechargeAction{
	//获取实际面额
	public function getActualFee(){
		$type = I("type");
		$actual_fee = M("mobile_recharge_fee")
		->where(['type' => $type])
		->field("amount,actual_fee,mobile_recharge_fee_id")
		->order("amount desc")
		->select();
		
		return ['errcode' => 0,'message' => '请求成功','content' => $actual_fee];
	}

    /**
     * 手机充值
     * @return array
     */
	public function recharge() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $phone = I('phone');
        if(empty($phone)){
            return ['errcode' => -100,'message' => '请输入电话'];
        }
        if(!preg_match("/^1\d{10}$/", $phone)){
            return ['errcode' => -101,'message' => '手机号格式错误'];
        }
        $id = I('mobile_recharge_fee_id');
        if (empty($id)) {
            return ['errcode' => -102,'message' => '请传入金额'];
        }
        return D('mobile_recharge_model')->recharge($customer_id,$phone,$id);
    }

    /**
     * 支付
     * @return array
     */
    public function pay() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $orders = I("orders");

        if (empty($orders)) {
            return ['errcode' => -101, 'message' => '请传入订单信息'];
        }

        $type = I("type");
//        if ($type == 1) {
//            return ['errcode' => -200, 'message' => '不能使用余额支付'];
//        }
        return D('mobile_recharge_model')->pay($customer_id,$type,$orders);
    }
}