<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/7/17
 * Time: 上午10:48
 */
namespace app\admin\model;
use think\Model;
class AdvertisementModel extends Model {
    /**
     * 获取登录广告
     * @return mixed
     */
    public function getAds(){
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        $ads = M("advertisement")
            ->alias("ad")
            ->join("advertisement_type at","at.type_id = ad.type")
//            ->join("action a",'a.action_id = ad.action_id')
            ->where(['ad.status' => 1,'at.status' => 1])
            ->field("ad.status,at.name,concat('$host',ad.img,'$suffix') as img,ad.ad_id,ad.date_add,ad.link")
            ->select();

        return $ads;
    }
}