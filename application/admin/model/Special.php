<?php
namespace app\admin\model;

use think\Model;
use app\library\ActionTransferHelper;
class Special extends Model{
	public function getIndex($special_id = 1){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$activity = M("special_images")
		->alias("si")
		->join("action a",'a.action_id = si.action_id',"left")
		->where(['si.special_id' => $special_id])
		->field("concat('$host',si.image) as image,a.name as action_name,si.sort,si.special_images_id,si.name,a.action_id,si.params,si.status")
		->select();
		
		\app\library\ActionTransferHelper::transfer($activity);
		
		return $activity;
	}
	
	public function getSpecial($special_images_id = 0){
		$special = M("special_images")
		->alias("si")
		->join("action a",'a.action_id = si.action_id',"LEFT")
		->where(['si.special_images_id' => $special_images_id])
		->field("si.image,a.action_id,si.sort,si.special_images_id,si.name,si.status,si.params")
		->find();
		
		return $special;
	}
	
	public function del($special_images_id = 0){
		$res = M("special_images")
		->where(['special_images_id' => $special_images_id])
		->delete();
		
		return $res;
	}
	
	public function getSpecialGoods($special_id = 0,$map){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
		
		$count = M("special_goods")
		->alias("sg")
		->join("goods g",'g.goods_id = sg.goods_id')
		->where($map)
		->count();
		
		$page = new \com\Page($count,15);
		
		$goods = M("special_goods")
		->alias("sg")
		->join("goods g",'g.goods_id = sg.goods_id')
		->join("seller_shopinfo ss",'ss.seller_id = g.seller_id and ss.status = 1',"left")
		->where($map)
		->field("g.name,concat('$host',IFNULL(sg.image,g.cover),'$suffix') as cover,sg.*,ifnull(ss.shop_name,'未找到关联店铺') as shop_name")
		->limit($page->firstRow . ',' . $page->listRows)
		->order('sg.sort ASC')
		->select();
		
		return ['goods' => $goods,'page' => $page];
	}

	public function getHomeCategoryGoods($home_category_id = 0,$map) {
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);

        $count = M("home_category_goods")
            ->alias("hcg")
            ->join("goods g",'g.goods_id = hcg.goods_id')
            ->where($map)
            ->count();

        $page = new \com\Page($count,15);

        $goods = M("home_category_goods")
            ->alias("hcg")
            ->join("goods g",'g.goods_id = hcg.goods_id')
            ->join("seller_shopinfo ss",'ss.seller_id = g.seller_id and ss.status = 1',"left")
            ->where($map)
            ->field("g.name,concat('$host',IFNULL(hcg.image,g.cover),'$suffix') as cover,hcg.*,ifnull(ss.shop_name,'未找到关联店铺') as shop_name")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        return ['goods' => $goods,'page' => $page];
    }

    public function getAppHomeActivityGoods($home_category_id = 0,$map) {
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);

        $count = M("app_home_activity_goods")
            ->alias("ahag")
            ->join("goods g",'g.goods_id = ahag.goods_id')
            ->where($map)
            ->count();

        $page = new \com\Page($count,15);

        $goods = M("app_home_activity_goods")
            ->alias("ahag")
            ->join("goods g",'g.goods_id = ahag.goods_id')
            ->join("seller_shopinfo ss",'ss.seller_id = g.seller_id and ss.status = 1',"left")
            ->where($map)
            ->field("g.name,concat('$host',IFNULL(ahag.image,g.cover),'$suffix') as cover,ahag.*,ifnull(ss.shop_name,'未找到关联店铺') as shop_name")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        return ['goods' => $goods,'page' => $page];
    }
}