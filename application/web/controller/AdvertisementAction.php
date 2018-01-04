<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/7/17
 * Time: 上午11:53
 */
namespace app\web\controller;
use  think\Cache;
class AdvertisementAction {
    public function getLoginAd() {
        $terminal = (int)I("t");
        $position = (int)I('postion');
        $ads = D("advertisement_model")->GetAds(1,$terminal,1920,600);
        return ['errcode' => 0,'message' => '请求成功','content' => ['ads' => $ads]];
    }
}