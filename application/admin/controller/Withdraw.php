<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/6/21
 * Time: 上午10:50
 */
namespace app\admin\controller;
use app\admin\Admin;
class Withdraw extends Admin {

    /**
     *  佣金提现列表
     */
    public function index() {
        $state = (int)I("get.state");
        $type = (int)I("get.type");

        $map = " cw.style = 2 ";
        $map .= " AND cw.state = ".$state;
        $state == 0 && $map = " cw.style = 2 ";
        if ($type != 0) {
            $map .= " AND cw.type = ".$type;
        }
        I('get.nickname') && $map .= " AND c.nickname like '%".trim(I('get.nickname')) . "%' ";
        I('get.min_date') && $map .= " AND cw.date_add >= ".strtotime(I('get.min_date')) . " ";
        I("get.max_date") && $map .=" AND cw.date_add <= ".(strtotime(I("get.max_date")))  . " ";

        $count = M('customer_withdraw')
            ->alias("cw")
            ->join("customer c","c.customer_id = cw.customer_id")
            ->where($map)
            ->count();
        $page = new \com\Page($count, 20);

        $list = M('customer_withdraw')
            ->alias("cw")
            ->join("customer c","c.customer_id = cw.customer_id")
            ->where($map)
            ->field("c.nickname,cw.id,cw.money,cw.state,cw.date_add,cw.type,cw.account,cw.realname,cw.style,cw.subbranch")
            ->order("cw.date_add DESC")
            ->limit($page->firstRow.",".$page->listRows)
            ->select();

        $this->assign("lists",$list);
        $this->assign('page',$page->show());
        $this->display();
    }

    /**
     *  余额提现列表
     */
    public function index_customer() {
        $state = (int)I("get.state");
        $type = (int)I("get.type");

        $map = " cw.style = 1 ";
        $map .= " AND cw.state = ".$state;
        $state == 0 && $map = " cw.style = 1 ";
        if ($type != 0) {
            $map .= " AND cw.type = ".$type;
        }
        I('get.nickname') && $map .= " AND c.nickname like '%".trim(I('get.nickname')) . "%' ";
        I('get.min_date') && $map .= " AND cw.date_add >= ".strtotime(I('get.min_date')) . " ";
        I("get.max_date") && $map .=" AND cw.date_add <= ".(strtotime(I("get.max_date")))  . " ";

        $count = M('customer_withdraw')
            ->alias("cw")
            ->join("customer c","c.customer_id = cw.customer_id")
            ->where($map)
            ->count();
        $page = new \com\Page($count, 20);

        $list = M('customer_withdraw')
            ->alias("cw")
            ->join("customer c","c.customer_id = cw.customer_id")
            ->where($map)
            ->field("c.nickname,cw.id,cw.money,cw.state,cw.date_add,cw.type,cw.account,cw.realname,cw.style,cw.subbranch")
            ->order("cw.date_add DESC")
            ->limit($page->firstRow.",".$page->listRows)
            ->select();

        $this->assign("lists",$list);
        $this->assign('page',$page->show());
        $this->display();
    }

    public function agree() {
        !($id = I("id")) && $this->error("请传入参数");
        $withdraw = M('customer_withdraw')->where(['id' => $id])->find();
        if (empty($withdraw)) {
            $this->error("未找到对应的提现信息");
        }
        M('customer_withdraw')->where(['id' => $id])->save(['state' => 2]);
        if ($withdraw['style'] == 1) {
            // 余额提现
            M("finance")->add([
                'customer_id' => $withdraw['customer_id'],
                'finance_type_id' => 3,
                'type' => 5,
                'amount' => $withdraw['money'],
                'date_add' => time(),
                'is_minus' => 1,
                'order_sn' => $withdraw['order_sn'],
                'comments' => '提现订单 '.$withdraw['order_sn'],
                'title' => '-'.$withdraw['money'],
            ]);
        } elseif ($withdraw['style'] == 3) {
            //余额宝提现
            M("finance")->add([
                'customer_id' => $withdraw['customer_id'],
                'finance_type_id' => 3,
                'type' => 10,
                'amount' => $withdraw['money'],
                'date_add' => time(),
                'is_minus' => 1,
                'order_sn' => $withdraw['order_sn'],
                'comments' => '提现订单 '.$withdraw['order_sn'],
                'title' => '-'.$withdraw['money'],
            ]);
        } else {
            // 佣金提现
            M("finance_op")->add([
                'customer_id' => $withdraw['customer_id'],
                'finance_type' => 5,
                'amount' => $withdraw['money'],
                'real_amount' => $withdraw['money'],
                'date_add' => time(),
                'is_minus' => 1,
                'order_sn' => $withdraw['order_sn'],
                'comments' => '提现订单 '.$withdraw['order_sn'],
                'title' => '-'.$withdraw['money'],
            ]);
            // 提现订单状态改变
            M("customer_withdraw_order")
                ->where(['state' => 2,'customer_id' => $withdraw['customer_id']])
                ->save(['state' => 3]);
        }


        $client = \app\library\message\MessageClient::getInstance();
        $message = new \app\library\message\Message();
        $message->setAction(\app\library\message\Message::ACTION_NONE)
            ->setPlatform([\app\library\message\Message::PLATFORM_MESSAGE,\app\library\message\Message::PLATFORM_PUSH])
            ->setTargetIds($withdraw['customer_id'])
            ->setTemplate("withdraw")
            ->setPushExtra(['title' => '提现订单'.$withdraw['order_sn']."申请成功，等待平台打款",'content' => '提现订单'.$withdraw['order_sn']."申请成功，等待平台打款"]);
        $client->send($message);

        $this->success("成功");
    }

