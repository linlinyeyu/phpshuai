<?php
namespace app\common\model;

use think\Model;
class CategoryModel extends Model {
	public function getCategory(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
		$top_categories = $this->where(['active' => 1,'level' => 1])->field("pid,category_id,sort,name,concat('$host',icon) as icon,concat('$host',selected_icon) as selected_icon")->order("sort")->select();
		$second_categories = $this->where(['active' => 1,'level' => 2])->field("pid,category_id,sort,name")->order("sort")->select();
		$third_categories = $this->where(['active' => 1,'level' => 3])->field("pid,category_id,sort,name,concat('$host',icon) as icon")->order("sort")->select();

		foreach ($second_categories as &$second_category){
			foreach ($third_categories as $third_category){
				if($third_category['pid'] == $second_category['category_id']){
					$second_category['child_category'][] = $third_category;
				}
			}
		}

		foreach ($top_categories as &$top_category){
			foreach ($second_categories as &$second_category){
				if($second_category['pid'] == $top_category['category_id']){
					$top_category['child_category'][] = $second_category;
				}
			}
		}

		return $top_categories;
	}
	
	public function getCategoryById($category_id = 0){
		$prefix = C('database.prefix');
		$category_ids = $this
						->field("category_id")
						->union("select cc.category_id from {$prefix}category c
						left join {$prefix}category cc on cc.pid = c.category_id and cc.active = 1
						where c.category_id =".$category_id." and c.active = 1")
						->union("select c3.category_id from {$prefix}category c
						LEFT JOIN {$prefix}category cc on cc.pid = c.category_id and cc.active =1
						LEFT JOIN {$prefix}category c3 on c3.pid = cc.category_id and c3.active = 1
						where c.active = 1 and c.category_id = ".$category_id)
						->where(['category_id' => $category_id])
						->select();
		$ids = [];
		foreach ($category_ids as $id){
			$ids[] = $id['category_id'];
		}
		return $ids;
	}
	
	public function getHomeCategory(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
		
		$category = M("home_category")
		->where(['status' => 1])
		->field("category_id,img,home_category_id")
		->select();
		
		return $category;
	}

	public function getHomeCategoryById($home_category_id = 0) {

    }
}