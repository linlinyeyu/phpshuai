<?php
namespace app\app\activity;

class EverydayNewActivity extends Activities{
	public function getActivity() {
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$special = D("special_model")->getSpecial($this->type);
		if (empty($special)){
			return ['errcode' => -101 ,'message' => '该活动不存在'];
		}
		foreach ($special as &$images){
			if (!empty($images['image']) && !empty($images['params'])){
				$images['image'] = $host.$images['image'];
				$images['params'] = unserialize($images['params']);
				$images['jump'] = $this->terminal?$images['ios_param']:$images['android_param'];
			}
		}

		$goods = D("init_model")->getEveryNewGoods($this->page);
		
		return ['errcode' => 0, 'message' => '请求成功' ,'content' => ['special' => $special,'goods' => $goods]];
	}
}