    public function reject() {
        !($id = I("id")) && $this->error("请传入参数");
        $withdraw = M('customer_withdraw')->where(['id' => $id])->find();
        if (empty($withdraw)) {
            $this->error("未找到对应的提现信息");
        }
        M('customer_withdraw')->where(['id' => $id])->save(['state' => 3]);
        if ($withdraw['style'] == 1) {
            M("customer")->where(['customer_id' => $withdraw['customer_id']])->setInc("account",$withdraw['money']);
        } elseif ($withdraw['style'] == 2) {
            M("customer")->where(['customer_id' => $withdraw['customer_id']])->setInc("commission",$withdraw['money']);
        } elseif ($withdraw['style'] == 3){
            M("customer")->where(['customer_id' => $withdraw['customer_id']])->setInc("reward_amount",$withdraw['money']);
        }

        $client = \app\library\message\MessageClient::getInstance();
        $message = new \app\library\message\Message();
        $message->setAction(\app\library\message\Message::ACTION_NONE)
            ->setPlatform([\app\library\message\Message::PLATFORM_MESSAGE,\app\library\message\Message::PLATFORM_PUSH])
            ->setTargetIds($withdraw['customer_id'])
            ->setTemplate("withdraw")
            ->setPushExtra(['title' => '提现订单'.$withdraw['order_sn']."申请失败，请联系平台客服",'content' => '提现订单'.$withdraw['order_sn']."申请失败，请联系平台客服"]);
        $client->send($message);

        $this->success("成功");
    }

    public function sure() {
        !($id = I("id")) && $this->error("请传入参数");
        $withdraw = M('customer_withdraw')->where(['id' => $id])->find();
        if (empty($withdraw)) {
            $this->error("未找到对应的提现信息");
        }
        M('customer_withdraw')->where(['id' => $id])->save(['state' => 4]);
        // 提现订单状态改变
        M("customer_withdraw_order")
            ->where(['state' => 3,'customer_id' => $withdraw['customer_id']])
            ->save(['state' => 4]);

        $client = \app\library\message\MessageClient::getInstance();
        $message = new \app\library\message\Message();
        $message->setAction(\app\library\message\Message::ACTION_NONE)
            ->setPlatform([\app\library\message\Message::PLATFORM_MESSAGE,\app\library\message\Message::PLATFORM_PUSH])
            ->setTargetIds($withdraw['customer_id'])
            ->setTemplate("withdraw")
            ->setPushExtra(['title' => '提现订单'.$withdraw['order_sn']."申请完成",'content' => '提现订单'.$withdraw['order_sn']."申请完成"]);
        $client->send($message);

        $this->success("成功");
    }

    /**
     *  余额宝提现列表
     */
    public function yuebao() {
        $state = (int)I("get.state");
        $type = (int)I("get.type");

        $map = " cw.style = 1 ";
        $map .= " AND cw.state = ".$state;
        $state == 0 && $map = " cw.style = 3 ";
        if ($type != 0) {
            $map .= " AND cw.type = ".$type;
        }
        I('get.nickname') && $map .= " AND c.nickname like '%".trim(I('get.nickname')) . "%' ";
        I('get.min_date') && $map .= " AND cw.date_add >= ".strtotime(I('get.min_date')) . " ";
        I("get.max_date") && $map .=" AND cw.date_add <= ".(strtotime(I("get.max_date")))  . " ";

        $count = M('customer_withdraw')
            ->alias("cw")
            ->join("customer c","c.customer_id = cw.customer_id")
            ->where($map)
            ->count();
        $page = new \com\Page($count, 20);

        $list = M('customer_withdraw')
            ->alias("cw")
            ->join("customer c","c.customer_id = cw.customer_id")
            ->where($map)
            ->field("c.nickname,cw.id,cw.money,cw.state,cw.date_add,cw.type,cw.account,cw.realname,cw.real_money,cw.subbranch")
            ->order("cw.date_add DESC")
            ->limit($page->firstRow.",".$page->listRows)
            ->select();

        $this->assign("lists",$list);
        $this->assign('page',$page->show());
        $this->display();
    }
}