<?php
namespace app\library;
abstract class SmsHelper{

	public static function getInstance($type = "BaMi"){
		$class_name = "app\\library\\sms\\Sms" . $type;
		if(class_exists($class_name)){
			return new $class_name();
		}else{
			return new sms\SmsBaMi();
		}
	}
	
	/**
	 * 发送的信息内容
	 * @param unknown $phonelist
	 * @param unknown $content
	 */
	public function send($phonelist,$content){
		if(empty($phonelist)){
			return ['errcode' => -110 , 'message' => "未传电话列表"];
		}
		if(!is_array($phonelist)){
			$phonelist = array ($phonelist);
		}
		$content = self::sign($content);
		
		$result = $this->send_fn($phonelist, $content);
		return $result;
	}
	
	public abstract function send_fn($phonelist, $content);
		
		public static function sign($content){
			if(empty($content)){
				return "";
			}
			$sign_position = SettingHelper::get("shuaibo_message_sign_position","0");
			$sign = "【" . SettingHelper::get("shuaibo_message_sign","帅柏") . "】";
			if($sign_position == "0"){
				$content = $sign . $content;
			}else{
				$content = $content . $sign;
			}
			return $content;
		}
}
