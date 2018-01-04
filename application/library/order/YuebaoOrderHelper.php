<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/12/27
 * Time: 15:34
 */
namespace app\library\order;

class YuebaoOrderHelper extends OrderHelper {
    public $pay_id = 6;

    public $pay_name = "余额宝支付";
    public function pay_params($customer_id)
    {
        $this->account_pay($customer_id);
    }
}
