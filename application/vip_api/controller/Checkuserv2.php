<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/7/21
 * Time: 上午10:45
 */
namespace app\vip_api\controller;
class Checkuserv2 {
    public function index() {
        $username = I('user')."lg";
        $customer = M('customer')->where(['nickname' => $username])->find();
        if (empty($customer)) {
            return ['no' => 0, 'info' => '用户不存在'];
        }
        if ($customer['active'] != 1) {
            return ['no' => 0, 'info' => '用户不可用'];
        }
        return ['no' => 1, 'info' => 'ok'];
    }
}



