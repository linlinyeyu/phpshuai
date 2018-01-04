<?php
namespace app\app\activity;

class OfflineDeliveryActivity extends Activities{
	public function getActivity(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$special = D("special_model")->getSpecial($this->type);
		if (empty($special)) {
			return ['errcode' => -201,'message' => '该活动不存在'];
		}
		foreach ($special as &$image) {
			if ($image != null) {
				if ($image['image'] != null) {
					$image['image'] = $host.$image['image'];
				}
                if (!empty($image['params'])){
                    $image['params'] = unserialize($image['params']);
                }
			}
		}
		$goods = D("goods_model")->getOfflineGoods($this->page,60);

		return ['errcode' => 0,'message' => '请求成功','content' => ['special' => $special,'goods' => $goods]];
	}
}