<?php
namespace app\common\model;

use think\Model;
class SellerShopinfoModel extends Model{
	public function getSellerGoods($condition = [],$sort = '',$page =1,$limit = 20){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$seller_goods = $this
		->alias("ss")
		->join("goods g",'g.seller_id = ss.seller_id')
		->join("order_comment oc",'oc.goods_id = g.goods_id',"left")
		->join("seller_category_goods scg",'scg.goods_id = g.goods_id')
		->where($condition)
		->field("concat('$host',g.cover) as cover,g.name,g.shop_price,g.sale_count,g.goods_id,((select count(oc.id) from vc_order_comment oc where oc.goods_id = g.goods_id and oc.type =1 and oc.score > 3)/(select count(oc.id) from vc_order_comment oc where oc.goods_id = g.goods_id and oc.type = 1)) as praise_rate")
		->order($sort)
		->group("g.goods_id")
		->page($page)
		->limit($limit)
		->select();
		
		$count = $this
		->alias("ss")
		->join("goods g",'g.seller_id = ss.seller_id')
		->join("order_comment oc",'oc.goods_id = g.goods_id',"left")
		->join("seller_category_goods scg",'scg.goods_id = g.goods_id')
		->where($condition)
		->group("g.goods_id")
		->count();
		
		$pagesize = ceil($count/$limit);
		
		return ['seller_goods' => $seller_goods,'pagesize' => $pagesize];
	}
	
	public function getSellerInfo($seller_id = 0){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$seller = $this
		->field("concat('$host',shop_logo) as shop_logo,shop_name,concat('$host',shop_header) as shop_header,seller_id,kf_qq")
		->where(['seller_id' => $seller_id, 'status' => 1])
		->find();
		
		return $seller;
	}
	
	public function getSellerCat($seller_id = 0){
		$top_cat = M('seller_category')
		->where(['seller_id' => $seller_id,'is_show' => 1,"level" => 1])
		->field("cat_name,seller_cat_id,pid,sort_order")
		->order("sort_order")
		->select();
		
		$second_cat = M("seller_category")
		->where(["seller_id" => $seller_id,'is_show' => 1,'level' => 2])
		->order("sort_order")
		->select();
		
		foreach ($top_cat as &$cat){
			foreach ($second_cat as $second){
				if ($second['pid'] == $cat['seller_cat_id']){
					$cat['child_category'][] = $second;
				}
			}
		}
		
		return $top_cat;
	}
	
	public function getShop($seller_id = 0){
		$seller = M("seller_shopinfo")->where(['seller_id' => $seller_id])->field("seller_id")->find();
		
		return $seller;
	}
	
	public function addFollow($data = []){
		$res = M("seller_follow")->add($data);
		
		return $res;
	}
	
	public function cancelFollow($data = []){
		$res = M("seller_follow")->where(['seller_id'=> $data['seller_id'],'user_id' => $data['user_id']])->delete();
		
		return $res;
	}
	
	public function isFollow($customer_id,$seller_id){
		$res = M("seller_follow")->where(['user_id' => $customer_id,'seller_id' => $seller_id])->find();
		if(!empty($res)){
			return 1;
		}
		return 0;
	}
	
	public function getBanner($seller_id = 0,$terminal = 1){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$banner = M("seller_shopslide")
		->alias("ss")
		->join("action a",'a.action_id = ss.action_id')
		->where(['ss.seller_id' => $seller_id,'ss.status' => 1])
		->field("concat('$host',ss.img_url) as image,ss.params,a.*")
		->select();
		
		foreach ($banner as &$b){
			\app\library\ActionTransferHelper::display($b);
		}
		
		return \app\library\ActionTransferHelper::get_terminal_params($banner, $terminal);
	}
	
	public function getChildCat($seller_cat_id = 0){
		$child_cat = M("seller_category")->where(['pid' => $seller_cat_id])->field("seller_cat_id")->select();
		$ids = [];
		if (!empty($child_cat)){
			foreach ($child_cat as $cat){
				$ids[] = $cat['seller_cat_id'];
			}
				
		}
		$ids[] = $seller_cat_id;
		return $ids;
	}

