<?php
namespace app\common\model;
use think\Model;
class GoodsModel extends Model{
	public function myCount($condition = []){
		return $this->where($condition)->count();
	}
	
	public function getGoodsList($condition=[],$fields="",$sort = "",$page = 1,$limit = 20){
		$goods = $this
		->alias("g")
		->join("category_goods cg",'cg.goods_id = g.goods_id','left')
		->join("seller_shopinfo ss",'ss.seller_id = g.seller_id and ss.status = 1')
		->field($fields)
		->where($condition)
		->page($page)
		->limit($limit)
		->order($sort)
		->group("g.goods_id")
		->select();
		
		$count = $this
		->alias("g")
		->join("category_goods cg",'cg.goods_id = g.goods_id','left')
		->join("seller_shopinfo ss",'ss.seller_id = g.seller_id and ss.status = 1')
		->where($condition)
		->count("distinct(g.goods_id)");
		
		$pageSize = ceil($count/60);
		
		return ['goods' => $goods,'pagesize' => $pageSize, 'count' => $count];
	}

    public function getHomeCategoryGoodsList($condition=[],$fields="",$sort = "",$page = 1,$limit = 20){
        $goods = $this
            ->alias("g")
            ->join("home_category_goods hcg",'hcg.goods_id = g.goods_id','left')
            ->join("seller_shopinfo ss",'ss.seller_id = g.seller_id and ss.status = 1')
            ->field($fields)
            ->where($condition)
            ->page($page)
            ->limit($limit)
            ->order($sort)
            ->group("g.goods_id")
            ->select();

        $count = $this
            ->alias("g")
            ->join("home_category_goods hcg",'hcg.goods_id = g.goods_id','left')
            ->join("seller_shopinfo ss",'ss.seller_id = g.seller_id and ss.status = 1')
            ->where($condition)
            ->count("distinct(g.goods_id)");

        $pageSize = ceil($count/60);

        return ['goods' => $goods,'pagesize' => $pageSize, 'count' => $count];
    }

    public function getAppHomeActivityGoodsList($condition=[],$fields="",$sort = "",$page = 1,$limit = 20){
        $goods = $this
            ->alias("g")
            ->join("app_home_activity_goods ahag",'ahag.goods_id = g.goods_id','left')
            ->join("seller_shopinfo ss",'ss.seller_id = g.seller_id and ss.status = 1')
            ->join("app_home_activity aha","aha.home_activity_id = ahag.home_activity_id")
            ->join("special s","s.special_id = aha.special_id")
            ->field($fields)
            ->where($condition)
            ->page($page)
            ->limit($limit)
            ->order($sort)
            ->group("ahag.home_activity_goods_id")
            ->select();

        $count = $this
            ->alias("g")
            ->join("app_home_activity_goods ahag",'ahag.goods_id = g.goods_id','left')
            ->join("seller_shopinfo ss",'ss.seller_id = g.seller_id and ss.status = 1')
            ->join("app_home_activity aha","aha.home_activity_id = ahag.home_activity_id")
            ->join("special s","s.special_id = aha.special_id")
            ->where($condition)
            ->count("distinct(g.goods_id)");

        $pageSize = ceil($count/60);

        return ['goods' => $goods,'pagesize' => $pageSize, 'count' => $count];
    }

