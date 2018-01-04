<?php
namespace app\shopadmin\model;

use think\Model;
class Shop extends Model{
	public function getBanner($seller_id = 0){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$banner = M("seller_shopslide")
		->alias("ss")
		->join("action a",'a.action_id = ss.action_id')
		->field("ss.status,a.name as action_name,concat('$host',ss.img_url) as image,ss.name,ss.params,ss.img_order,ss.id,ss.action_id")
		->select();
		
		\app\library\ActionTransferHelper::transfer($banner);
		
		return $banner;
	}
	
	public function getShopInfo($seller_id = 0){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$shop = M("seller_shopinfo")
		->where(['seller_id' => $seller_id])
		->field("shop_name,type,seller_id,concat('$host',shop_logo) as shop_logo,concat('$host',shop_header) as shop_header,is_baicheng,baicheng_apply,is_youxiu,".
            "shop_address")
		->find();
		
		return $shop;
	}
}