	public function getSellerInfoByGoods($goods_id = 0,$customer_id = 0) {
        $host = \app\library\SettingHelper::get("shuaibo_image_url");

        $seller = M("seller_shopinfo")
            ->alias("ss")
            ->join("goods g","g.seller_id = ss.seller_id")
            ->where(['g.goods_id' => $goods_id])
            ->field("ss.seller_id,ss.shop_name,concat('$host',ss.shop_logo) as shop_logo,case ss.type when 1 then '普通店铺' when 2 then '旗舰店' when 3 then '自营' end as type")
            ->find();
        $seller_id = $seller['seller_id'];

        $is_follow = 1;
        $follow = M("seller_follow")
            ->where(['seller_id' => $seller_id,'user_id' => $customer_id])
            ->field("id")
            ->find();

        if (empty($follow)){
            $is_follow = 0;
        }
        $total = M("order_comment")
            ->where(['is_deleted' => 0,"type" => 1])
            ->field("count(1)")
            ->buildSql();

        $praise = M("order_comment")
            ->where(['is_deleted' => 0,'score' => ["in","4,5"],'type' => 1])
            ->field("count(1)")
            ->buildSql();

        $goods_comment = M("order_comment")
            ->where(['is_deleted' => 0, 'type' => 1,'seller_id' => $seller_id])
            ->field("avg(score)")
            ->buildSql();

        $service_comment = M("order_comment")
            ->where(['is_deleted' => 0, 'type' => 2,'seller_id' => $seller_id])
            ->field("avg(service_score)")
            ->buildSql();

        $logistics_comment = M("order_comment")
            ->where(['is_deleted' => 0, 'type' => 2,'seller_id' => $seller_id])
            ->field("avg(logistics_score)")
            ->buildSql();

        $comment = M("order_comment")->alias("c")
            ->field("ifnull($total, 0) as total, ifnull($praise,0) as praise,round(ifnull($goods_comment,5),1) as goods_comment, round(ifnull($service_comment,5),1) as service_comment, round(ifnull($logistics_comment,5),1) as logistics_comment")
            ->find();
        if(empty($comment)){
            $comment = ['praise_rate' => 1, 'goods_comment' => 5.0,'service_comment' => 5.0,'logistics_comment' => 5.0];
        }else {
            if ($comment['total'] != 0) {
                $comment['praise_rate'] = round($comment['praise']/$comment['total'],2);
            } else {
                $comment['praise_rate'] = 1;
            }
        }

        return ['seller' => $seller,'is_follow' => $is_follow,'comment' => $comment];
    }
	
	public function getAppSellerInfo($seller_id = 0,$customer_id = 0){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$seller = M("seller_shopinfo")
		->where(['seller_id' => $seller_id])
		->field("seller_id,shop_name,concat('$host',shop_logo) as shop_logo,case type when 1 then '普通店铺' when 2 then '旗舰店' when 3 then '自营' end as type")
		->find();
		
		$is_follow = 1;
		$follow = M("seller_follow")
		->where(['seller_id' => $seller_id,'user_id' => $customer_id])
		->field("id")
		->find();
		
		if (empty($follow)){
			$is_follow = 0;
		}
		$total = M("order_comment")
		->where(['is_deleted' => 0,"type" => 1,'seller_id' => $seller_id])
		->field("count(1)")
		->buildSql();
		
		$praise = M("order_comment")
		->where(['is_deleted' => 0,'score' => ["in","4,5"],'type' => 1,'seller_id' => $seller_id])
		->field("count(1)")
		->buildSql();
		
		$goods_comment = M("order_comment")
		->where(['is_deleted' => 0, 'type' => 1,'seller_id' => $seller_id])
		->field("avg(score)")
		->buildSql();
			
		$service_comment = M("order_comment")
		->where(['is_deleted' => 0, 'type' => 2,'seller_id' => $seller_id])
		->field("avg(service_score)")
		->buildSql();
			
		$logistics_comment = M("order_comment")
		->where(['is_deleted' => 0, 'type' => 2,'seller_id' => $seller_id])
		->field("avg(logistics_score)")
		->buildSql();
			
		$comment = M("order_comment")->alias("c")
		->field("ifnull($total, 0) as total, ifnull($praise,0) as praise,round(ifnull($goods_comment,5),1) as goods_comment, round(ifnull($service_comment,5),1) as service_comment, round(ifnull($logistics_comment,5),1) as logistics_comment")
				->find();
		if(empty($comment)){
			$comment = ['praise_rate' => 1, 'goods_comment' => 5.0,'service_comment' => 5.0,'logistics_comment' => 5.0];
		}else {
            if ($comment['total'] != 0) {
                $comment['praise_rate'] = round($comment['praise']/$comment['total'],2);
            } else {
                $comment['praise_rate'] = 1;
            }
		}
		
		return ['seller' => $seller,'is_follow' => $is_follow,'comment' => $comment];
	}
}