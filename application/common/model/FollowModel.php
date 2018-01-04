<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/6/19
 * Time: 下午2:54
 */
namespace app\common\model;

use think\Model;
class FollowModel extends Model{
    public function getFollowCount($customer_id = 0){
        $count = M("seller_follow")
            ->alias("sf")
            ->join("seller_shopinfo ss",'ss.seller_id = sf.seller_id')
            ->where(['sf.user_id' => $customer_id, 'ss.status' => 1])
            ->count();

        return $count;
    }

    public function getSellerFollow($customer_id = 0){
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $follow = M("seller_follow")
            ->alias("sf")
            ->join("seller_shopinfo ss",'ss.seller_id = sf.seller_id')
            ->where(['sf.user_id' => $customer_id, 'ss.status' => 1])
            ->field("ss.seller_id,concat('$host',ss.shop_logo) as shop_logo,ss.shop_name,ss.shop_title,ss.province,ss.city,ss.shop_address")
            ->select();

        return $follow;
    }
}