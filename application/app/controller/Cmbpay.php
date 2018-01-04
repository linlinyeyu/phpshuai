<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/12/26
 * Time: 15:02
 */
namespace app\app\controller;

class Cmbpay{
    //支付回调
    public function notify(){
        $request_data = I("jsonRequestData");
        $request_data = json_decode($request_data,true);
        //验签
        $tosign_data = $this->dictSort($request_data['noticeData']);
        $str_to_sign = "";
        foreach($tosign_data as $key => $val){
            $str_to_sign = $str_to_sign.$key."=".$val."&";
        }

        $res = $this->verify($str_to_sign,$request_data['sign']);
        if ($res == false){
            header("HTTP/1.1 100 Continue");
            return "false";
        }
        $order_no = $request_data['noticeData']['orderNo'];
        $amount = $request_data['noticeData']['amount'];
        $out_order_no = $request_data['noticeData']['bankSerialNo'];
        $pay_id = M("order_info")->where(['order_sn' => $order_no])->getField("pay_id");
        $order_notify = new \app\library\order\OrderNotify($order_no,$amount,$pay_id);
        $order_notify->setOutTradeNo($out_order_no);
        $order_notify->notify();

        return "success";
    }

    //签约回调
    public function signNotify(){
        $jsonRequest_data = I("jsonRequestData");
        $jsonRequest_data = json_decode($jsonRequest_data,true);
        //验签
        $tosign_data = $this->dictSort($request_data['noticeData']);
        $str_to_sign = "";
        foreach($tosign_data as $key => $val){
            $str_to_sign = $str_to_sign.$key."=".$val."&";
        }

        $res = $this->verify($str_to_sign,$request_data['sign']);
        if ($res == false){
            header("HTTP/1.1 100 Continue");
            return "false";
        }
        $protocol_num = $jsonRequest_data['noticeData']['protocol_num'];
        $customer_cmb = M("cmb_protocol")->where(['protocol_num' => $protocol_num])->find();
        if ($notice_data['rspCode'] == "SUC0000"){
            M("customer")->where(['customer_id' => $customer_cmb['customer_id']])->save(['protocol_num' => $customer_cmb['protocol_num']]);
            M("customer_cmb")->where(['protocol_num' => $protocol_num])->save(['status' => 1,'date_upd' => time()]);
        }else{
            M("customer_cmb")->where(['protocol_num' => $protocol_num])->save(['status' => 2,'date_upd' => time()]);
        }

        return "success";
    }

    //升序排序
    private function dictSort($reqData){
        $keyArr = [];
        $keyArrSorted = [];
        foreach($reqData as $key => $val){
            array_push($keyArr, strtolower($key));
        }
        sort($keyArr);

        for($i = 0; $i < count($keyArr); $i++){
            foreach($reqData as $key => $val){
                if(!strcasecmp($key, $keyArr[$i])){
                    $keyArrSorted[$key] = $val;
                }
            }
        }
        return $keyArrSorted;
    }

    //验签
    private function verify($str_to_sign,$sign){
        //公钥
        $pub_key = file_get_contents("./cert/cmb_pay/cmb.pem");
        $pem = chunk_split($pub_key, 64, "\n");
        $pem = "-----BEGIN PUBLIC KEY-----\n" . $pem . "-----END PUBLIC KEY-----\n";
        $pkid = openssl_pkey_get_public($pem);
        if (empty($pkid)) {
            die('获取 pkey 失败');
        }
//验证
        $ok = openssl_verify($str_to_sign, base64_decode($sign), $pkid, OPENSSL_ALGO_SHA1);
        if ($ok == 1){
            return true;
        }
        return false;
    }


}