<?php
namespace app\library\sms;
class SmsBaMi extends \app\library\SmsHelper{
	
	public static $sms_id = 1667;
	public static $sms_key = '247380503';
	public static $sms_url = 'http://www.itcc8.com:8890/mtPort/mt2?';
	// y000000
	/**
	 * 发送的信息内容
	 * @param unknown $phonelist
	 * @param unknown $content
	 */
	public function send_fn($phonelist, $content){
	
		$phonelist = join(",", $phonelist);
		$content = urlencode($content);
		$sms_id = self::$sms_id;
		$sms_key = md5(self::$sms_key);
	
		$url = self::$sms_url."uid=$sms_id&pwd=$sms_key&phonelist=$phonelist&content=$content";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$result = curl_exec($ch);
		curl_close($ch);
		$result = simplexml_load_string($result);
		if($result->CODE == 0){
			return ['errcode' => 0 , 'message' => '请求成功'];
		}
		return ['errcode' => -101 , 'message' => '请求失败'];
	}
}