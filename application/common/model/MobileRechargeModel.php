<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/6/9
 * Time: 下午4:50
 */
namespace app\common\model;
use app\library\ExpressHelper;
use think\Cache;
use think\Model;
class MobileRechargeModel extends Model{
    /**
     * 话费充值
     * @param $customer_id
     * @param $phone
     * @param $id
     * @return array
     */
    public function recharge($customer_id,$phone,$id) {

        $times = (int)Cache::get("shuaibo_recharge_mobile_order_". $phone);
        if($times > 0){
            return ['errcode' => -101, 'message' => '不要重复点击'];
        }
        Cache::set("shuaibo_recharge_mobile_order_". $phone , ++$times, 3);

        $mobile_recharge = M('mobile_recharge_fee')->where(['mobile_recharge_fee_id' => $id])->field("type,amount,actual_fee")->find();
        $order_sn = createNo("mobile_recharge","order_sn","MR");
        $data = [
            'customer_id' => $customer_id,
            'mobile' => $phone,
            'order_sn' => $order_sn,
            'amount' =>$mobile_recharge['amount'],
            'total_fee' => $mobile_recharge['actual_fee'],
            'type' => $mobile_recharge['type'],
            'date_add' => time()
        ];

        $mid = M('mobile_recharge')->add($data);
        if (empty($mid)) {
            return ['errcode' => -100, 'message' => '失败'];
        }
        $balance = M('customer')->where(['customer_id' => $customer_id])->field("account,reward_amount,transfer_amount")->find();
        return ['errcode' => 0, 'message' => '成功', 'content' => ['order_sn' => $order_sn, 'balance' => $balance['account'],
            'reward_amount' => $balance['reward_amount'],'transfer_amount' => $balance['transfer_amount']]];
    }

    /**
     * 支付
     * @param $customer_id
     * @param $type
     * @param $orders
     * @return array
     */
    public function pay($customer_id,$type,$orders) {
        $times = (int)Cache::get("shuaibo_pay_mobile_order_". $orders);
        if($times > 0){
            return ['errcode' => -101, 'message' => '不要重复点击'];
        }
        Cache::set("shuaibo_pay_mobile_order_". $orders , ++$times, 3);

        $fee = M('mobile_recharge')->where(['order_sn' => $orders])->field("mobile,order_sn,amount,total_fee,type")->find();

        $helper = \app\library\order\OrderHelper::getInstance($type);

        if(empty($helper)){
            return ['errcode' => -101 , 'message' => '没有对应的支付方式'];
        }
        if(is_string($helper)){
            return ['errcode' => -102 , 'message' => $helper];
        }

        $pay_order_sn = createNo();

        $sum = $fee['total_fee'];
        $goods_amount = $fee['amount'];

        if ($fee['type'] == 1) {
            $subject = "充值话费" . $sum . "元";
            $order_type = 4;
        } else {
            $subject = "充值流量" . $sum . "元";
            $order_type = 5;
        }

        $helper->setOrderNumber($pay_order_sn)
            ->setOrderType($order_type)
            ->setGoodsAmount($goods_amount)
            ->setBody($subject)
            ->setSubject($subject);

        $res = $helper->get_init_order($customer_id,$sum);

        if($res['errcode'] < 0){
            return $res;
        }

        $order = $helper->getOrder();

        $order['foregin_infos'] = $orders;

        M("order_info")->add($order);

        M("mobile_recharge")->where(['order_sn' => $orders])->save(['out_order_no' => $order['order_sn']]);

        $return = $helper->getReturn();

        if(in_array($order['pay_id'],[1,6,7])){
            $notify = new \app\library\order\OrderNotify($order['order_sn'], $return['count'], $order['pay_id']);

            $res = $notify->notify();
            if ($res['errcode'] != 0) {
                return $res;
            }
        }

        return ['errcode' => 0, 'message'=>'请求成功', 'content' => $return];
    }
}