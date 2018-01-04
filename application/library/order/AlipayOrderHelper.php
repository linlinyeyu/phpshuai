<?php
namespace app\library\order;
class AlipayOrderHelper extends OrderHelper{
	public function pay_params($customer_id){
		
		$params = \app\library\SettingHelper::get_pay_params(2, ['is_open' => 0]);
		if($params['is_open'] == 0){
			return ['errcode' => -101, 'message' => '支付宝支付未开启'];
		}
		$this->return['partner'] = $params['partner'];
        $this->return['seller_email'] = $params['seller_email'];
        $this->return['notify_url'] = "http://" . get_domain() ."/app/alipay/notify";
        $this->return['public_key'] = $params['ali_public_key'];
        $this->return['private_key'] = $params['private_key'];
        $this->return['count'] = $this->sum;

		return ['errcode' => 0 , 'message' => '成功'];
	}
}