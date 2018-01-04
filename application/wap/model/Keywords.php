<?php
namespace app\app\model;
use think\Model;
class Keywords extends Model{
	public function GetHotKeys(){
		return $this->where(array("type" => 0))->order("sort")->getField('key_name',true);
	}
}