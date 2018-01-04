<?php
namespace app\web\activities;

//会员积分专区
class ShoppingCoinActivity extends Activities{
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
		
		$goods = D("special_model")->getWebShoppingGoods($this->type);
		$response = [];
		if(!empty($goods)){
			foreach ($goods as $value){
				$response[$value['shopping_type']][] = $value;
			}
		}
		
		return ['errcode' => 0 ,'message' => '请求成功', 'content' => ['special' => $special,'goods' => $response]];
	}
}