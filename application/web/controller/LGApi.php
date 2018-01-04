<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/7/20
 * Time: 上午10:31
 */
namespace app\web\controller;

class LGApi{
    public function recharge() {
        $method = I('method_name');
        $userid = I('userid');
        $sign = I('sign');
        $intrgration = round(I('integration'),2);
        $shopping_coin = round(I('shopping_coin'),2);
        $data = [
            'method_name' => $method,
            'userid' => $userid,
            'sign' => $sign,
            'integration' => $intrgration,
            'shopping_coin' => $shopping_coin
        ];
        $api = new \app\api\LGApi();
        $res = $api->recharge($data);
        return $res;
    }
}