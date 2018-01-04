<?php
namespace app\web\controller;
use app\library\handler;
use app\library\handler\MobileRechargeHandler;
class MobileNotify{
	private $appsecret = 'nvquIXKTvz8FM7qAwypNveKevI9TcX33';
	public function mobileRecharge(){
		$result = I("");
		$mobile_handler = new MobileRechargeHandler();
		$mobile_handler->mobileNotify($result);
		if($rechargestatus != 0) echo 'success';
		exit();
	}
	
	public function flowRecharge(){
		$result = I("");
		$mobile_handler = new MobileRechargeHandler();
		$mobile_handler->flowNotify($result);
		if($rechargestatus != 0) echo 'success';
		exit();
	}
}