    public function getGoodsInfoShow($goods_id,$customer_id = 0) {
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix_header = \app\library\UploadHelper::getInstance()->getThumbSuffix(1500, 700);
        $suffix_shop_logo = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        $cover_suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
        $goods_suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(800, 800);
        $goods = $this
            ->alias("g")
            ->join("image i","i.goods_id = g.goods_id and i.cover = 0","LEFT")
            ->join("special_goods sg","sg.goods_id = g.goods_id AND sg.special_id = 1 AND sg.status = 1 AND sg.date_start <= ".time()." AND sg.date_end >=".time(),"LEFT")
            ->join("seller_shopinfo ss",'g.seller_id = ss.seller_id')
            ->field("g.goods_id,g.category_id,g.manage_fee,g.pv,g.seller_id,g.weight,g.description,g.max_integration,g.max_shopping_coin,g.pv,g.dispatch_fee,g.dispatch_id,
				g.dispatch_type,concat('$host',g.cover,'$cover_suffix') as cover,GROUP_CONCAT('$host',i.name,'$goods_suffix') as images,g.mini_name,g.detail,
				g.market_price,g.shop_price,(g.sale_count + g.virtual_count) as sale_count,g.quantity as stock,g.name,g.max_once_buy,
				ss.shop_name,ss.kf_qq,concat('$host',ss.shop_logo,'$suffix_shop_logo') as shop_logo,concat('$host',ss.shop_header,'$suffix_header') as shop_header,ss.shop_name,".
                "sg.special_goods_id")
            ->where(['g.goods_id' => $goods_id,'g.is_delete' => 0])
            ->group("g.goods_id")
            ->find();

        if($goods['stock'] < 0){
            $goods['stock'] = 0;
        }
        $description = "";
        if(!empty($goods)){
            if (!empty($goods['special_goods_id'])) {
                $goods['shop_price'] = 1;
            }
            $description = $goods['description'];
            $goods['images'] = explode(",", $goods['images']);

            $goods['specs'] = M("goods_spec")
                ->alias("gs")
                ->join("goods_spec_item gsi","gsi.spec_id = gs.id")
                ->where(['goods_id' => $goods_id])
                ->field("gs.name, GROUP_CONCAT(gsi.id order by gsi.sort) as item_ids , GROUP_CONCAT(gsi.name order by gsi.sort) as item_names")
                ->group("gs.id")
                ->order("gs.sort")
                ->select();
            foreach ($goods['specs'] as &$s){
                $s['select'] = null;
                $s['items'] = [];
                $item_ids = explode(",", $s['item_ids']);
                $item_names = explode(",", $s['item_names']);
                for ($i = 0; $i< count($item_ids) ; $i ++){
                    $s['items'][] = ['id' => $item_ids[$i], 'name' => $item_names[$i]];
                }
                unset($s['item_ids']);
                unset($s['item_names']);
            }

            //商品规格
            $params = M("goods_param")
                ->where(['goods_id' => $goods_id])
                ->field("name,value")
                ->order("sort")
                ->select();
            $goods['params'] = $params;

            $goods['options'] = M("goods_option")
                ->alias("go")
                ->where(['goods_id' => $goods_id])
                ->field("name,sale_price,case when stock < 0 then 0 else stock end as stock,specs,id, weight")
                ->order("sort")
                ->select();
            if (empty($goods['options'])) {
                $goods['options'] = [];
            }
            foreach ($goods['options'] as &$option) {
                if ($option['stock'] < $goods['max_once_buy']) {
                    $goods['max_once_buy'] = $option['stock'];
                }
            }

            $total = M("order_comment")
                ->where(['is_deleted' => 0,'c.goods_id = goods_id',"type" => 1])
                ->field("count(1)")
                ->buildSql();

            $praise = M("order_comment")
                ->where(['is_deleted' => 0,'score' => ["in","4,5"],'c.goods_id = goods_id','type' => 1])
                ->field("count(1)")
                ->buildSql();

            $common = M("order_comment")
                ->where(['is_deleted' => 0,'score' => 3,'c.goods_id = goods_id','type' => 1])
                ->field("count(1)")
                ->buildSql();

            $bad = M("order_comment")
                ->where(['is_deleted' => 0,'score' => ['in',"1,2"] ,'c.goods_id = goods_id','type' => 1])
                ->field("count(1)")
                ->buildSql();

            $have_picture = M("order_comment")
                ->where(['is_deleted' => 0, "type" => 1, 'c.goods_id = goods_id', '_string' => "(images is not null or images != '')"])
                ->field("count(1)")
                ->buildSql();

            $goods_comment = M("order_comment")
                ->where(['is_deleted' => 0, 'type' => 1,'goods_id' => $goods_id])
                ->field("avg(score)")
                ->buildSql();

            $service_comment = M("order_comment")
                ->where(['is_deleted' => 0, 'type' => 2,'seller_id' => $goods['seller_id']])
                ->field("avg(service_score)")
                ->buildSql();

            $logistics_comment = M("order_comment")
                ->where(['is_deleted' => 0, 'type' => 2,'seller_id' => $goods['seller_id']])
                ->field("avg(logistics_score)")
                ->buildSql();

            $goods['comment'] = M("order_comment")->alias("c")
                ->where(['goods_id' => $goods_id, 'type' => 1])
                ->field("ifnull($total, 0) as total, ifnull($praise,0) as praise,ifnull($common,0) as common,ifnull($bad,0) as bad,ifnull($have_picture,0) as have_picture,
					format(ifnull($goods_comment,5),1) as goods_comment, format(ifnull($service_comment,5),1) as service_comment, format(ifnull($logistics_comment,5),1) as logistics_comment")
                ->find();
            if(empty($goods['comment'])){
                $goods['comment'] = ['total' => 0,'praise' => 0, 'common' => 0,'bad' => 0, 'have_picture' => 0, 'praise_rate' => 1, 'goods_comment' => 5.0,'service_comment' => 5.0,'logistics_comment' => 5.0];
            }else {
                if ($goods['comment']['total'] != 0) {
                    $goods['comment']['praise_rate'] = round($goods['comment']['praise']/$goods['comment']['total'],2);
                } else {
                    $goods['comment']['praise_rate'] = 1;
                }
            }

            unset($s);
        }

        if(empty($goods)){
            return;
        }

        // 优惠券
        $coupons = D('coupon_model')->getGoodsCoupons($customer_id,$goods_id);
        if (count($coupons) <= 0) {
            $goods['coupons'] = [];
        }
        $goods['coupons'] = $coupons['content'];

        //判断是否收藏该商品
        if(!empty($customer_id)){
            $collection = S("shuaibo_collection".$customer_id);
            if(!empty($collection)){
                $goods['is_collection'] = in_array($goods_id, $collection);
            }else{
                $collection_id = M("collection")->field("collection_id")->where(['goods_id' => $goods_id,'customer_id' => $customer_id])->find();
                $goods['is_collection'] = !empty($collection_id);
            }
            $collection = S("shuaibo_seller_collection".$customer_id);
            if(!empty($collection)){
                $goods['is_seller_collection'] = in_array($goods_id, $collection);
            }else{
                $collection_id = M("seller_follow")->field("id")->where(['seller_id' => $goods['seller_id'], 'user_id' => $customer_id])->find();
                $goods['is_seller_collection'] = !empty($collection_id);
            }
        }else {
            $goods['is_collection'] = false;
            $goods['is_seller_collection'] = false;
        }

        $goods['goods_spec_url'] = "http://".get_domain()."/wap/goods/goodsSpec.html?goods_id=".$goods_id;
        return $goods;
    }
	
	public function getGoodsInfo($goods_id,$customer_id){
		
		$goods = $this->getGoods($goods_id);
		
		if(empty($goods)){
			return;
		}

        // 优惠券
        $coupons = D('coupon_model')->getGoodsCoupons($customer_id,$goods_id);
		if (count($coupons) <= 0) {
            $goods['coupons'] = [];
        }
        $goods['coupons'] = $coupons['content'];
		
		//判断是否收藏该商品
		if(!empty($customer_id)){
			$collection = S("shuaibo_collection".$customer_id);
			if(!empty($collection)){
				$goods['is_collection'] = in_array($goods_id, $collection);
			}else{
				$collection_id = M("collection")->field("collection_id")->where(['goods_id' => $goods_id,'customer_id' => $customer_id])->find();
				$goods['is_collection'] = !empty($collection_id);
			}
			$collection = S("shuaibo_seller_collection".$customer_id);
			if(!empty($collection)){
				$goods['is_seller_collection'] = in_array($goods_id, $collection);
			}else{
				$collection_id = M("seller_follow")->field("id")->where(['seller_id' => $goods['seller_id'], 'user_id' => $customer_id])->find();
				$goods['is_seller_collection'] = !empty($collection_id);
			}
		}else {
			$goods['is_collection'] = false;
			$goods['is_seller_collection'] = false;
		}
		
		$goods['goods_spec_url'] = "http://".get_domain()."/wap/goods/goodsSpec.html?goods_id=".$goods_id;
		return $goods;
	}
	
	public function getGoods($goods_id, $value = []){
		$goods = S("shuaibo_goods_" . $goods_id);
		
		if(empty($goods)){
			$goods = $this->setGoods($goods_id);
			S("shuaibo_goods_" . $goods_id, $goods);
		}
		
		if(empty($goods)){
			return;
		}
		$goods = unserialize($goods);
		$result = [];
		if(!empty($value)){
			foreach ($value as $v){
				if(isset($goods[$v])){
					$result[$v] = $goods[$v];
				}
			}
		}else{
			$result = $goods;
		}
		return $result;
	}
	
	public function getRecs($goods_id){
		$goods = S("bear_goods_recs");
		if(empty($goods)){
			$goods = $this->setRecs();
			S("bear_goods_recs", $goods);
		}
		if(empty($goods)){
			return [];
		}
		
		$goods = unserialize($goods);
		if(empty($goods)){
			return [];
		}
		$key = 0;
		foreach ($goods as $k => $g){
			if($g['goods_id'] == $goods_id){
				$key = $k;
				break;
			}
			$key = $k;
		}
		unset($goods[$key]);
		$goods = array_values($goods);
		return $goods;
	}
	
	public function setRecs(){
		$host = \app\library\SettingHelper::get("bear_image_url");
		$cover_suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(600, 600);
		$recs = M("goods")
		->where(['on_sale' => 1,'is_deleted' => 0 ,'quantity' => ['gt' , 0]])
		->field("goods_id,name, concat('$host', cover,'$cover_suffix') as image,price,market_price, sale_count + virtual_count as sale_count")
		->limit(5)
		->select();
		
		return serialize($recs);
		
	}
	
	public function updateGoods($goods_id , $value =[]){
		$goods = $this->getGoods($goods_id);
		if(!empty($goods) && !empty($value)){
			foreach ($value as $k => $v){
				$goods[$k] = $v;
			}
		}
		S("shuaibo_goods_" . $goods_id, serialize($goods));
	}
	
	public function setGoods($goods_id){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix_header = \app\library\UploadHelper::getInstance()->getThumbSuffix(1500, 700);
        $suffix_shop_logo = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        $cover_suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
        $goods_suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(800, 800);
        $goods = $this
		->alias("g")
		->join("image i","i.goods_id = g.goods_id and i.cover = 0","LEFT")
            ->join("special_goods sg","sg.goods_id = g.goods_id AND sg.special_id = 1 AND sg.status = 1 AND sg.date_start <= ".time()." AND sg.date_end >=".time(),"LEFT")
            ->join("seller_shopinfo ss",'g.seller_id = ss.seller_id')
		->field("g.goods_id,g.category_id,g.manage_fee,g.pv,g.seller_id,g.weight,g.description,g.max_integration,g.max_shopping_coin,g.pv,g.dispatch_fee,g.dispatch_id,g.is_verify,g.reward_fee,g.integra_fee,g.purchase_fee,g.hongfu,
				g.dispatch_type,concat('$host',g.cover,'$cover_suffix') as cover,GROUP_CONCAT('$host',i.name,'$goods_suffix') as images,g.mini_name,g.detail,g.address,
				g.market_price,g.shop_price,(g.sale_count + g.virtual_count) as sale_count,g.quantity as stock,g.name,g.max_once_buy,
				ss.shop_name,ss.kf_qq,concat('$host',ss.shop_logo,'$suffix_shop_logo') as shop_logo,concat('$host',ss.shop_header,'$suffix_header') as shop_header,ss.shop_name,".
                "sg.special_goods_id")
		->where(['g.goods_id' => $goods_id,'g.is_delete' => 0,'g.on_sale' => 1,'ss.status' => 1,'g.apply_status' => 2])
		->group("g.goods_id")
		->find();
		
		if($goods['stock'] < 0){
			$goods['stock'] = 0;
		}
		$description = "";
		if(!empty($goods)){
		    if (!empty($goods['special_goods_id'])) {
		        $goods['shop_price'] = 1;
            }
			$description = $goods['description'];
			$goods['images'] = explode(",", $goods['images']);
			
			$goods['specs'] = M("goods_spec")
			->alias("gs")
			->join("goods_spec_item gsi","gsi.spec_id = gs.id")
			->where(['goods_id' => $goods_id])
			->field("gs.name, GROUP_CONCAT(gsi.id order by gsi.sort) as item_ids , GROUP_CONCAT(gsi.name order by gsi.sort) as item_names")
			->group("gs.id")
			->order("gs.sort")
			->select();
			foreach ($goods['specs'] as &$s){
				$s['select'] = null;
				$s['items'] = [];
				$item_ids = explode(",", $s['item_ids']);
				$item_names = explode(",", $s['item_names']);
				for ($i = 0; $i< count($item_ids) ; $i ++){
					$s['items'][] = ['id' => $item_ids[$i], 'name' => $item_names[$i]];
				}
				unset($s['item_ids']);
				unset($s['item_names']);
			}
			
			//商品规格
			$params = M("goods_param")
			->where(['goods_id' => $goods_id])
			->field("name,value")
			->order("sort")
			->select();
			$goods['params'] = $params;
			
			$goods['options'] = M("goods_option")
			->alias("go")
			->where(['goods_id' => $goods_id])
			->field("name,sale_price,case when stock < 0 then 0 else stock end as stock,specs,id, weight")
			->order("sort")
			->select();
			if (empty($goods['options'])) {
                $goods['options'] = [];
            }
			foreach ($goods['options'] as &$option) {
			    if ($option['stock'] < $goods['max_once_buy']) {
                    $goods['max_once_buy'] = $option['stock'];
                }
            }
			
			$total = M("order_comment")
			->where(['is_deleted' => 0,'c.goods_id = goods_id',"type" => 1])
			->field("count(1)")
			->buildSql();
				
			$praise = M("order_comment")
			->where(['is_deleted' => 0,'score' => ["in","4,5"],'c.goods_id = goods_id','type' => 1])
			->field("count(1)")
			->buildSql();
				
			$common = M("order_comment")
			->where(['is_deleted' => 0,'score' => 3,'c.goods_id = goods_id','type' => 1])
			->field("count(1)")
			->buildSql();
			
			$bad = M("order_comment")
			->where(['is_deleted' => 0,'score' => ['in',"1,2"] ,'c.goods_id = goods_id','type' => 1])
			->field("count(1)")
			->buildSql();
			
			$have_picture = M("order_comment")
			->where(['is_deleted' => 0, "type" => 1, 'c.goods_id = goods_id', '_string' => "(images is not null or images != '')"])
			->field("count(1)")
			->buildSql();

			$goods_comment = M("order_comment")
			->where(['is_deleted' => 0, 'type' => 1,'goods_id' => $goods_id])
			->field("avg(score)")
			->buildSql();
			
			$service_comment = M("order_comment")
			->where(['is_deleted' => 0, 'type' => 2,'seller_id' => $goods['seller_id']])
			->field("avg(service_score)")
			->buildSql();
			
			$logistics_comment = M("order_comment")
			->where(['is_deleted' => 0, 'type' => 2,'seller_id' => $goods['seller_id']])
			->field("avg(logistics_score)")
			->buildSql();
			
			$goods['comment'] = M("order_comment")->alias("c")
			->where(['goods_id' => $goods_id, 'type' => 1])
			->field("ifnull($total, 0) as total, ifnull($praise,0) as praise,ifnull($common,0) as common,ifnull($bad,0) as bad,ifnull($have_picture,0) as have_picture,
					format(ifnull($goods_comment,5),1) as goods_comment, format(ifnull($service_comment,5),1) as service_comment, format(ifnull($logistics_comment,5),1) as logistics_comment")
			->find();
			if(empty($goods['comment'])){
				$goods['comment'] = ['total' => 0,'praise' => 0, 'common' => 0,'bad' => 0, 'have_picture' => 0, 'praise_rate' => 1, 'goods_comment' => 5.0,'service_comment' => 5.0,'logistics_comment' => 5.0];
			}else {
			    if ($goods['comment']['total'] != 0) {
                    $goods['comment']['praise_rate'] = round($goods['comment']['praise']/$goods['comment']['total'],2);
                } else {
                    $goods['comment']['praise_rate'] = 1;
                }
			}
			
			unset($s);
		}
		return serialize($goods);
	}
	
	public function getComments($goods_id = 0,$type = 0,$page = 1,$customer_id = 0){
		$prefix = C("database.prefix");
		$map = array(
				"oc.goods_id" => $goods_id,
				"oc.is_deleted" => 0, 
				'oc.type' => 1
		);
		if($type == 1){
			$map['oc.score'] = array('gt',3);
		}elseif ($type == 2){
			$map['oc.score'] = array('eq',3);
		}elseif ($type == 3){
			$map['oc.score'] = array('lt',3);
		}elseif ($type == 4){
			$map['_string'] = " (oc.images is not null or oc.images != '')";
		}
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(1500, 700);
		$cover_suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		$content = M("order_comment")
		->alias("oc")
		->join("customer c","c.customer_id = oc.customer_id")
		->join("order_goods og",'og.goods_id = oc.goods_id and og.order_id = oc.order_id',"left")
		->join("goods_option go","go.id = og.option_id","left")
		->join("comment_useful cu",'cu.comment_id = oc.id and cu.customer_id ='.$customer_id,'left')
		->where($map)
		->field("(select count(comment_reply_id) from {$prefix}comment_reply cr where cr.comment_id = oc.id) as reply_count,oc.id,oc.content,c.nickname,oc.score,concat('$host',c.avater,'$cover_suffix') as image,oc.date_add as date,oc.images,oc.reply_content,oc.reply_images,oc.date_reply,go.name as option_name,oc.add_content,oc.add_images,oc.add_date,
				  (select count(comment_useful_id) from {$prefix}comment_useful cu where cu.comment_id = oc.id) as useful,case when cu.comment_useful_id is null then 0 when cu.comment_useful_id is not null then 1 end as is_useful")
		->page($page)
		->order("oc.date_add desc")
		->group("oc.id")
		->limit(20)
		->select();
		foreach ($content as &$comment){
			$comment['current_img'] = null;
			if (empty($comment['images'])){
				$comment['images'] = null;
			}
			if(!empty($comment['images'])){
				$comment['images'] = explode(",", $comment['images']);
				foreach ($comment['images'] as &$image){
					$image = $host.$image.$suffix;
				}
			}
			if (empty($comment['reply_images'])){
				$comment['reply_images'] = null;
			}
			if(!empty($comment['reply_images'])){
				$comment['reply_images'] = explode(",", $comment['reply_images']);
				foreach ($comment['reply_images'] as &$image){
					$image = $host.$image.$suffix;
				}
			}
			if (empty($comment['add_images'])){
				$comment['add_images'] = null;
			}
			if (!empty($comment['add_images'])){
				$comment['add_images'] = explode(",", $comment['add_images']);
				foreach ($comment['add_images'] as &$image){
					$image = $host.$image.$suffix;
				}
			}
		}
		
		$count = M("order_comment")
		->alias("oc")
		->where($map)
		->count("distinct oc.id");
		$pageSize = ceil($count/20);
		
		return ['content' => $content,'pagesize' => $pageSize];
	}
	
	public function replyComment($data = []){
		$res = M("comment_reply")->add($data);
		
		return $res;
	}
	
	public function getReplyComment($id = 0){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$seller_reply = M("order_comment")->where(['id' => $id])->field("reply_content,date_reply")->find();
		$reply = M('comment_reply')
		->alias("cr")
		->join("customer c",'c.customer_id = cr.customer_id')
		->where(['cr.comment_id' => $id])
		->field("cr.content,cr.reply_date,concat('$host',c.avater) as avater,c.nickname")
		->select();
		
		return ['seller_reply' => $seller_reply,'reply' => $reply];
	}
	
	public function usefulComment($id = 0,$customer_id = 0){
		$res = false;
		$userful = M("comment_useful")->where(['comment_id' => $id,'customer_id' => $customer_id])->find();
		if(!empty($userful)){
			$res = M("comment_useful")->where(['comment_id' => $id,'customer_id' => $customer_id])->delete();
		}else {
			$res = M("comment_useful")->add(['comment_id' => $id,'customer_id' => $customer_id]);
		}
		return $res;
	}
	
	//店内推荐
	public function getRecommend($seller_id = 0){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$prefix = C("database.prefix");
		$sql = "SELECT concat('$host',g.cover) as cover,g.name,g.goods_id,g.shop_price,g.sale_count
			FROM {$prefix}goods g WHERE g.goods_id and g.goods_type = 1 and g.seller_id = $seller_id and g.is_delete = 0 and g.on_sale = 1 and g.is_recommend = 1 and g.apply_status = 2
			ORDER BY rand() LIMIT 16";
		$goods = M("goods")->query($sql);
		
		return $goods;
	}
	
	//猜你喜欢
	public function getGuessLike($category_id = 0){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(800, 800);
		
		$prefix = C("database.prefix");
		$sql = "SELECT concat('$host',g.cover,'$suffix') as cover,g.name,g.goods_id,g.shop_price,g.sale_count
			FROM {$prefix}goods AS g JOIN (SELECT ROUND(RAND() * ((SELECT MAX(goods_id) FROM {$prefix}goods where goods_type = 1 and category_id = '$category_id')-(SELECT MIN(goods_id) FROM {$prefix}goods where goods_type = 1 and category_id = '$category_id'))+(SELECT MIN(goods_id) FROM {$prefix}goods where goods_type = 1 and category_id = '$category_id')) AS id) AS t2
			WHERE g.goods_id >= t2.id and g.goods_type = 1 and g.category_id = $category_id and g.is_delete = 0 and g.on_sale = 1 and g.apply_status = 2
			ORDER BY g.goods_id LIMIT 6";
		
		$goods = M("goods")->query($sql);
		return $goods;
	}
	
	//热销排行
	public function getHotGoods($seller_id = 0,$page = 1){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$goods = $this
		->where(['seller_id' => $seller_id,'goods_type' => 1,'is_delete' => 0, 'on_sale' => 1,'apply_status' => 2])
		->field("concat('$host',cover) as cover,name,shop_price,sale_count,goods_id")
		->order("sale_count desc")
		->page($page)
		->limit(8)
		->select();
		
		return $goods;
	}
	
	//精品推荐
	public function getHighGoods($keywords="",$category_id){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(800, 800);

        $map = "g.goods_type = 1 and g.is_delete = 0 and g.on_sale = 1 and g.apply_status = 2";
        if (!empty($keywords)) {
            $map .= " and g.name like '%".urldecode(I("keyword"))."%'";
        }
        if ($category_id > 0) {
            $map .= " and cg.category_id = ".I("category_id");
        }

        $goods = $this
            ->alias("g")
            ->join("category_goods cg","cg.goods_id = g.goods_id")
		->where($map)
		->field("concat('$host',g.cover,'$suffix') as cover,g.name,g.shop_price,g.sale_count,g.goods_id")
		->order("g.sale_count desc")
		->limit(5)
        ->group("g.goods_id")
		->select();
		
		return $goods;
	}
	
	public function GetGoodsName($goods_id = 0){
		return $this->where(['goods_id' => $goods_id])->getField("name");
	}
	
	public function getInitLike($trail = []){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
		$ids = [];
		foreach ($trail as $category){
			$ids[] = $category['category_id'];
		}
		
		$ids = implode(",",$ids);
		$ids = rtrim($ids,",");
		
		$prefix = C("database.prefix");
		$sql = "SELECT concat('$host',g.cover,'$suffix') as cover,g.name,g.goods_id,g.shop_price,g.sale_count
		from {$prefix}goods g
		WHERE g.goods_type = 1 and g.category_id in ($ids) and g.is_delete = 0 and g.on_sale = 1 and g.apply_status = 2
		ORDER BY rand() LIMIT 20";
		
		$goods = M("goods")->query($sql);
		return $goods;
	}

	public function getDefaultLike(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
		
		$prefix = C("database.prefix");
		$sql = "SELECT concat('$host',g.cover,'$suffix') as cover,g.name,g.goods_id,g.shop_price,g.sale_count
		FROM {$prefix}goods AS g JOIN (SELECT ROUND(RAND() * ((SELECT MAX(goods_id) FROM {$prefix}goods where goods_type = 1)-(SELECT MIN(goods_id) FROM {$prefix}goods where goods_type = 1))+(SELECT MIN(goods_id) FROM {$prefix}goods where goods_type = 1)) AS id) AS t2
		WHERE g.goods_id >= t2.id and g.goods_type = 1 and g.is_delete = 0 and g.on_sale = 1 and g.apply_status = 2
		ORDER BY g.sale_count desc LIMIT 20";
		
		$goods = M("goods")->query($sql);
		return $goods;
	}
	
	public function getOneComment($goods_id = 0){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        $suffix_h = \app\library\UploadHelper::getInstance()->getThumbSuffix(800, 800);

        $comment = M("order_comment")
		->alias("oc")
		->join("customer c",'c.customer_id = oc.customer_id')
		->where(['oc.goods_id' => $goods_id,'oc.type' => 1,"oc.is_deleted" => 0])
		->field("concat('$host',c.avater,'$suffix') as avater,oc.score,oc.content,oc.images,oc.date_add,c.nickname")
		->order("oc.date_add desc")
		->find();
		
		if(!empty($comment['images'])){
			$images = explode(",", $comment['images']);
            $imgs = [];
            $imgs_h = [];
			foreach ($images as $image){
                $imgs[] = $host.$image.$suffix;
                $imgs_h[] = $host.$image.$suffix_h;
			}
            $comment['images_url'] = $imgs;
            $comment['images_h_url'] = $imgs_h;
            $comment['images'] = $images;
		}else {
            $comment['images_url'] = [];
            $comment['images_h_url'] = [];
            $comment['images'] = [];
		}

		return $comment;
	}

    /**
     * 足迹
     * @param $customer_id
     * @param $goods_id
     */
	public function trail($customer_id,$goods_id) {
	    if (empty($customer_id)) {
	        return;
        }
        $trail = M('trail')->where(['user_id' => $customer_id, 'goods_id' => $goods_id,'date_add' => ['egt',strtotime(date("Y-m-d"),time())]])->find();
        if (!empty($trail)) {
            return;
        }
        M('trail')->add([
            'user_id' => $customer_id,
            'goods_id' => $goods_id,
            'date_add' => time()
        ]);
        return;
    }

    public function full() {
        $full = M('fullcut')
            ->alias('f')
            ->where(['f.active' => 1])
            ->field("f.id as full_id,f.name,f.limit,f.amount")
            ->order('f.limit DESC')
            ->select();
        return $full;
    }

    /**
    *奖励商品
    **/
    public function getRewardGoods($page = 0,$limit = 60){
        $host = \app\library\SettingHelper::get("shuaibo_image_url");

        $count = M("goods")
        ->where("reward_fee is not null and reward_fee > 0 and is_delete = 0 and apply_status = 2 and on_sale = 1")
        ->count();

        $goods = M("goods")
        ->where("reward_fee is not null and reward_fee > 0 and is_delete = 0 and apply_status = 2 and on_sale = 1")
        ->field("name,shop_price,concat('$host',cover) as cover,sale_count,goods_id")
        ->page($page)
        ->limit($limit)
        ->order("date_add desc")
        ->select();

        $pageSize = ceil($count/60);

        return ['goods' => $goods,'pagesize' => $pageSize];
    }
    /**
    *鸿府商品
    **/
    public function getHongfuGoods($page = 0,$limit = 60){
        $host = \app\library\SettingHelper::get("shuaibo_image_url");

        $count = M("goods")
        ->where("hongfu is not null and hongfu > 0 and is_delete = 0 and apply_status = 2 and on_sale = 1")
        ->count();

        $goods = M("goods")
        ->where("hongfu is not null and hongfu > 0 and is_delete = 0 and apply_status = 2 and on_sale = 1")
        ->field("name,shop_price,concat('$host',cover) as cover,sale_count,goods_id,hongfu")
        ->page($page)
        ->limit($limit)
        ->select();

        $pageSize = ceil($count/60);

        return ['goods' => $goods,'pagesize' => $pageSize];
    }

    /**
    *线下提货商品
    **/
    public function getOfflineGoods($page = 0,$limit = 60){
        $host = \app\library\SettingHelper::get("shuaibo_image_url");

        $count = M("goods")
        ->where("address is not null and is_delete = 0 and apply_status = 2 and on_sale = 1")
        ->count();

        $goods = M("goods")
        ->where("address is not null and is_delete = 0 and apply_status = 2 and on_sale = 1")
        ->field("name,shop_price,concat('$host',cover) as cover,sale_count,goods_id,address")
        ->page($page)
        ->limit($limit)
        ->select();

        $pageSize = ceil($count/60);

    return ['goods' => $goods,'pagesize' => $pageSize];
    }
}