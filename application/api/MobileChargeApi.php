<?php
namespace app\api;
require_once 'curl.func.php';

class MobileChargeApi{
	private $appkey = 'c9338cbcce8d9c5f';
	private $appsecret = 'nvquIXKTvz8FM7qAwypNveKevI9TcX33';

    public static function getInstance(){
        return new MobileChargeApi();
    }
	
	//话费充值
	public function mobileRecharge($mobile,$amount,$outorderno = null){
		$sign = self::makeSign(array('mobile' => $mobile,'amount' => $amount,'outorderno' => $outorderno), $this->appsecret);
		$url = "http://api.jisuapi.com/mobilerecharge/recharge?appkey=$this->appkey&mobile=$mobile&amount=$amount&outorderno=$outorderno&sign=$sign";
		$result = curlOpen($url);
		$jsonarr = json_decode($result, true);
		//exit(var_dump($jsonarr));
		if($jsonarr['status'] != 0)
		{
			return ['errcode' => -500,'message' => $jsonarr['msg']];
		}
		
		return ['errcode' => 0,'message' => '充值成功'];
	}
	
	//流量充值
	public function flowRecharge($mobile,$amount,$outorderno = null,$areatype = null){
		$sign = self::makeSign(array('mobile' => $mobile,'amount' => $amount,'outorderno' => $outorderno), $this->appsecret);
		$url = "http://api.jisuapi.com/flowrecharge/recharge?appkey=$this->appkey&mobile=$mobile&amount=$amount&outorderno=$outorderno&sign=$sign";
		$result = curlOpen($url);
		$jsonarr = json_decode($result, true);
		//exit(var_dump($jsonarr));
		if($jsonarr['status'] != 0)
		{
			return ['errcode' => -500,'message' => $jsonarr['msg']];
		}
		
		return ['errcode' => 0,'message' => '充值成功'];
	}
	private function makeSign($queryarr, $appsecret)
	{
	    ksort($queryarr, SORT_STRING);
	    $str = implode($queryarr);
	    $str .= $appsecret;
	    //exit($str);
	    $str = md5($str);
	     
	    return $str;
	}
}