<?php
namespace app\library\handler;
class MobileRechargeHandler extends Handler{

    public function orderNotify($order){
        $order_sn = $order['foregin_infos'];
        $mobile_recharge = M('mobile_recharge')->where(['order_sn' => $order_sn])->find();

        $api = \app\api\MobileChargeApi::getInstance();
//        $res['errcode'] = 0;
        if ($order['order_type'] == 4) {
            $res = $api->mobileRecharge($mobile_recharge['mobile'],$mobile_recharge['amount'],$mobile_recharge['out_order_no']);
        } elseif ($order['order_type'] == 5) {
            $res = $api->flowRecharge($mobile_recharge['mobile'],$mobile_recharge['amount'],$mobile_recharge['out_order_no']);
        }
        if ($res['errcode'] == 0) {
            // 充值成功
            M('mobile_recharge')->where(['recharge_id' => $mobile_recharge['recharge_id']])->save(['is_success' => 1]);
        } else {
            // 充值失败 加入余额
            M('customer')->where(['customer_id' => $order['customer_id']])->setInc('account',$mobile_recharge['total_fee']);
        }
        return ['errcode' => 0, 'message' => '充值'];
    }

    /*
    public function mobileNotify($result){
		$arr = array('order_no'=>$result['orderno'],'outorderno' => $result['outorderno'],'mobile' => $result['mobile'],'rechargestatus' => $result['rechargestatus'],'amount' => $result['amount'],'totalfee' => $result['totalfee']);
		if (self::checkSign($arr, $this->appsecret, $result['sign'])){
			//1成功 2充值失败 0充值中
			switch($rechargestatus)
			{
				case '1':
					{
						//充值成功
						$data = array(
								'mobile' => $result['mobile'],
								'order_sn' => $result['outorderno'],
								'out_order_no' => $result['orderno'],
								'amount' => $result['amount'],
								'total_fee' => $result['totalfee'],
								'type' => 1,
								'is_success' => 1,
								'date_add' => time()
						);
						M("mobile_charge")->add($data);
						break;
					}
				case '2':
					{
						//充值失败
						break;
					}
			}
		}
		return ['errcode' => 0,'message' => 'success'];
	}
	
	public function flowNotify($result){
		$arr = array('orderno' => $result['orderno'],'outorderno' => $result['outorderno'],'mobile' => $result['mobile'],'rechargestatus' => $result['rechargestatus'],'amount' => $result['amount'],'totalfee' => $result['totalfee']);
		if (self::checkSign($arr, $this->appsecret, $result['sign'])){
			//1成功 2充值失败 0充值中
			switch($rechargestatus)
			{
				case '1':
					{
						//充值成功
						$data = array(
								'mobile' => $result['mobile'],
								'order_sn' => $result['outorderno'],
								'out_order_no' => $result['orderno'],
								'amount' => $result['amount'],
								'total_fee' => $result['totalfee'],
								'is_success' => 1,
								'type' => 2,
								'date_add' => time()
						);
						M("mobile_recharge")->add($data);
						break;
					}
				case '2':
					{
						//充值失败
						break;
					}
			}
		}
		return ['errcode' => 0,'message' => 'success'];
	}
    */
	
	/**
	 * 验证签名
	 *
	 */
	function checkSign($queryarr, $appsecret, $signature)
	{
		$str = makeSign($queryarr, $appsecret);
		if($str == strtolower($signature)) return true;
		else return false;
	}
}