<?php
namespace app\app\activity;

//百城万店
class MallsAndShopsActivity extends Activities{
	public function getActivity(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$special = D("special_model")->getSpecial($this->type);
		if (empty($special)){
			return ['errcode' => -101 ,'message' => '该活动不存在'];
		}
		foreach ($special as &$images){
			if (!empty($images['image']) && !empty($images['params'])){
				$images['image'] = $host.$images['image'];
				$images['params'] = unserialize($images['params']);
				$images['jump'] = $this->terminal == 1?$images['ios_param']:$images['android_param'];
			}
		}
		
		//区域地区馆
		$area = D("special_model")->getArea();
		
		//优秀店铺
		$excellent_shop = D("special_model")->getExcellentShop();
		
		//新店
		$new_seller = D('special_model')->getNewSeller();
		
		return ['errcode' => 0, 'message' => '请求成功','content' => ['special' => $special,'area' => $area,'excellent_shop' => $excellent_shop,'new_seller' => $new_seller]];
	}
}