<?php
namespace app\web\controller;

class SellerAction{
	//店铺详情
	public function getSellerInfo(){
	    $customer_id = get_customer_id();
		$seller_id = I("seller_id");
		if (empty($seller_id)) {
			return ['errcode' => -101 ,'message' => '请传入店铺id'];
		}
		$map = "ss.seller_id = ".$seller_id." and g.is_delete = 0 and g.on_sale = 1 and g.apply_status = 2";
		I("min_price") && $map .= " and g.shop_price >= ".I("min_price");
		I("max_price") && $map .= " and g.shop_price <= ".I("max_price");
		I("is_recommend") && $map .= " and g.is_recommend = ".I("is_recommend");
		I("seller_cat_id") && $map .=" and scg.seller_cat_id = ".I("seller_cat_id"); 
		
		$sort = I('sort');
		
		$page = I("page");
		//店铺信息
		$seller = D("seller_shopinfo_model")->getSellerInfo($seller_id);
		//店铺分类
		$seller_cat = D("seller_shopinfo_model")->getSellerCat($seller_id);
		
		//店铺商品
		$seller_goods = D("seller_shopinfo_model")->getSellerGoods($map,$sort,$page,48);
		
		//店铺评分
		$seller_info = D("seller_shopinfo_model")->getAppSellerInfo($seller_id,$customer_id);
		
		return ['errcode' => 0 ,'message' => '请求成功', 'content' => ['seller' => $seller,'seller_cat' => $seller_cat, 'goods' => $seller_goods,'seller_comment' => $seller_info]];
	}
	
	//热销排行
	public function getHotGoods(){
		$seller_id = I("seller_id");
		if(empty($seller_id)){
			return ['errcode' => -101, 'message' => '请传入店铺id'];
		}
		$hot_goods = D("goods_model")->getHotGoods($seller_id);
		
		return ['errcode' => 0, 'message' => '请求成功', 'content' => $hot_goods];
	}
}