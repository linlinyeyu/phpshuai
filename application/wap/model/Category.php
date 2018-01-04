<?php
namespace app\app\model;
use think\Model;
class Category extends Model{

	public function GetCates(){
		$host = C("host");
		$condition = array(
			'active' => 1
			);
		return $this
		->field("category_id as cate_id,name as cate_name, concat('$host',icon) as cate_image")
		->order("sort")
		->where(array("active" => 1))
		->select($condition);
	}
}