<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/8/1
 * Time: 下午4:40
 */
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Commission extends Admin {
    public function index() {
        $seller_id = session("sellerid");
        $map['ss.seller_id'] = $seller_id;

        // 全部
        $condition = [
            'op1.finance_type' => 1,
            'op1.is_minus' => 2,
            'o.order_state' => ['in','2,3,4,5,7']
        ];
        $condition['_string'] = "op1.customer_id = fo.customer_id";
        $sql1 = M('finance_op')
            ->alias("op1")
            ->where($condition)
            ->join("order o","o.order_sn = op1.order_sn")
            ->field("IFNULL(sum(real_amount),0)")
            ->buildSql();

        // 可提现
        $condition = [
            'op1.finance_type' => 1,
            'op1.is_minus' => 2,
            'o.order_state' => ['in','4,5,7'],
            'cwo.state' => 1
        ];
        $condition['_string'] = "op1.customer_id = fo.customer_id";
        $sql2 = M('finance_op')
            ->alias("op1")
            ->where($condition)
            ->join("order o","o.order_sn = op1.order_sn")
            ->join('customer_withdraw_order cwo','cwo.order_sn = op1.order_sn')
            ->field("IFNULL(sum(real_amount),0)")
            ->buildSql();

        $settlement = M("finance_op")
            ->alias("fo")
            ->join("seller_shopinfo ss","ss.customer_id = fo.customer_id")
            ->join("customer c",'c.customer_id = fo.customer_id')
            ->where($map)
            ->field("ss.shop_name,c.phone,fo.customer_id,
				ifnull((select sum(amount) from vc_finance_op op1 where op1.finance_type = 1 and op1.is_minus = 2 and op1.customer_id = fo.customer_id),0) as amount,
				ifnull($sql1,0) as real_amount,
				ifnull((select sum(amount) from vc_finance_op op1 where op1.finance_type = 5 and is_minus = 1 and op1.customer_id = fo.customer_id),0) as withdrawals_amount,
				ifnull($sql2,0) as available_amount
				")
            ->group("fo.customer_id")
            ->find();

        $map = [
            'cwo.customer_id' => $settlement['customer_id'],
            'o.order_state' => ['in','1,2,3,4,5,7'],
            'cwo.order_amount' => ['gt',0]
        ];
//        $map['_string'] = " (orr.state is null OR orr.state in (2,6)) ";

        $count = M("customer_withdraw_order")
            ->alias('cwo')
            ->join("order o","o.order_sn = cwo.order_sn")
//            ->join("order_return orr","orr.order_id = o.id","LEFT")
            ->where($map)
            ->count();
        $page = new \com\Page($count,15);

        $withdraw_orders = M('customer_withdraw_order')
            ->alias("cwo")
            ->join("order o","o.order_sn = cwo.order_sn")
//            ->join("order_return orr","orr.order_id = o.id","LEFT")
            ->where($map)
            ->field("cwo.date_add,cwo.order_sn,cwo.order_amount,cwo.commission_amount,cwo.state")
            ->order("cwo.date_add DESC")
            ->limit($page->firstRow.",".$page->listRows)
            ->select();
        foreach ($withdraw_orders as &$order) {
            $order_sn = $order['order_sn'];
            $order['goods'] = M('order')
                ->alias("o")
                ->where(['o.order_sn' => $order_sn])
                ->join("order_goods og","og.order_id = o.id")
                ->join("goods g","g.goods_id = og.goods_id")
                ->field("g.name as goods_name,og.price,og.quantity")
                ->select();
        }

        $this->assign("settlement",$settlement);
        $this->assign("withdrawals",$withdraw_orders);
        $this->assign("page",$page->show());
        $this->display();
    }

    public function excel() {
        $seller_id = session("sellerid");
        $customer_id = M('seller_user')->where(['seller_id' => $seller_id])->getField("customer_id");
        $map = [
            'cwo.customer_id' => $customer_id,
            'o.order_state' => ['in','1,2,3,4,5,7'],
            'cwo.order_amount' => ['gt',0]
        ];
        $lists = M('customer_withdraw_order')
            ->alias("cwo")
            ->join("order o","o.order_sn = cwo.order_sn")
            ->join("order_goods og","og.order_id = o.id")
            ->join("goods g","g.goods_id = og.goods_id")
            ->join("customer c","c.customer_id = o.customer_id")
            ->where($map)
            ->field("o.order_sn,FROM_UNIXTIME(o.date_add) as date_add,GROUP_CONCAT( g.name, '×', og.quantity) as goods_names,c.nickname,cwo.order_amount,cwo.commission_amount,".
                "CASE cwo.state WHEN 1 THEN '可提现' WHEN 2 THEN '申请中' WHEN 3 THEN '已提现' WHEN 4 THEN '已完成' ELSE '待结算' END")
            ->order("cwo.date_add DESC")
            ->group("o.id")
            ->select();
        $filename="佣金明细";
        $headArr=["订单编号","下单时间", "下单商品/数量",'会员名','订单金额','商家佣金',"结算状态"];
        $this->getExcel($filename,$headArr,$lists);
    }
}