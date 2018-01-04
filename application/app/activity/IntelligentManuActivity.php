<?php
namespace app\app\activity;

//中国智造
class IntelligentManuActivity extends Activities{
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
				$images['params']['nav_title'] = $images['name'];
				unset($images['name']);
				$images['jump'] = $this->terminal == 1?$images['ios_param']:$images['android_param'];
			}
		}
		$condition = array(
				"sg.status" => 1,
				's.type' => $this->type,
				's.status' => 1,
				'g.is_delete' => 0,
				'g.on_sale' => 1,
				'g.apply_status' => 2
		);
		$fields = "g.goods_id,g.shop_price,g.name,g.sale_count,concat('$host',g.cover) as cover";
		$goods = D("special_model")->getSpecialGoods($condition,$fields,"sg.sort ASC",$this->page,8);
		
		return ['errcode' => 0, 'message' => '请求成功' ,'content' => ['special' => $special,'goods' => $goods]];
	}
}