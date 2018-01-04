<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/6/19
 * Time: 下午2:54
 */
namespace app\common\model;

use think\Model;
class TrailModel extends Model{
    public function getTrailCount($customer_id = 0){
        $count = $this
            ->where(['user_id' => $customer_id])
            ->count();

        return $count;
    }

    public function getTrailList($customer_id = 0){
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $trail_list = $this
            ->alias("t")
            ->join("goods g",'g.goods_id = t.goods_id')
            ->field("g.goods_id,g.name,concat('￥',g.shop_price) as shop_price,concat('$host',g.cover) as cover")
            ->where(['t.user_id' => $customer_id])
            ->select();

        return $trail_list;
    }
}