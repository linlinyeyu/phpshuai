<?php
namespace app\web\controller;

class ShopAction{
	//关注店铺
	public function addFollow(){
		$customer_id = get_customer_id();
		
		if(empty($customer_id)){
			return ['errcode' => 99, 'message' => '请重新登录'];
		}
		
		$shop_id = I("seller_id");
		$shop = D("seller_shopinfo_model")->getShop($shop_id);

		if(empty($shop)){
			return ['errcode' => -101, 'message' => '未找到该商铺'];
		}
		
		$data = array(
				'seller_id' => $shop_id,
				'user_id' => $customer_id,
                'date_add' => time(),
                'date_upd' => time()
		);
		$follow_id = D("seller_shopinfo_model")->addFollow($data);
		
		if($follow_id === false){
			return ['errcode' => -201, 'message' => '插入失败'];
		}
		
		return ['errcode' => 0, 'message' => '关注成功'];
	}
	
	//取消关注
	public function cancelFollow(){
		$customer_id = get_customer_id();
		if(empty($customer_id)){
			return ['errcode' => 99, 'message' => '请重新登录'];
		}
		
		$shop_id = I("seller_id");
		$shop = D("seller_shopinfo_model")->getShop($shop_id);
		
		if(empty($shop)){
			return ['errcode' => -101, 'message' => '未找到该商铺'];
		}
		
		$data = array(
				'seller_id' => $shop_id,
				'user_id' => $customer_id
		);
		$follow_id = D("seller_shopinfo_model")->cancelFollow($data);
		
		if($follow_id === false){
			return ['errcode' => -201, 'message' => '插入失败'];
		}
		
		return ['errcode' => 0, 'message' => '取消关注成功'];
	}
	
	//店铺首页
	public function sellerHome(){
		$customer_id = get_customer_id();
		
		$seller_id = I("seller_id");
		$terminal = I("t");
		if (empty($seller_id)){
			return ['errcode' => -101,'message' => '请传入店铺id'];
		}
		
		//店铺信息
		$seller = D("seller_shopinfo_model")->getSellerInfo($seller_id);
		//是否关注
		$follow = D("seller_shopinfo_model")->isFollow($customer_id,$seller_id);
		//轮播图
		$banner = D("seller_shopinfo_model")->getBanner($seller_id,$terminal);
		
		//是否有分类
		$seller_cat = D("seller_shopinfo_model")->getSellerCat($seller_id);
		$is_cat = 1;
		if (empty($seller_cat)){
			$is_cat = 0;
		}
		
		return ['errcode' => 0,'message' => '请求成功','content' => ['seller' => $seller,'is_follow' => $follow,'banner' => $banner,'is_cat' => $is_cat]];
	}
	
	//掌柜推荐
	public function getRecommend(){
		$seller_id = I("seller_id");
		if (empty($seller_id)){
			return ['errcode' => -101, 'message' => '请传入店家id'];
		}
		$goods = D("goods_model")->getRecommend($seller_id);
		
		return ['errcode' => 0, 'message' => '请求成功', 'content' => $goods];
	}
	
	//本店热卖
	public function getHotGoods(){
		$seller_id = I("seller_id");
		$page = I("page");
		if(empty($seller_id)){
			return ['errcode' => -101, 'message' => '请传入店铺id'];
		}
		$hot_goods = D("goods_model")->getHotGoods($seller_id,$page);
	
		return ['errcode' => 0, 'message' => '请求成功', 'content' => $hot_goods];
	}
	
	//分类查看
	public function getSellerCat(){
		$seller_id = I("seller_id");
		if(empty($seller_id)){
			return ['errcode' => -101, 'message' => '请传入店铺id'];
		}
		$seller_cat = D("seller_shopinfo_model")->getSellerCat($seller_id);
		return ['errcode' => 0,'message' => '请求成功','content' => $seller_cat];
	}
	
	//店铺分类查看商品
	public function getSellerGoods(){
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
		
		//店铺商品
		$seller_goods = D("seller_shopinfo_model")->getSellerGoods($map,$sort,$page,8);
		
		return ['errcode' => 0,'message' => '请求成功','content' => $seller_goods];
	}
	
	//店铺信息
	public function getSellerInfo(){
		$customer_id = get_customer_id();
		
		$seller_id = (int)I("seller_id");
		$goods_id = (int)I('goods_id');
		if ($seller_id <= 0 && $goods_id <= 0) {
			return ['errcode' => -101 ,'message' => '请传入店铺id或者商品id'];
		}
		if ($seller_id <= 0) {
            $seller = D("seller_shopinfo_model")->getSellerInfoByGoods($goods_id,$customer_id);
        } else {
            $seller = D("seller_shopinfo_model")->getAppSellerInfo($seller_id,$customer_id);
        }

		return ['errcode' => 0,'message' => '请求成功','content' => $seller];
	}
	
	//联系客服
	public function contactService(){
		$params = \app\library\SettingHelper::get("shuaibo_seller_info",['address' => '杭州市' ,'qq' => '1211065934']);
		
		return ['errcode' => 0,'message' => '请求成功','content' => $params];
	}
	

    /**
     * 可领取优惠券
     * @return mixed
     */
    public function getCoupons() {
        $customer_id = get_customer_id();
        $seller_id = I("seller_id");
        return D('coupon_model')->getShopCoupons($customer_id,$seller_id);
    }

    public function getGoodsCoupons() {
        $customer_id = get_customer_id();
        $goods_id = I('goods_id');
        return D('coupon_model')->getGoodsCoupons($customer_id,$goods_id);
    }

    /**
     * 领取优惠券
     * @return array
     */
    public function receiveCoupon() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $coupon_id = I('coupon_id');
        if (empty($coupon_id)) {
            return ['errcode' => -100, 'message' => '请传入优惠券信息'];
        }
        return D('coupon_model')->receiveCoupon($customer_id,$coupon_id);
    }
}