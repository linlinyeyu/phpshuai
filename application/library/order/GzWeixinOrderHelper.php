<?php
namespace app\library\order;
class GzWeixinOrderHelper extends OrderHelper{
	
	public function pay_params($customer_id){
        vendor("Weixin.WxPayHelper");
        
        $config = \app\library\SettingHelper::get_pay_params(4);
        if(empty($config)){
        	return ['errcode' => -101 , 'message' => '微信公众号参数未配置'];
        }
        if(!isset($config['is_open']) || $config['is_open'] == 0){
        	return ['errcode' => -102, 'message' => '微信公众号支付未开启'];
        }
        
        $customer = M("customer")->where(['customer_id' => $customer_id])->field("wx_gz_openid")->find();
        $openid = I("openid");
        if( empty($openid) && (empty($customer) || empty($customer['wx_gx_openid'])) ){
        	return ['errcode' => -107, 'message' => '用户未登陆公众号，不能进行公众号支付'];
        }

        if(empty($openid)){
        	$openid = $customer['wx_gz_openid'];
        }
        
        $config['notify_url'] =  "http://" . get_domain() . "/app/wxpay/notifypub";
        $weixin = new \WxPayHelper($config);
        $extra = [
        		'openid' => $openid,
        		'trade_type' => 'JSAPI'
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
        
        if(isset($prepay['prepay_id'])){
        	$prepay = $weixin->getOrder($prepay['prepay_id']);
        	$prepay['package_name'] = $prepay['package'];
        	$this->return = array_merge($this->return, $prepay);
        }
        
        $this->return['count'] = $this->sum ;

        return ['errcode' => 0, 'message' =>'请求成功'];
        
	}
}