<?php
namespace app\common\model;
use think\Model;
use app\library\ActionTransferHelper;
class Activity extends Model{

	public function GetActivity( $t = 0){
		$activity = S("bear_activity");
		if(empty($activity)){
			$activity = $this->SetActivity();
		}
		$activity = unserialize($activity);
		
		return ActionTransferHelper::get_terminal_params($activity, $t);
	}
	
	public function SetActivity(){
		$host = \app\library\SettingHelper::get("bear_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(1200, 400);
		
		$activity = M("activity")->alias("ac")
		->join("action a","a.action_id = ac.action_id")
		->field("ac.params,a.need_login,a.action_id, ac.name, concat('$host',ac.image,'{$suffix}') as image , a.ios_param,a.android_param,a.web_param, a.wap_param")
		->order("ac.sort")
		->where(['status' => 1])
		->select();
		foreach ($activity as $k => $ac){
			ActionTransferHelper::display($ac);
			$activity[$k] = $ac;
		}
		$activity = serialize($activity);
		S("bear_activity", $activity);
		return $activity;
	}
	
}