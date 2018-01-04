<?php
namespace app\web\activities;

//百城万店
class MallsAndShopsActivity extends Activities{
	public function getActivity(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$special = D("special_model")->getSpecial($this->type);
		if (empty($special)){
			return ['errcode' => -101 ,'message' => '该活动不存在'];
		}
		foreach ($special as &$images){
			$images['image'] = $host.$images['image'];
			$images['params'] = unserialize($images['params']);
		}
		$address = self::getIpAddress($this->ip);
		//排头五个店铺
		$top_seller = D("special_model")->getSellerList($address,1,5);
		
		//区域地区馆
		$area = D("special_model")->getArea();
		
		//优秀店铺
		$excellent_shop = D("special_model")->getExcellentShop();
		
		//新店
		$new_seller = D('special_model')->getNewSeller();
		
		//店铺列表
		$seller_list = D("special_model")->getSellerList($address,$this->page,16);
		
		return ['errcode' => 0, 'message' => '请求成功','content' => ['special' => $special,'top_seller' => $top_seller,'area' => $area,'excellent_shop' => $excellent_shop,'new_seller' => $new_seller,'seller_list' => $seller_list]];
	}
	
	private function getIpAddress($ip= ''){
		if(empty($ip)){
			$ip = get_client_ip();
		}
		$res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $ip);
		if(empty($res)){ return false; }
		$jsonMatches = array();
		preg_match('#\{.+?\}#', $res, $jsonMatches);
		if(!isset($jsonMatches[0])){ return false; }
		$json = json_decode($jsonMatches[0], true);
		if(isset($json['ret']) && $json['ret'] == 1){
			$json['ip'] = $ip;
			unset($json['ret']);
		}else{
			return false;
		}
		return $json;
	}
}