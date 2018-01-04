<?php
/**
 * 韵达快递查询接口
 */
namespace app\library\express;
use app\library\ExpressHelper;

class ExpressYunDa extends ExpressHelper{
    private static $partnerid = "yunda";
    private static $charset = "utf8";
    private static $url = "http://dev.yundasys.com:15105/join/query/json.php?";
    public function get_express($express_sn)
    {
        $url = self::$url."partnerid=".self::$partnerid."&mailno=".self::$charset."";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $contents = curl_exec($ch);
        curl_close($ch);
        $obj = json_decode($contents,1);
        $result = array();
//        if($obj['result']=='true'){
//            foreach ($obj['steps'] as $key=>$val){
//                $result[$key]['time'] = $val['time'];
//                $result[$key]['address'] = $val['address']." ".$val['remark'];
//            }
//        }
        return $result;
    }

}