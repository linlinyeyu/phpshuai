<?php
namespace app\library\order;
use app\library\QrcodeHelper;

class WebWeixinOrderHelper extends OrderHelper{
	
	public function pay_params($customer_id){
		vendor("Weixin.WxPayHelper");
		
		$config = \app\library\SettingHelper::get_pay_params(5);
		if(empty($config)){
			return ['errcode' => -101 , 'message' => '微信参数未配置'];
		}
		if(!isset($config['is_open']) || $config['is_open'] == 0){
			return ['errcode' => -102, 'message' => '微信支付未开启'];
		}
		$config['notify_url'] = "http://" . get_domain() . "/app/wxpay/notifypub";
		$weixin = new \WxPayHelper($config);

		$extra = [
			'trade_type' => 'NATIVE'
		];
		
		if(isset($this->goods_tag) && !empty($this->goods_tag)){
			$extra['goods_tag'] = $this->goods_tag;
		}
		
		$weixin->setExtra($extra);
		try{
			$prepay = $weixin->getPrePayOrder($this->body, $this->order_number, $this->sum * 100);
		}catch(\Exception $e){
			return ['errcode' => -101, '获取微信信息失败'];
		}

		if($prepay['return_code'] != "SUCCESS"){
			return ['errcode'=> -102, '请求微信失败'];
		}
		
		$this->return['order_number'] = $this->order_number;
		
		$this->return['count'] = $this->sum ;

		$this->return['code_url'] = $prepay['code_url'];

		return ['errcode' => 0, 'message' =>'请求成功' ];
	}
	
}