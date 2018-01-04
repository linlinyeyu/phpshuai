<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/7/17
 * Time: 上午10:41
 */
namespace app\admin\controller;
use app\admin\Admin;
class Advertisement extends Admin {
    public function index() {
        $ad = D("advertisement_model")->getAds();

        $this->assign("ads",$ad);
        $this->display();
    }

    public function add()
    {
        if (IS_POST) {
            $id = I("post.id");
            $image = I("image");
            $type = (int)I("type");

            !$image && $this->error("请传入图片");

            $link = I("link");

            $data = [
                'img' => $image,
                'sort' => (int)I("sort"),
                'link' => $link,
                'type' => $type,
            ];
            if (empty($id)) {
                $data['date_add'] = time();
                $data['date_upd'] = time();
                $id = M("advertisement")->add($data);
                $this->log("添加广告：" . "id：" . $id);
            } else {
                $data['date_upd'] = time();
                M("advertisement")->where(['ad_id' => $id])->save($data);
                $this->log("修改广告：" . "id：" . $id);
            }
            $this->success("操作成功", null, U("advertisement/index"));
        } else {
            $id = I("get.id");

            if (!empty($id)) {
                $ad = M("advertisement")->where(['ad_id' => $id])->find();
            } else {
                $ad = M("advertisement")->getEmptyFields();
            }
            $ad_types = M('advertisement_type')->where(['status' => 1])->select();
            $this->assign("ad_types", $ad_types);
            $this->assign("ad", $ad);
            $this->display();
        }
    }
}