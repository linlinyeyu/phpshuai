<?php
namespace app\library\order;
class WebAlipayOrderHelper extends OrderHelper{

    public function pay_params($customer_id){
        // 支付宝提现操作
        vendor("Alipay.alipay.AopSdk");

        $params = \app\library\SettingHelper::get_pay_params(2, ['is_open' => 0]);

        if($params['is_open'] == 0){
            return ['errcode' => -102 , 'message' => '支付宝暂未开启'];
        }

        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = trim($this->order_number);

        //订单名称，必填
        $subject = trim($this->subject);

        //付款金额，必填
        $total_amount = trim($this->sum);

        //商品描述，可空
        $body = trim($this->body);

        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder ();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);

        $biz_content=$payRequestBuilder->getBizContent();

        $notify_url = "http://"."shuaibo.zertone1.com"."/app/alipay/notify_web";
        //$notify_url	= "http://" . "app.shuaibomall.net" ."/app/alipay/notify_web";
        if ($this->order_type == 1) {
            $return_url	= "http://" . "www.shuaibomall.net" ."/module/confirmOrder.html#payResult";
        } elseif ($this->order_type == 2) {
            $return_url	= "http://" . "www.shuaibomall.net" ."/module/myOrder.html#vip7";
        } elseif ($this->order_type == 4 || $this->order_type == 5) {
            $return_url	= "http://" . "www.shuaibomall.net" ."/module/index.html";
        } elseif ($this->order_type == 6){
            $return_url = "http://"."localhost:8024"."/module/myOrder.html#vip74";
            //$return_url = "http://"."www.shuaibomall.net"."/module/myOrder.html#vip74";
        }

        $request = new \AlipayTradePagePayRequest ();

        $request->setNotifyUrl($notify_url);
        $request->setReturnUrl($return_url);
        $request->setBizContent ( $biz_content );

        /**
         * pagePay 电脑网站支付请求
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @param $return_url 同步跳转地址，公网可以访问
         * @param $notify_url 异步通知地址，公网可以访问
         * @return $response 支付宝返回的信息
         */

        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = "2016050601369766";
        $aop->rsaPrivateKey =  "MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBALPE1tyKjSLrZuhNeCMlz5ubrGqgUDBII7pWLlpNuE/kObkAHAf0eBbpNU3WICKzXulZbQqZ76ZnDewHUo6lwyvQt4iw1/4xpNasldZf3duisGC06cpcPtRdExuJ9dRDnOLeYE+Q3tZKnHHXNd6iUAUWU/jKALLcknwxtRIwJbvDAgMBAAECgYEAkmwNpeykMIEUfLo81EQD6XbO5LpXFjxr/WYcsykCqu/1pB3WtxQEjIS/CfsSibEX6XERQ8VGUX8287yzHcUeRb/Ave1s6YFsCI+9Lf32trzFnZX54AMUndOQi5CB3sDh5nOCKDlKZ6+fbyaEqWIMsNxgwAKXIHTeH7Ut7jSM2+ECQQDkW9z/OEnlP2QHVjoakucBzxuVcHk8akonw/djzBK1iS8wyB6dhnuCL7r7sz8V1R701OOUB9XZkjhCupUeSpL1AkEAyYdS2JJDEWGe8M2FAWtQdgD382miecvnMDvAS578/ZX6cC+k27LlC7k99JpQopuI6htUCf049Pc6MRhcGeEQ1wJBAM/1IGKDvje47L0Jt0wv75NkKjiC/sUX/oQMICSP2ZHcZk9ETy0hJSS/lsZUy+Rz+wb3QHC0WfkTAY0zIU0+mGkCQGLQLXjvTl9JZGth+iNWAR+7HdiGJRpfNj5aLdFmZVnZnfBADC+FKfVzoMM8nuj8JkfTmoNDXBgQ2MGV1iMCTgUCQQCyv3DDD7R6/lIxr0GtxloYQVE/O0rDNOEOnf/GG7DhY5SCZ22IxtdHX7FIV3SnBDMMRfbjeR+P4u4NGqMGZE12";
        $aop->alipayrsaPublicKey = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB";
        $aop->apiVersion ="1.0";
        $aop->postCharset = 'utf-8';
        $aop->format= 'json';
        $aop->signType='RSA';


		// 首先调用支付api
        $result = $aop->pageExecute($request,'POST');

        $this->return['html_text'] = $result;

        return ['errcode' => 0, 'message' => '请求成功'];


    }
}