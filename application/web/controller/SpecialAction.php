<?php
namespace app\web\controller;

class SpecialAction{
	//专题活动
	public function getThematicActivities(){
		$page = I("page");
		$type = I("type");
		$ip = get_client_ip();
		$class = \app\web\activities\Activities::checkMethod($page, $type,$ip);
		if(empty($class)){
			return ['errcode' => -101 ,'message' => '没有该方法'];
		}
		return $class->getActivity();
	}
	
	//区域地区馆详情
	public function getAreaShop(){
		$page = I("page");
		$area_id = I("area_id");
		
		$shop = D("special_model")->getAreaShop($page,$area_id,20);
		
		return ['errcode' => 0,'message' => '请求成功','content' => $shop];
	}
}