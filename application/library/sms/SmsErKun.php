<?php
namespace app\library\sms;
class SmsErKun extends \app\library\SmsHelper{
	
	public static $sms_id = 310052;
	public static $sms_key = 'ngj75wgc';
	public static $sms_url = 'http://218.204.70.58:28083/CmppWebServiceJax/sendsms.jsp';
	
	/**
	 * 发送的信息内容
	 * @param unknown $phonelist
	 * @param unknown $content
	 */
	public function send_fn($phonelist, $content){
		$phonelist = join(",", $phonelist);
		
		header("content-type:text/html;charset=gbk");
		$data = [
				'spid' => self::$sms_id,
				'password' => self::$sms_key,
				'nr' => iconv("UTF-8", "GBK", $content),
				'mobs' => $phonelist,
				'kzm' => 6
		];
		
	    $url = self::$sms_url; 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data) );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$result = curl_exec($ch);
		curl_close($ch);
		if(strpos($result, "0") >= 0){
			return ['errcode' => 0 ,'message' => '请求成功'];
		}else{
			vendor("Weixin.WxLog");
			$log_ = new \Log_();
			$dir = "data/sms/";
			$log_name= $dir. "erkun.log";//log文件路径
			mkDirs($_SERVER['DOCUMENT_ROOT'] . "/" . $dir);
			$log_->log_result($log_name,"【接收到的错误通知】:\n".$result."\n");
		}
		return ['errcode' => -101 , 'message' => '请求失败'];
		
	}
}