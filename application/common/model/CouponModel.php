<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/6/7
 * Time: 下午3:16
 */

namespace app\common\model;
use think\Model;
use app\library\ActionTransferHelper;
class CouponModel extends Model{

    /**
     * 获取店铺优惠券
     * @param $customer_id
     * @return array
     */
    public function getShopCoupons($customer_id,$seller_id = 0) {
        $coupons = M('coupon')
            ->alias("c")
            ->join("coupon_goods cg","cg.coupon_id = c.coupon_id")
            ->join("goods g","g.goods_id = cg.goods_id")
            ->where(['c.active' => 1,"g.seller_id" => $seller_id])
            ->field("c.coupon_id,c.name,c.limit,c.amount,c.use_total")
            ->select();

        if (empty($customer_id) || $customer_id <= 0) {
            return ['errcode' => 0, 'message' => '成功', 'content' => $coupons];
        }

        $customer_coupons = M('customer_coupon')
            ->where(['customer_id' => $customer_id])
            ->field("COUNT(customer_coupon_id) as count,coupon_id")
            ->group("coupon_id")
            ->select();
        foreach ($coupons as &$coupon) {
            $coupon['is_use'] = true;
            foreach ($customer_coupons as $customer_coupon) {
                if ($customer_coupon['coupon_id'] == $coupon['coupon_id']) {
                    if ($customer_coupon['count'] >= $coupon['use_total']) {
                        $coupon['is_use'] = false;
                    }
                }
            }
        }
        return ['errcode' => 0, 'message' => '成功', 'content' => $coupons];
    }

    public function getGoodsCoupons($customer_id,$goods_id) {
        $coupons = M('coupon_goods')
            ->alias('cg')
            ->join("coupon c","c.coupon_id = cg.coupon_id")
            ->where(['c.active' => 1,'cg.goods_id' => $goods_id])
            ->field("c.coupon_id,c.name,c.limit,c.amount,c.use_total")
            ->select();

//        $coupons = M('coupon')
//            ->where(['active' => 1])
//            ->field("coupon_id,name,limit,amount,use_total")
//            ->select();

        if (empty($customer_id) || $customer_id <= 0) {
            return ['errcode' => 0, 'message' => '成功', 'content' => $coupons];
        }

        $customer_coupons = M('customer_coupon')
            ->where(['customer_id' => $customer_id])
            ->field("COUNT(customer_coupon_id) as count,coupon_id")
            ->group("coupon_id")
            ->select();
        foreach ($coupons as &$coupon) {
            $coupon['is_use'] = true;
            foreach ($customer_coupons as $customer_coupon) {
                if ($customer_coupon['coupon_id'] == $coupon['coupon_id']) {
                    if ($customer_coupon['count'] >= $coupon['use_total']) {
                        $coupon['is_use'] = false;
                    }
                }
            }
        }
        return ['errcode' => 0, 'message' => '成功', 'content' => $coupons];
    }

    /**
     * 领取优惠券
     * @param $customer_id
     * @param $coupon_id
     * @return array
     */
    public function receiveCoupon($customer_id,$coupon_id) {
        $coupon = M('coupon')->where(['coupon_id' => $coupon_id])->find();
        if ($coupon['active'] != 1) {
            return ['errcode' => -101, 'message' => '该优惠券已不可用'];
        }
        $count = M('customer_coupon')->where(['coupon_id' => $coupon_id, 'customer_id' => $customer_id])->count();
        if ($coupon['use_total'] <= $count) {
            return ['errcode' => -102, 'message' => '您已领取过该优惠券'];
        }
        $id = M('customer_coupon')->add([
            'coupon_id' => $coupon_id,
            'customer_id' => $customer_id,
            'date_add' => time(),
            'date_start' => strtotime(date("Y-m-d"),time()),
            'date_end' => strtotime(date("Y-m-d"),time()) + $coupon['date_expire'],
        ]);
        if (empty($id)) {
            return ['errcode' => -103, 'message' => '领取失败'];
        }

        $client = \app\library\message\MessageClient::getInstance();
        $message = new \app\library\message\Message();
        $message->setAction(\app\library\message\Message::ACTION_COUPON_LIST)
            ->setPlatform([\app\library\message\Message::PLATFORM_MESSAGE])
            ->setTargetIds($customer_id)
            ->setTemplate("coupon")
            ->setExtras(['title' => '恭喜您获得了一张优惠券','content' => '恭喜您获得了一张优惠券']);
        $client->send($message);

        return ['errcode' => 0, 'message' => '领取成功'];
    }
}