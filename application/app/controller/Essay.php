<?php
namespace app\app\controller;
class Essay{
	
	public function get_essay(){
		$page = (int)I("page");
		$host = \app\library\SettingHelper::get("bear_image_url");
		
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		
		$essays = M("essay")
		->where(['is_deleted' => 0 ,'state' => 1])
		->field("id,concat('$host', cover, '$suffix') as cover, title,date_add")
		->order("sort desc, date_add desc")
		->limit(10)
		->page($page)
		->select();
		
		return ['errcode' => 0 ,'message' => '请求成功', 'content' => $essays];
	}
	
	
}