<?php
namespace app\web\controller;

use app\web\activities;
class GoodsAction{
	//普通商品列表
	public function goodsList(){
		$map = "g.goods_type = 1 and g.is_delete = 0 and g.on_sale = 1 and g.apply_status = 2";
		I("keyword") && $map .= " and g.name like '%".urldecode(I("keyword"))."%'";
		I("category_id") && $map .= " and cg.category_id = ".I("category_id");
		I("seller_id") && $map .= " and g.seller_id = ".I("seller_id");
		$sort = I("sort");
		
		//分页
		$page = I("page");
		
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(750, 750);
		
		$fields = "g.goods_id,concat('$host',g.cover,'$suffix') as cover,g.name,g.shop_price,g.sale_count,g.category_id,".
            "((select count(oc.id) from vc_order_comment oc where oc.goods_id = g.goods_id and oc.type =1 and oc.score > 3 and oc.is_deleted = 0)/(select count(oc.id) from vc_order_comment oc where oc.goods_id = g.goods_id and oc.type = 1 and oc.is_deleted = 0)) as praise_rate";
		$goods_list = D("goods_model")->getGoodsList($map,$fields,$sort,$page,60);
		
		return ['errcode' => 0, 'message' => '请求成功','content' => ['goods_list' => $goods_list['goods'],'pagesize' => $goods_list['pagesize'], 'count' => $goods_list['count']]];
	}
	
	//商品详情
	public function goodsDetail(){
	    $customer_id = get_customer_id();
		$goods_id = I("goods_id");
		if(empty($goods_id)){
			return ['errcode' => -101, 'message' => '请传入商品id'];
		}
		
		//是否为一元抢购
		$fields = "sg.date_end,sg.date_start";
		$special = D("special_model")->getSpecialByGoodsId($goods_id,1,$fields);
		//商品详情
		$goods = D("goods_model")->getGoodsInfo($goods_id,$customer_id);
		//满减
        $full = D('goods_model')->full();
        // 新用户
        $new_user = \app\library\SettingHelper::get("shuaibo_new_user",['limit' => 0, 'amount' => 10]);
        //是否自提
        $address = M("goods")->where(['goods_id' => $goods_id])->getField("address");
        $goods['address'] = $address;
        // 是否有协议
        if (isset($goods['need_protocol']) && $goods['need_protocol'] == 1) {
            $goods['protocol'] = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_goods_protocol"), ENT_QUOTES);
        } else {
            $goods['protocol'] = "";
        }

        // 足迹
        D('goods_model')->trail($customer_id,$goods_id);
		
        return ['errcode' => 0, 'message' => '请求成功', 'content' => ['goods' =>$goods,'special' => $special,'full' => $full, 'new_user' => $new_user]];
	}
	
	//获取评价
	public function getComments(){
		$goods_id = I("goods_id");
		if(empty($goods_id)){
			return ['errcode' => -101, 'message' => '请传入商品id'];
		}
		$customer_id = get_customer_id();
		if (empty($customer_id)){
			$customer_id = 0;
		}
		$type = I("type");
		$page = I("page");
		$comments = D("goods_model")->getComments($goods_id,$type,$page,$customer_id);
		
		return ['errcode' => 0, 'message' => '请求成功', 'content' => $comments];
	}
	
	//回复评论
	public function replyComment(){
		$customer_id = get_customer_id();
		if (empty($customer_id)){
			return ['errcode' => 99,'message' => '请重新登录'];
		}
		$content = I('content');
		if (empty($content)){
			return ['errcode' => -101,'message' => '请输入回复内容'];
		}
		$id = I("id");
		if(empty($id)){
			return ['errcode' => -101,'message' => '请传入回复id'];
		}
		$data = array(
				'comment_id' => $id,
				'content' => $content,
				'reply_date' => time(),
				'customer_id' => $customer_id
		);
		$res = D("goods_model")->replyComment($data);
		if($res === false){
			return ['errcode' => -202,'message' => '回复失败'];
		}
	
		return ['errcode' => 0,'message' => '回复成功'];
	}
	
	//获取回复
	public function replyContent(){
		$id = I("id");
		if(empty($id)){
			return ['errcode' => -101,'message' => '请传入回复id'];
		}
		$reply = D("goods_model")->getReplyComment($id);
	
		return ['errcode' => 0,'message' => '请求成功','content' => $reply];
	}
	
	//有用
	public function usefulComment(){
		$customer_id = get_customer_id();
		if (empty($customer_id)){
			return ['errcode' => 99,'message' => '请重新登录'];
		}
		$id = I("id");
		if(empty($id)){
			return ['errcode' => -101,'message' => '请传入回复id'];
		}
	
		$res = D("goods_model")->usefulComment($id,$customer_id);
		if ($res === false){
			return ['errcode'=> -202,'message' => '评论失败'];
		}
		return ['errcode' => 0,'message' => '成功'];
	}
	
	//店内推荐
	public function getRecommend(){
		$seller_id = I("seller_id");
		if (empty($seller_id)){
			return ['errcode' => -101, 'message' => '请传入店家id'];
		}
		$goods = D("goods_model")->getRecommend($seller_id);
		
		return ['errcode' => 0, 'message' => '请求成功', 'content' => $goods];
	}
	
	//猜你喜欢
	public function getGuessLike(){
		$category_id = I("category_id");
		if (empty($category_id)){
			return ['errcode' => -101, 'message' => '请传入分类'];
		}
		
		$goods = D("goods_model")->getGuessLike($category_id);
		
		return ['errcode' => 0, 'message' => '请求成功', 'content' => $goods];
	}
	
	//精品推荐
	public function getHighGoods(){
		$keyword = I("keyword");
        $category_id = (int)I('category_id');
		if(empty($keyword) && $category_id <= 0){
			return ['errcode' => -101,'message' => '请输入参数'];
		}
		$goods = D("goods_model")->getHighGoods($keyword,$category_id);
		
		return ['errcode' => 0 ,'message' => '请求成功', 'content' => $goods];
	}

    /**
     * 收藏/取消收藏
     * @return array
     */
    public function collection() {
        $customer_id = get_customer_id();
        if (empty($customer_id)){
            return ['errcode' => 99,'message' => '请重新登录'];
        }
        $goods_id = I("goods_id");
        if(empty($goods_id)){
            return ['errcode' => -100,'message' => '请传入商品信息'];
        }
        $res = D('collection_model')->collection($customer_id,$goods_id);
        if ($res['errcode'] == 100) {
            return ['errcode' => 0, 'message' => '收藏成功', 'content' => ['is_collection' => 1]];
        } elseif ($res['errcode'] == 101) {
            return ['errcode' => 0, 'message' => '取消收藏成功', 'content' => ['is_collection' => 0]];
        }
    }
}