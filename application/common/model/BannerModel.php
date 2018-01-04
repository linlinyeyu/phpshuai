<?php
namespace app\common\model;
use think\Model;
use app\library\ActionTransferHelper;
class BannerModel extends Model{
/**
 *获取所有的横幅 信息
 * @param number $position
 */
	public function GetBanners($position = 0, $t = 0, $width = 1200, $height = 600){
		$banner = S("shuaibo_banner_".$position );
		if(empty($banner)){
			$banner = $this->SetBanners($position, $width , $height);
		}
		$banner = unserialize($banner);
		return ActionTransferHelper::get_terminal_params($banner, $t);
	}
	
	public function SetBanners($position = 0, $width = 1200, $height = 600){
		
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(1200, 600);
		
		$banners = M("banner")->alias("b")
		->join("action a","a.action_id = b.action_id")
		->field("a.action_id,a.need_login,b.params, concat('$host',b.image) as image ,a.ios_param,a.android_param,a.web_param, a.wap_param")
		->where(['b.position' => $position])
		->order("b.sort")
		->where(['status' => 1])
		->select();
		foreach ($banners as &$b){
			ActionTransferHelper::display($b);
		}
		$banners = serialize($banners);
		S("shuaibo_banner_".$position, $banners);
		return $banners;
	}
}