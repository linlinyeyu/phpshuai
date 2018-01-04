<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/6/6
 * Time: 下午3:26
 */
namespace app\api;
require_once 'curl.func.php';

class ExpressApi {
    private $appkey = 'c9338cbcce8d9c5f';
    private $appsecret = 'nvquIXKTvz8FM7qAwypNveKevI9TcX33';

    public static function getInstance(){
        return new ExpressApi();
    }

    //物流信息
    public function express($type = 'auto',$number){
        $type = 'auto';
        $url = "http://api.jisuapi.com/express/query?appkey=$this->appkey&type=$type&number=$number";
        $result = curlOpen($url);
        $jsonarr = json_decode($result, true);
        //exit(var_dump($jsonarr));
        if($jsonarr['status'] != 0)
        {
            return ['errcode' => -101,'message' => $jsonarr['msg']];
        }

        return ['errcode' => 0,'message' => $jsonarr['msg'], 'content' => $jsonarr['result']];
    }

    public function orderExpress($type = 'auto',$number){
//        if (empty($type)) {
            $type = 'auto';
//        }
        $url = "http://api.jisuapi.com/express/query?appkey=$this->appkey&type=$type&number=$number";
        $result = curlOpen($url);
        $jsonarr = json_decode($result, true);
        //exit(var_dump($jsonarr));
        if($jsonarr['status'] != 0)
        {
            return ['errcode' => -101,'message' => $jsonarr['msg']];
        }

        return ['errcode' => 0,'message' => $jsonarr['msg'], 'content' => $jsonarr['result']['list'][0]];
    }

    public function expressState($type = 'auto',$number){
//        if (empty($type)) {
            $type = 'auto';
//        }
        $url = "http://api.jisuapi.com/express/query?appkey=$this->appkey&type=$type&number=$number";
        $result = curlOpen($url);
        $jsonarr = json_decode($result, true);
        //exit(var_dump($jsonarr));
        if($jsonarr['status'] != 0)
        {
            return ['state' => -1000, 'state_name' => '未知'];
        }

        if ($jsonarr['result']['deliverystatus'] == 1) {
            return ['state' => $jsonarr['result']['deliverystatus'], 'state_name' => '卖家已发货'];
        }

        if ($jsonarr['result']['deliverystatus'] == 2) {
            return ['state' => $jsonarr['result']['deliverystatus'], 'state_name' => '配送中'];
        }

        if ($jsonarr['result']['deliverystatus'] == 3) {
            return ['state' => $jsonarr['result']['deliverystatus'], 'state_name' => '已签收'];
        }

        return ['state' => -1000, 'state_name' => '未知'];
    }

}