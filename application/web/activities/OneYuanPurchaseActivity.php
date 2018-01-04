<?php
namespace app\web\activities;

//一元抢购
class OneYuanPurchaseActivity extends Activities{
	//获取活动
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
		$condition = array(
				"sg.status" => 1,
				's.type' => $this->type,
				's.status' => 1,
				'g.is_delete' => 0,
				'g.on_sale' => 1,
				'g.apply_status' => 2
		);
		$fields = "g.goods_id,1 as kill_price,g.shop_price,g.name,g.promote_date_start,sg.date_end,sg.date_start,concat('$host',g.cover) as cover";
		$goods = D("special_model")->getSpecialGoods($condition,$fields,"",$this->page,60);
		
		return ['errcode' => 0, 'message' => '请求成功' ,'content' => ['special' => $special,'goods' => $goods]];
	}
}