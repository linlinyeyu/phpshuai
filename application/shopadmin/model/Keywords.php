<?php 
namespace app\shopadmin\model;

use think\Model;

class Keywords extends Model
{
	
	
	public function GetKeyName($id){
		if($id > 0){
			$key = $this->where(['key_id' => $id])->find();
			if(!empty($key)){
				return $key['key_name'];
			}
		}
	}
}