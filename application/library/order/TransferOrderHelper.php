<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/12/27
 * Time: 15:38
 */
namespace app\library\order;

class TransferOrderHelper extends OrderHelper{
    public $pay_id = 7;

    public $pay_name = "转账金支付";
    public function pay_params($customer_id)
    {
        $this->account_pay($customer_id);
    }
}