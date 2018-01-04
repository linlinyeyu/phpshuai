<?php
namespace app\app\controller;

class SpecialAction{
	//专题
	public function getActivity(){
		$page = I("page");
		$type = I("type");
		$terminal = I("t");
		$ip = get_client_ip();
		
		$class = \app\app\activity\Activities::checkMethod($page, $type,$ip,$terminal);
		if(empty($class)){
			return ['errcode' => -101 ,'message' => '没有该方法'];
		}
		return $class->getActivity();
	}
	
	//区域地区馆详情
	public function getAreaShop(){
		$page = I("page");
		$area_id = I("area_id");
	
		$shop = D("special_model")->getAreaShop($page,$area_id,8);
	
		return ['errcode' => 0,'message' => '请求成功','content' => $shop];
	}
}