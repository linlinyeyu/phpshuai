<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/8/10
 * Time: 下午7:17
 */
namespace app\vip_api\controller;
class Editpassv2 {
    public function index() {
        $username = I('user')."lg";
        $passwd = I('pass');
        if (strlen($passwd) < 6) {
            return ['no' => 0, 'info' => '密码不能小于6位'];
        }
        $customer = M('customer')->where(['nickname' => $username])->find();
        if (empty($customer)) {
            return ['no' => 0, 'info' => '用户不存在'];
        }
        if ($customer['active'] != 1) {
            return ['no' => 0, 'info' => '用户不可用'];
        }
        $new_passwd = md5(sha1($passwd));
        M('customer')->where(['customer_id' => $customer['customer_id']])->save(['passwd' => $new_passwd]);
        return ['no' => 1, 'info' => 'ok'];
    }
}