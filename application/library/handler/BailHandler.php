<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/6/5
 * Time: 下午4:13
 */
namespace app\library\handler;
class BailHandler extends Handler{

    public function orderNotify($order){

        // 缴纳保证金成功处理
        M('seller_check')
            ->where(['customer_id' => $order['customer_id']])
            ->save(['cash_deposit' => $order['order_amount'],'is_pay' => 2]);

        $customer = M("customer")
            ->alias("c")
            ->where(['c.customer_id' => $order['customer_id']])
            ->join("seller_shopinfo ss","ss.customer_id = c.customer_id")
            ->field("c.nickname,c.phone,ss.seller_id")
            ->find();
        M("seller_user")->add(array('username' => $customer['nickname'],'phone' => $customer['phone'],'seller_id' => $customer['seller_id'],'password' => md5('888888'),'status' => 1,'customer_id' => $order['customer_id']));

        // 资金明细
        M('finance')->add([
            'customer_id' => $order['customer_id'],
            'finance_type_id' => 3,
            'type' => 1,
            'amount' => $order['order_amount'],
            'date_add' => time(),
            'is_minus' => 1,
            'order_sn' => $order['order_sn'],
            'comments' => '支付保证金 '.$order['order_sn'],
            'title' => '-'.$order['order_amount'],
        ]);

        return ['errcode' => 0,'message' => 'success'];
    }
}