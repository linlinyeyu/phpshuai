<?php
namespace app\common\model;
use think\Model;
use app\library\ActionTransferHelper;
class HomeTagModel extends Model{

	public function GetTags( $t = 0,$is_app = 0,$position=0){
		$tags = null;
		if ($is_app == 1){
			$tags = S("shuaibo_tags".$position);
		}else {
			$tags = S("shuaibo_tags");
		}
		if(empty($tags)){
			$tags = $this->SetTags($is_app,$position);
		}
		$tags = unserialize($tags);
		
		return ActionTransferHelper::get_terminal_params($tags, $t);
	}
	
	public function SetTags($is_app = 0,$position = 0){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");		
		
		$tags = M("home_tag")->alias("ht")
		->join("action a","a.action_id = ht.action_id")
		->field("ht.params,a.need_login,a.action_id, ht.name, concat('$host',ht.icon) as image , a.ios_param,a.android_param,a.web_param, a.wap_param")
		->order("ht.sort")
		->where(['ht.status' => 1,"ht.is_app" => $is_app,"ht.position" => $position])
		->select();
		foreach ($tags as $k => $ta){
		    $params = unserialize($ta['params']);
		    $params['nav_title'] = $ta['name'];
            $ta['params'] = serialize($params);
			ActionTransferHelper::display($ta);
			$tags[$k] = $ta;
		}
		$tags = serialize($tags);
		if ($is_app == 1){
			S("shuaibo_tags".$position, $tags);
		}else {
			S("shuaibo_tags");
		}
		
		return $tags;
	}
	
}