<?php
namespace app\app\controller;

use think\Cache;

class CategoryAction {
	//获取分类
	public function getCategory(){
		$category = S("shuaibo_categories");
		$category = unserialize($category);
		if(empty($category)){
			$category = D("category_model")->getCategory();
			S("shuaibo_categories",serialize($category));
		}
	
		return ['errcode' => 0,'message' => '请求成功','content' => $category];
	}
}