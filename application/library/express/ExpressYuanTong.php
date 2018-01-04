<?php

namespace app\library\express;

use app\library\ExpressHelper;

class ExpressYuanTong extends ExpressHelper{
    private static $app_key = "VZtTm7";
    private static $user_id = "lingmeiyunhe";
    private static $method = "yto.Marketing.WaybillTrace";
    private static $format = "XML";
    private static $version = "1.01";
    private static $url = "http://58.32.246.70:8002";

    public function get_express($express_sn)
    {
        $url = self::$url;
        $data = ['app_key' => self::$app_key,
            'method' => self::$method,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' =>self::$user_id,
            'v' => self::$version,
            'format' => self::$format
        ];
        ksort($data);
        $str = 'RvNKhK';
        foreach ($data as $k => $v){
            $str .= $k. $v;
        }
        $sign = strtoupper(md5($str));
        $data['sign'] = $sign;
        $data['param'] = '<?xml version="1.0" ?>
			<ufinterface>
			<Result>
			<WaybillCode>
			<Number>'.$express_sn.'</Number>
			</WaybillCode>
			</Result>
			</ufinterface>';
        $params = [];
        foreach ($data as $k => $v){
            $params[] = $k . "=" . $v;
        }
        $header = ["Content-Type:application/x-www-form-urlencoded;charset=UTF-8"];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, join("&",$params) );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, $header);
        $result = curl_exec($ch);
        $index = strpos($result, '<?xml');
        if($index === false){
            return [];
        }
        $result = substr($result, $index);
        $result = simplexml_load_string($result);
        if($result->success){
            return [];
        }
        $result = $result->Result;
        $express = [];
        foreach( $result->WaybillProcessInfo as $k => $v){
            $express[] = ['time' => (string)$v->Upload_Time, 'address' => (string)$v->ProcessInfo];
        }

        return $express;



    }
}