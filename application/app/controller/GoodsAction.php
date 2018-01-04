<?php
namespace app\app\controller;
class GoodsAction
{	
	//商品列表加搜索
	public function GetGoods()
	{
		$map = "g.goods_type = 1 and g.is_delete = 0 and g.on_sale = 1 and g.apply_status = 2";
		I("keyword") && $map .= " and g.name like '%".I("keyword")."%'";
		I("category_id") && $map .= " and cg.category_id = ".I("category_id");
		I("seller_id") && $map .= " and g.seller_id = ".I("seller_id");
		$sort = I("sort");

		//分页
		$page = I("page");
		
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
		
		$fields = "g.goods_id,concat('$host',g.cover,'$suffix') as cover,g.name,g.shop_price,g.sale_count,g.category_id,((select count(oc.id) from vc_order_comment oc where oc.goods_id = g.goods_id and oc.type =1 and oc.score > 3 and oc.is_deleted = 0)/(select count(oc.id) from vc_order_comment oc where oc.goods_id = g.goods_id and oc.type = 1 and oc.is_deleted = 0)) as praise_rate";
		$goods_list = D("goods_model")->getGoodsList($map,$fields,$sort,$page,8);
		
		return ['errcode' => 0, 'message' => '请求成功','content' => ['goods_list' => $goods_list['goods'],'pagesize' => $goods_list['pagesize'], 'count' => $goods_list['count']]];
	}
	
	//商品详情
	public function goodsinfo(){
		$goods_id = I("goods_id");
		if(empty($goods_id)){
			return ['errcode' => -88,'message' => '请传入商品信息'];
		}
		$customer_id = get_customer_id();
		//是否为一元抢购
		$special = D("special_model")->getSpecialByGoodsId($goods_id,1,"sg.date_start,sg.date_end");
		//商品详情
		$goods = D("goods_model")->getGoodsInfo($goods_id,$customer_id);
		//单条评论
		$one_comment = D("goods_model")->getOneComment($goods_id);
        //满减
        $full = D('goods_model')->full();
        // 新用户
        $new_user = \app\library\SettingHelper::get("shuaibo_new_user",['limit' => 0, 'amount' => 10]);
        //是否自提
        $address = M("goods")->where(['goods_id' => $goods_id])->getField("address");
        $goods['address'] = $address;
        // 是否有协议
        if (isset($goods['need_protocol']) && $goods['need_protocol'] == 1) {
            $goods['protocol'] = "http://" . get_domain() . "/wap/goods/protocol";
        } else {
            $goods['protocol'] = "";
        }

        if(isset($goods['description'])){
			$goods['descript'] = "http://" . get_domain() . "/wap/goods/description" . "?goods_id=" . $goods_id;
		}

		// 足迹
        D('goods_model')->trail($customer_id,$goods_id);
		
		$goods['special'] = $special;
		$goods['comments'] = $one_comment;
        $goods['full'] = $full;
        $goods['new_user'] = $new_user;
        return ['errcode'=>0,'content'=>$goods,'message'=>'请求成功'];
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
	
	public function shareGoods(){
		$goods_id = I("goods_id");
		if(empty($goods_id)){
			return ['errcode' => -88,'message' => '请传入商品信息'];
		}

		$goods = D("goods_model")->getGoods($goods_id);
		$result = [];
		if(!empty($goods)){
			$result['url'] = "http://".get_domain()."/wap/goods/goodsinfo.html?goods_id=".$goods_id;
			$result['detail'] = isset($goods['detail'])?$goods['detail']:null;
			$result['mini_name'] = isset($goods['mini_name'])?$goods['mini_name']:null;
			$result['cover'] = isset($goods['cover'])?$goods['cover']:null;
		}
		return ['errcode' => 0, 'message' => '请求成功', 'content' => $result];
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