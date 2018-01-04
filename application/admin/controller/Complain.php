<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/8/12
 * Time: 上午11:01
 */
namespace app\admin\controller;
use app\admin\Admin;
class Complain extends Admin {
    public function index() {
        $map = " co.is_delete = 0 ";
        I('get.min_date') && $map .= " AND co.date_add >= ".strtotime(I('get.min_date')) ;
        I("get.max_date") && $map .=" AND co.date_add <= ".(strtotime(I("get.max_date"))) ;
        I('get.nickname') && $map .=" AND c.nickname like '%".trim(I('get.customer_name'))."%'";

        $count = M('complain')
            ->alias("co")
            ->join("customer c","c.customer_id = co.customer_id")
            ->where($map)
            ->count();
        $page = new \com\Page($count , 15);

        $complains = M('complain')
            ->alias("co")
            ->join("customer c","c.customer_id = co.customer_id")
            ->where($map)
            ->field("co.*,c.nickname")
            ->limit($page->firstRow . "," . $page->listRows)
            ->order("co.date_add desc")
            ->select();

        $this->assign("page", $page->show());
        $this->assign("complains",$complains);
        $this->display();
    }

    public function deleted() {
        (!$id = I("id")) && $this->error("请输入id");
        M("complain")->where(['id'=>$id])->save(['is_delete' => 1]);
        $this->log("删除投诉：". $id);
        $this->success("操作成功");
    }

    public function reply() {
        (!$id = I("id")) && $this->error("请输入id");
        (!$reply = I("reply") ) && $this->error("请输入回复内容");

        $complain = M("complain")
            ->where(['id' => $id])
            ->find();

        M("complain")->where(['id' => $id])
            ->save(['date_reply' => time(),'reply' => $reply]);
        $this->log("回复投诉：". $id . ", $reply");

        $client = \app\library\message\MessageClient::getInstance();
        $message = new \app\library\message\Message();
        $message->setAction(\app\library\message\Message::ACTION_NONE)
            ->setPlatform([\app\library\message\Message::PLATFORM_MESSAGE,\app\library\message\Message::PLATFORM_PUSH])
            ->setTargetIds($complain['customer_id'])
            ->setTemplate("complain")
            ->setExtras(['title' => '','content' => '投诉反馈:'.$complain['reply']]);
        $client->send($message);

        $this->success("操作成功");
    }
}