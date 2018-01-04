<?php
namespace app\library;
class MessageHelper{

	public static function push($customer_id, $title, $content, $type = 0, $id = null){
		D("message")->send($customer_id, $title, $content, $type, $id);
	}
	public static function sms($customer_id, $content){
		$phone = M("customer")->where(['customer_id' => $customer_id])->getField("phone");
		if(empty($phone)){
			return ['errcode'=> -101 , 'message' => '该用户未绑定手机'];
		}
		$helper = SmsHelper::getInstance();
		return $helper->send($phone, $content);
	}
	
	public static function push_sms($customer_id, $title, $content, $type = 0, $id = null){
		self::push($customer_id, $title, $content, $type, $id );
		self::sms($customer_id, $content);
	}
	
	public static function getInstance(){
		return new MessageHelper();
	}
	
	public function setTemplate($template){
		$this->template = $template;
	}
}