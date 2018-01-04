<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/8/7
 * Time: 上午10:58
 */
namespace app\admin\controller;
use app\admin\Admin;
class Bottomsetting extends Admin {
    public function buy_index() {
        if(IS_POST){
            $buy_process = htmlspecialchars(I('post.editorValue'), ENT_QUOTES);
            \app\library\SettingHelper::set("shuaibo_buy_process", $buy_process);
            $this->success("修改成功");
        }
        $buy_process = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_buy_process"), ENT_QUOTES) ;
        $this->assign("buy_process", $buy_process);
        $this->display();
    }

    public function aftersale_index() {
        if(IS_POST){
            $after_sale = htmlspecialchars(I('post.editorValue'), ENT_QUOTES);
            \app\library\SettingHelper::set("shuaibo_after_sale", $after_sale);
            $this->success("修改成功");
        }
        $after_sale = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_after_sale"), ENT_QUOTES) ;
        $this->assign("after_sale", $after_sale);
        $this->display();
    }

    public function refund_index() {
        if(IS_POST){
            $refund = htmlspecialchars(I('post.editorValue'), ENT_QUOTES);
            \app\library\SettingHelper::set("shuaibo_refund_info", $refund);
            $this->success("修改成功");
        }
        $refund = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_refund_info"), ENT_QUOTES) ;
        $this->assign("refund", $refund);
        $this->display();
    }

    public function return_index() {
        if(IS_POST){
            $return = htmlspecialchars(I('post.editorValue'), ENT_QUOTES);
            \app\library\SettingHelper::set("shuaibo_return_goods", $return);
            $this->success("修改成功");
        }
        $return = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_return_goods"), ENT_QUOTES) ;
        $this->assign("return", $return);
        $this->display();
    }

    public function cancel_index() {
        if(IS_POST){
            $cancel = htmlspecialchars(I('post.editorValue'), ENT_QUOTES);
            \app\library\SettingHelper::set("shuaibo_cancel_order", $cancel);
            $this->success("修改成功");
        }
        $cancel = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_cancel_order"), ENT_QUOTES) ;
        $this->assign("cancel", $cancel);
        $this->display();
    }

    public function seven_index() {
        if(IS_POST){
            $seven = htmlspecialchars(I('post.editorValue'), ENT_QUOTES);
            \app\library\SettingHelper::set("shuaibo_seven_day", $seven);
            $this->success("修改成功");
        }
        $seven = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_seven_day"), ENT_QUOTES) ;
        $this->assign("seven", $seven);
        $this->display();
    }
}