<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/8/7
 * Time: 下午12:00
 */
namespace app\web\controller;
use  think\Cache;
class BottomAction {
    public function buy_process() {
        $buy_process = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_buy_process"), ENT_QUOTES) ;

        return ['errcode' => 0, 'message' => "成功", 'content' => $buy_process];
    }

    public function after_sale() {
        $after_sale = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_after_sale"), ENT_QUOTES) ;

        return ['errcode' => 0, 'message' => "成功", 'content' => $after_sale];
    }

    public function refund_info() {
        $refund_info = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_refund_info"), ENT_QUOTES) ;

        return ['errcode' => 0, 'message' => "成功", 'content' => $refund_info];
    }

    public function return_goods() {
        $return_goods = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_return_goods"), ENT_QUOTES) ;

        return ['errcode' => 0, 'message' => "成功", 'content' => $return_goods];
    }

    public function cancel_order() {
        $cancel_order = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_cancel_order"), ENT_QUOTES) ;

        return ['errcode' => 0, 'message' => "成功", 'content' => $cancel_order];
    }

    public function seven_day() {
        $seven_day = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_seven_day"), ENT_QUOTES) ;

        return ['errcode' => 0, 'message' => "成功", 'content' => $seven_day];
    }
}