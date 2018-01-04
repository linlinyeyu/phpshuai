<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/7/17
 * Time: 上午11:56
 */
namespace app\common\model;
use think\Model;
use app\library\ActionTransferHelper;
class AdvertisementModel extends Model{
    /**
     *获取所有的横幅 信息
     * @param number $position
     */
    public function GetAds($position = 0, $t = 0, $width = 1200, $height = 600){
        $ad = S("shuaibo_ad_".$position );
        if(empty($ad)){
            $ad = $this->SetAds($position, $width , $height);
        }
        $ad = unserialize($ad);
        return ActionTransferHelper::get_terminal_params($ad, $t);
    }

    public function SetAds($position = 0, $width = 1200, $height = 600){

        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix($width, $height);

        $ads = M("advertisement")
            ->alias("ad")
            ->join("action a","a.action_id = ad.action_id","LEFT")
            ->field("a.action_id,a.need_login,ad.link, concat('$host',ad.img,'{$suffix}') as image ,a.ios_param,a.android_param,a.web_param, a.wap_param")
            ->where(['ad.type' => $position])
            ->order("ad.sort")
            ->where(['ad.status' => 1])
            ->select();
        $ads = serialize($ads);
        S("shuaibo_ad_".$position, $ads);
        return $ads;
    }
}