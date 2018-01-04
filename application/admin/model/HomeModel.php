<?php
namespace app\admin\model;

use think\Model;
class HomeModel extends Model{
	public function getHomeActivity(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		$activity = M("home_activity")
		->alias("hc")
		->join("special s","s.special_id = hc.special_id")
		->join("special_type st",'st.type = s.type')
		->join("action a",'a.action_id = hc.action_id')
		->where(['s.status' => 1,'st.status' => 1])
		->field("hc.status,st.name,concat('$host',hc.img,'$suffix') as img,s.special_id,hc.home_activity_id,hc.date_add,a.name as action_name")
		->select();
		
		return $activity;
	}
	
	public function getActivityById($activity_id = 0){
		$activity = M("home_activity")
		->alias("hc")
		->join("special s","s.special_id = hc.special_id")
		->join("special_type st",'st.type = s.type')
		->join("action a","a.action_id = hc.action_id")
		->where(['s.status' => 1,'st.status' => 1,'hc.home_activity_id' => $activity_id])
		->field("hc.status,st.name,hc.img,s.special_id,hc.home_activity_id,hc.date_add,a.action_id")
		->find();
		
		return $activity;
	}
	
	public function saveActivity($data){
		$res = M("home_activity")->where(['home_activity_id' => $data['home_activity_id']])->save($data);
		return $res;
	}
	
	public function addActivity($data){
		$res = M("home_activity")->add($data);
		return $res;
	}
	
	public function isOpen($activity_id = 0,$status = 0){
		$res = M("home_activity")->where(['home_activity_id' => $activity_id])->save(['status' => $status]);
		return $res;
	}
	
	public function toDel($activity_id = 0){
		$res = M("home_activity")->where(['home_activity_id' => $activity_id])->delete();
		
		return $res;
	}
	
	public function getActivity(){
		$activity = M("special")
		->alias("s")
		->join("special_type st","st.type = s.type")
		->field("s.special_id,st.name,s.status")
		->where(['s.status' => 1,'st.status' => 1])
		->select();
		
		return $activity;
	}
	
	public function getHomeCategory(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		$categories = M('home_category')
		->alias("hc")
		->join("category c",'c.category_id = hc.category_id')
		->where(['c.active' => 1,'c.level' => 1])
		->field("concat('$host',hc.img,'$suffix') as img,c.name,hc.status,hc.date_add,hc.category_id,hc.home_category_id")
		->select();
		
		return $categories;
	}
	
	public function getCategoryById($category_id = 0){
		$category = M('home_category')
		->alias("hc")
		->join("category c",'c.category_id = hc.category_id')
		->where(['c.active' => 1,'c.level' => 1,'hc.home_category_id' => $category_id])
		->field("hc.img,c.name,hc.status,hc.category_id,hc.home_category_id")
		->find();
		
		return $category;
	}
	
	public function getCategory(){
		$categories = M("category")
		->where(['active' => 1,'level' => 1])
		->field("category_id,name")
		->select();
		
		return $categories;
	}
	
	public function saveCategory($data){
		$res = M("home_category")->where(['home_category_id' => $data['home_category_id']])->save($data);
		
		return $res;
	}
	
	public function addCategory($data){
		$res = M("home_category")->add($data);
		
		return $res;
	}
	
	public function isCatOpen($activity_id,$status){
		$res = M("home_category")->where(['home_category_id' => $activity_id])->save(['status' => $status]);
		
		return $res;
	}
	
	public function delCat($category_id = 0){
		$res = M("home_category")->where(['home_category_id' => $category_id])->delete();
		return $res;	
	}
	
	public function getEveryGoods(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		$goods = M("everyday_newgoods")
		->alias("en")
		->join("goods g","g.goods_id = en.goods_id")
		->join("seller_shopinfo ss",'ss.seller_id = g.seller_id')
		->where(['en.status' => 1,'g.is_delete' => 0,'g.on_sale' => 1,'ss.status' => 1])
		->field("concat('$host',g.cover,'$suffix') as cover,g.goods_id,g.name,ss.shop_name,en.date_add,en.everyday_newgoods_id")
		->select();
		
		return $goods;
	}
	
	public function addNew($data){
		$res = M("everyday_newgoods")
		->add($data);
		
		return $res;
	}
	
	public function delNew($id){
		$res = M('everyday_newgoods')
		->where(['everyday_newgoods_id' => $id])
		->save(['status' => 0]);
		
		return $res;
	}
	
	public function getAppHomeActivity(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		$activity = M("app_home_activity")
		->alias("hc")
		->join("special s","s.special_id = hc.special_id")
		->join("special_type st",'st.type = s.type')
		->join("action a",'a.action_id = hc.action_id')
		->where(['s.status' => 1,'st.status' => 1])
		->field("hc.status,st.name,concat('$host',hc.img,'$suffix') as img,s.special_id,hc.home_activity_id,hc.date_add,a.name as action_name")
		->select();
		
		return $activity;
	}
	
	public function getAppActivityById($activity_id = 0){
		$activity = M("app_home_activity")
		->alias("hc")
		->join("special s","s.special_id = hc.special_id")
		->join("special_type st",'st.type = s.type')
		->join("action a","a.action_id = hc.action_id")
		->where(['s.status' => 1,'st.status' => 1,'hc.home_activity_id' => $activity_id])
		->field("hc.status,st.name,hc.img,s.special_id,hc.home_activity_id,hc.date_add,a.action_id")
		->find();
	
		return $activity;
	}
	
	public function isAppOpen($activity_id = 0,$status = 0){
		$res = M("app_home_activity")->where(['home_activity_id' => $activity_id])->save(['status' => $status]);
		return $res;
	}
	
	public function toAppDel($activity_id = 0){
		$res = M("app_home_activity")->where(['home_activity_id' => $activity_id])->delete();
	
		return $res;
	}
	
	public function addAppActivity($data){
		$res = M("app_home_activity")->add($data);
		return $res;
	}
	
	public function saveAppActivity($data){
		$res = M("app_home_activity")->where(['home_activity_id' => $data['home_activity_id']])->save($data);
		
		return $res;
	}
}