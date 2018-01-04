<?php
namespace app\common\model;
use app\library\ActionTransferHelper;
use think\Model;

class InitModel extends Model{
	public function getEveryNewGoods($page = 1,$t = 0,$pageSize = 10){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
//		$begintime=date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d'),date('Y')));
//		$endtime=date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1);
//
//		$goods = M("goods")
//		->alias("g")
//		->where(['g.is_delete' => 0,'g.on_sale' => 1,'g.apply_status' => 2,'g.date_on_sale' => [['egt',strtotime($begintime)],['elt',strtotime($endtime)]]])
//		->field("g.goods_id,g.name,g.shop_price,(sale_count+virtual_count) as sale_count,concat('$host',g.cover) as cover")
//		->limit(8)
//		->select();

//        $goods = M('special_images')
//            ->alias("si")
//            ->where(['si.status' => 1, 'si.special_id' => 8])
//            ->join("action a","a.action_id = si.action_id")
//            ->field("si.name,concat('$host',si.image) as cover,si.params,a.action_id,a.need_login,a.ios_param,a.android_param,a.web_param, a.wap_param")
//            ->order("si.sort DESC")
//            ->limit(8)
//            ->select();
//        foreach ($goods as &$good) {
//            $params = unserialize($good['params']);
//            $tmp_good = M("goods")
//                ->alias("g")
//                ->where(['g.goods_id' => $params['goods_id'],'g.is_delete' => 0,'g.on_sale' => 1,'g.apply_status' => 2])
//                ->field("g.goods_id,g.name,g.shop_price,(sale_count+virtual_count) as sale_count,concat('$host',g.cover) as cover")
//                ->find();
//            $good = array_merge($tmp_good,$good);
//            ActionTransferHelper::display($good);
//        }
//        $goods = ActionTransferHelper::get_terminal_params($goods, $t);

        $title = M('special_type')->where(['type' => 8])->getField("name");

        $goods = M("goods")
            ->alias("g")
            ->join('everyday_newgoods en',"en.goods_id = g.goods_id")
            ->where(['en.status' => 1,'g.is_delete' => 0,'g.on_sale' => 1,'g.apply_status' => 2])
            ->field("g.goods_id,g.name,g.shop_price,(sale_count+virtual_count) as sale_count,concat('$host',g.cover) as cover")
            ->page($page)
            ->limit($pageSize)
            ->order('en.sort asc')
            ->select();
		return ['goods' => $goods,'nav_title' => $title];
	}

	public function getWebEveryNewGoods($page){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$count = M("goods")
        ->alias("g")
        ->join('everyday_newgoods en',"en.goods_id = g.goods_id")
        ->where(['en.status' => 1,'g.is_delete' => 0,'g.on_sale' => 1,'g.apply_status' => 2])
        ->count();

		$goods = M("goods")
            ->alias("g")
            ->join('everyday_newgoods en',"en.goods_id = g.goods_id")
            ->where(['en.status' => 1,'g.is_delete' => 0,'g.on_sale' => 1,'g.apply_status' => 2])
            ->field("g.goods_id,g.name,g.shop_price,(sale_count+virtual_count) as sale_count,concat('$host',g.cover) as cover,g.market_price")
            ->page($page)
            ->order("en.sort ASC")
            ->limit(24)
            ->select();
        $pagesize = ceil($count/24);
		return ['goods' => $goods,'pagesize' => $pagesize];
	}
	
	public function getHomeActivity(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
//		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
		
		$home_activity = M("home_activity")
		->alias("hc")
		->join("special s",'s.special_id = hc.special_id')
		->join("special_type st",'st.type = s.type')
		->join("action a",'a.action_id = hc.action_id')
		->where(['hc.status' => 1,'s.status' => 1,'st.status' => 1])
		->field("hc.special_id,concat('$host',hc.img) as img,st.type,a.web_param,s.sort")
		->select();
		
		return $home_activity;
	}
	
	public function getAppHomeActivity(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		
		$home_activity = M("app_home_activity")
		->alias("hc")
		->join("special s",'s.special_id = hc.special_id')
		->join("special_type st",'st.type = s.type')
		->join("action a",'a.action_id = hc.action_id')
		->where(['hc.status' => 1,'s.status' => 1,'st.status' => 1])
		->field("hc.special_id,concat('$host',hc.img) as img,st.type,st.name as nav_title,a.*")
		->select();
		
		return $home_activity;
	}
	
	public function getKeywordList(){
		$keyword = M("keyword")
		->where(['status' =>1])
		->field("keyword_id,keyword")
		->order("sort")
		->select();
		
		return $keyword;
	}
	
	public function getTrail($customer_id){
		$trail = M("trail")
		->alias("t")
		->join("category_goods cg",'cg.goods_id = t.goods_id')
		->where(['t.user_id' => $customer_id])
		->field("cg.category_id")
		->order("date_add")
		->limit(3)
		->select();
		
		return $trail;
	}
	
	public function getAd(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$ad = M("essay")->where(['state' => 1,'is_deleted' => 0])->field("title,id,concat('$host',cover),content")->select();
		
		return $ad;
	}
}