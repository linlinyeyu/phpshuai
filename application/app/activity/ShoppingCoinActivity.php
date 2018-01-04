<?php
namespace app\app\activity;

//会员积分专区
class ShoppingCoinActivity extends Activities{
	public function getActivity(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$special = D("special_model")->getSpecial($this->type);
		if (empty($special)){
			return ['errcode' => -101 ,'message' => '该活动不存在'];
		}
		foreach ($special as &$images){
			if (!empty($images['image']) && !empty($images['params'])){
				$images['image'] = $host.$images['image'];
				$images['jump'] = $this->terminal == 1?$images['ios_param']:$images['android_param'];
				$images['params'] = unserialize($images['params']);
			}
		}
		
		$goods = D("special_model")->getShoppingGoods($this->type,$this->page);
		
		return ['errcode' => 0 ,'message' => '请求成功', 'content' => ['special' => $special,'goods' => $goods]];
	}
}