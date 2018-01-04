<?php
namespace app\app\controller;
use think\Cache;
class Rank
{
	public function getRankList(){
		$type = (int)I("type");
		
		$ranks = \app\library\SettingHelper::get_rank($type);
		$page = (int)I("page");
		if($page <= 0){
			$page = 1;
		}
		$ranks = array_slice($ranks['rank'], ($page - 1) * 20 , 20);
		return ['errcode' => 0, 'content' => $ranks, 'message' => '请求成功'];
	}
	
	public function getRankDetail(){
		
		$customer_id = get_customer_id();
		if(!$customer_id){
			return ['errcode' => 99 , 'message' => '请重新登录'];
		}
		$t = (int)I("t");
		$banners = D("banner")->GetBanners(1, $t);
		$records = D("customer")->getRankList($customer_id);
		
		return ['errcode' => 0 ,'message' => '请求成功', 'content' => ['banners' => $banners,'records' => $records]];
	}
}