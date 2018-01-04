<?php
namespace app\library\order;
class NetAlipayOrderHelper extends OrderHelper{
	
	public function pay_params($customer_id){
		vendor("Alipay.SubmitClass");
		
		$params = \app\library\SettingHelper::get_pay_params(2, ['is_open' => 0]);
		
		if($params['is_open'] == 0){
			return ['errcode' => -102 , 'message' => '支付宝暂未开启'];
		}
		$params['sign_type'] = "RSA";
		$params['input_charset'] = "utf-8";
		
		
		$alipaySubmit = new \AlipaySubmit($params);
		$parameter = array(
				"service"       => 'alipay.wap.create.direct.pay.by.user',
				"partner"       => $params['partner'],
				"seller_id"  => $params['seller_email'],
				"payment_type"	=> 1,
				"notify_url"	=> "http://" . get_domain() ."/app/alipay/notify",
				"return_url"	=> "http://" . get_domain() ."/wap/order/my_order" ,
				"_input_charset"	=> 'utf-8',
				"out_trade_no"	=> $this->order_number,
				"subject"	=> $this->subject,
				"total_fee"	=> $this->sum,
				"show_url"	=> "http://" . get_domain() . "/wap/",
				"body"	=> $this->body,
		);
		$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
		$this->return['html_text'] = $html_text;
		return ['errcode' => 0, 'message' => '请求成功'];
	}
}