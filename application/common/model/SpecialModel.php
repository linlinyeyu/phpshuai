<?php
namespace app\common\model;

use think\Model;
use com\Page;
class SpecialModel extends Model{
	public function getSpecial($type = 0){
		return $this
		->alias("s")
		->join("special_images si",'si.special_id = s.special_id and si.status = 1',"left")
		->join("special_type st","st.type = s.type")
		->join("action a",'a.action_id = si.action_id',"left")
		->where(['s.type' => $type,'s.status' => 1])
		->field("si.image,si.params,si.sort,s.special_id,st.name,a.*")
		->order("si.sort")
		->select();
	}
	
	public function getSpecialType(){
		return M("special_type")->where(['status' => 1])->field("activities,type")->select();
	}
	
	public function getSpecialGoods($condition = [],$fields = "",$sort = '',$page = 1,$limit = 20){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$goods = $this
		->alias("s")
		->join("special_goods sg","sg.special_id = s.special_id")
		->join("goods g","g.goods_id = sg.goods_id")
		->where($condition)
		->field($fields)
		->page($page)
		->order($sort)
		->limit($limit)
		->select();
		
		$count = $this
		->alias("s")
		->join("special_goods sg","sg.special_id = s.special_id")
		->join("goods g","g.goods_id = sg.goods_id")
		->where($condition)
		->count();
		
		$pageSize = ceil($count/$limit);
		
		return ['goods' => $goods,"pagesize" => $pageSize];
	}
	
	public function getSpecialByGoodsId($goods_id = 0,$type =1,$fields=""){
		return $this
		->alias("s")
		->join("special_goods sg",'sg.special_id = s.special_id')
		->where(['sg.goods_id' => $goods_id,'s.type' => 1,'sg.status' => 1])
		->field("sg.special_id,sg.date_end,sg.date_start,sg.max_buy")
		->find();
	}
	
	public function getShoppingGoods($type = 0,$page = 1,$limit = 10){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$goods = M('goods')
		->alias("g")
		->where("g.is_delete = 0 and g.on_sale = 1 and g.apply_status = 2 and g.max_shopping_coin >= g.shop_price")
		->field("concat('$host',g.cover) as cover,g.max_shopping_coin as shopping_coin,g.name,g.shop_price,g.goods_id")
		->page($page)
		->limit($limit)
		->select();
		
		$count = M('goods')
		->alias("g")
		->where("g.is_delete = 0 and g.on_sale = 1 and g.apply_status = 2 and g.max_shopping_coin >= g.shop_price")
		->count();
		
		$pageSize = ceil($count/$limit);
		
		return ['goods' => $goods,"pagesize" => $pageSize];
	}
	
	public function getWebShoppingGoods($type = 0){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
	
		$goods = M('goods')
		->alias("g")
		->join("special_goods sg","sg.goods_id = g.goods_id and sg.status =1 and sg.special_id = ".$type,"left")
		->where("g.is_delete = 0 and g.on_sale = 1 and g.apply_status = 2 and g.max_shopping_coin >= g.shop_price and g.max_shopping_coin > 0")
		->field("concat('$host',g.cover) as cover,g.max_shopping_coin as shopping_coin,ifnull(sg.shopping_type,1) as shopping_type,g.name,g.shop_price,g.goods_id")
		->select();
	
		return $goods;
	}
	
	public function getIntergralSeller(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");		
		$seller = M("intergral_shop")
		->where(["status" => 1])
		->field("concat('$host',images) as image,seller_id")
		->limit(8)
		->select();
		
		return $seller;
	}
	
	public function getSellerList($address,$page = 1,$limit= 16){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$seller = M("seller_shopinfo")
		->alias("ss")
		->join("region r",'r.region_id = ss.province',"left")
		->join("region rr",'rr.region_id = ss.city',"left")
		->where(['r.region_name' => $address['province'], 'rr.region_name' => $address['city'], 'r.region_type' => 1,'rr.region_type' => 2,
				'ss.status' => 1,'ss.baicheng_apply' => 2,'ss.is_baicheng' => 1 
		])
		->field("concat('$host',ss.shop_logo) as shop_logo,ss.seller_id,ss.shop_name,r.region_name as province,rr.region_name as city")
		->page($page)
		->limit($limit)
		->select();
		
		$count = M("seller_shopinfo")
		->alias("ss")
		->join("region r",'r.region_id = ss.province',"left")
		->join("region rr",'rr.region_id = ss.city',"left")
		->where(['r.region_name' => $address['province'], 'rr.region_name' => $address['city'], 'r.region_type' => 1,'rr.region_type' => 2,
				'ss.status' => 1,'ss.baicheng_apply' => 2,'ss.is_baicheng' => 1 
		])
		->count();
		
		$pagesize = ceil($count/$limit);
		
		return ['seller' => $seller,'pagesize' => $pagesize];
	}
	
	public function getArea(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");		
		$area = M("area")
		->field("area_id,concat('$host',image) as image")
		->where(['status' => 1])
		->select();
		
		return $area;
	}
	
	public function getAreaShop($page = 1,$area_id,$limit = 20){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$shop = M("seller_shopinfo")
		->alias("ss")
		->join("region r",'r.region_id = ss.province','left')
		->join("region rr",'rr.region_id = ss.city','left')
		->where(['area_id' => $area_id,'baicheng_apply' => 2,'is_baicheng' => 1])
		->field("concat('$host',ss.shop_logo) as shop_logo,ss.shop_name,ss.seller_id,ss.shop_title,r.region_name as province,rr.region_name as city")
		->page($page)
		->limit($limit)
		->select();
		
		$count = M("seller_shopinfo")
		->alias("ss")
		->join("region r",'r.region_id = ss.province','left')
		->join("region rr",'rr.region_id = ss.city','left')
		->where(['area_id' => $area_id,'baicheng_apply' => 2,'is_baicheng' => 1])
		->field("concat('$host',ss.shop_logo) as shop_logo,ss.shop_name,ss.seller_id,ss.shop_title,r.region_name as province,rr.region_name as city")
		->count();
		
		$pagesize = ceil($count/$limit);
		
		return ['shop' => $shop,'pagesize' => $pagesize];
	}
	
	public function getExcellentShop(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$seller = M("seller_shopinfo")
		->alias("ss")
		->join("region r",'r.region_id = ss.province','left')
		->join("region rr",'rr.region_id = ss.city','left')
		->where(['ss.is_youxiu' => 1,'ss.status' => 1,'ss.baicheng_apply' => 2,'ss.is_baicheng' => 1])
		->field("ss.seller_id,ss.shop_name,concat('$host',ss.shop_logo) as shop_logo,ss.shop_title,r.region_name as province,rr.region_name as city,ss.shop_title")
		->limit(10)
		->select();
		
		return $seller;
	}
	
	public function getNewSeller(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$seller = M("seller_shopinfo")
		->alias("ss")
		->join("region r",'r.region_id = ss.province','left')
		->join("region rr",'rr.region_id = ss.city','left')
		->where(['ss.status' => 1,'ss.baicheng_apply' => 2, 'ss.is_baicheng' => 1])
		->field("ss.seller_id,ss.shop_name,concat('$host',ss.shop_logo) as shop_logo,ss.shop_title,r.region_name as province,rr.region_name as city")
		->order("ss.date_add desc")
		->limit(10)
		->select();
		
		return $seller;
	}
	
	public function getBrand($page = 1){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$brand = M("seller_brand")
		->where(['status' => 1])
		->field("concat('$host',image) as image,seller_id,brand_name")
		->page($page)
		->limit(20)
		->select();
		
		return $brand;
	}
}