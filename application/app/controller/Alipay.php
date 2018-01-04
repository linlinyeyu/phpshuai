<?php
namespace app\app\controller;
use think\Cache;
class Alipay{

/**
 *支付宝交易的一些通知信息
 */

    public function notify(){

    	$alipay_config = \app\library\SettingHelper::get_pay_params(2);
    	if(empty($alipay_config)){
    		echo 'fail';
    		die();
    	}
    	$alipay_config['cacert'] = $_SERVER['DOCUMENT_ROOT']. "/cert/cacert.pem";
    	$default_config = array(
            'sign_type' => strtoupper('RSA'),
            'input_charset' =>strtolower('utf-8'),
            'cacert' => $_SERVER['DOCUMENT_ROOT']. "/cert/cacert.pem",
            'transport' => 'http',
        );
    	$alipay_config = array_merge($alipay_config, $default_config);
    	
    	if(isset($alipay_config['ali_public_key_path'])){
    		$alipay_config['ali_public_key_path'] = $_SERVER['DOCUMENT_ROOT'] . $alipay_config['ali_public_key_path'];
    	}
    	
    	vendor('Alipay.CoreFunction');
        vendor('Alipay.RsaFunction');
        vendor('Alipay.NotifyClass');
        vendor("Weixin.WxLog");
    	
    	$log_ = new \Log_();
    	$dir = "data/pay/";
    	$log_name= $dir. "ali_notify_url.log";//log文件路径
    	mkDirs($_SERVER['DOCUMENT_ROOT'] . "/" . $dir);
    	$log_->log_result($log_name,"notify:". json_encode(I("")));
    	$alipayNotify = new \AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
		if($verify_result){
			$out_trade_no = I('out_trade_no');

			//支付宝交易号

			$trade_no = I('trade_no');

			//交易状态
			$trade_status = I('trade_status');

			$total_fee = I("total_fee");

            $total_amount = I("total_amount");

            if($trade_status == 'TRADE_FINISHED') {
		    	
				//判断该笔订单是否在商户网站中已经做过处理
					//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
					//如果有做过处理，不执行商户的业务程序
						
				//注意：
				//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
				//请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的

		        //调试用，写文本函数记录程序运行情况是否正常
		        //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		    }
		    
		    else if ($trade_status == 'TRADE_SUCCESS') {
		    	if(I("seller_id") != $alipay_config['partner']){
		    		echo "fail";
		    		die();
		    	}
		    	$pay_id = M('order_info')->where(['order_sn' => $out_trade_no])->getField("pay_id");
                if ($pay_id == 4) {
                    $total_fee = I("total_amount");
                }
                $order_notify = new \app\library\order\OrderNotify($out_trade_no,$total_fee, $pay_id);
				$order_notify->setOutTradeNo($trade_no);
				$res = $order_notify->notify();
                $log_->log_result($log_name,"return:". json_encode($res));
                //判断该笔订单是否在商户网站中已经做过处理
					//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
					//如果有做过处理，不执行商户的业务程序
						
				//注意：
				//付款完成后，支付宝系统发送该交易状态通知
				//请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的

		        //调试用，写文本函数记录程序运行情况是否正常
		        //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		    }
		    echo "success";
		    die();
		}else{
			echo "fail";
			die();
		}
    }


    public function notify_web(){
        $alipay_config = [
            'is_open' => 1,
            'partner' => '2088221626518034',
            'seller_email'=>'2088221626518034',
            'public_key_path' => '/cert/alipayWeb/public_key.pem',
            'ali_public_key_path' => '/cert/alipayWeb/ali_public_key.pem',
            'private_key_path' => '/cert/alipayWeb/private_key.pem'
        ];

        if(empty($alipay_config)){
            echo 'fail';
            die();
        }
        $alipay_config['cacert'] = $_SERVER['DOCUMENT_ROOT']. "/cert/cacert.pem";
        $default_config = array(
            'sign_type' => strtoupper('RSA'),
            'input_charset' =>strtolower('utf-8'),
            'cacert' => $_SERVER['DOCUMENT_ROOT']. "/cert/cacert.pem",
            'transport' => 'http',
        );
        $alipay_config = array_merge($alipay_config, $default_config);

        if(isset($alipay_config['ali_public_key_path'])){
            $alipay_config['ali_public_key_path'] = $_SERVER['DOCUMENT_ROOT'] . $alipay_config['ali_public_key_path'];
        }

        vendor('Alipay.CoreFunction');
        vendor('Alipay.RsaFunction');
        vendor('Alipay.NotifyClass');
        vendor("Weixin.WxLog");

        $log_ = new \Log_();
        $dir = "data/pay/";
        $log_name= $dir. "ali_notify_url.log";//log文件路径
        mkDirs($_SERVER['DOCUMENT_ROOT'] . "/" . $dir);
        $log_->log_result($log_name,"notify:". json_encode(I("")));
        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        if($verify_result){
            $out_trade_no = I('out_trade_no');

            //支付宝交易号

            $trade_no = I('trade_no');

            //交易状态
            $trade_status = I('trade_status');

            $total_fee = I("total_fee");

            $total_amount = I("total_amount");

            if($trade_status == 'TRADE_FINISHED') {

                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
                //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            }

            else if ($trade_status == 'TRADE_SUCCESS') {
                if(I("seller_id") != $alipay_config['partner']){
                    echo "fail";
                    die();
                }
                $pay_id = M('order_info')->where(['order_sn' => $out_trade_no])->getField("pay_id");
                $log_->log_result($log_name,"pay_id:". json_encode($pay_id));
                if ($pay_id == 4) {
                    $total_fee = I("total_amount");
                }
                $order_notify = new \app\library\order\OrderNotify($out_trade_no,$total_fee, $pay_id);
                $order_notify->setOutTradeNo($trade_no);
                $res = $order_notify->notify();
                $log_->log_result($log_name,"return:". json_encode($res));
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //付款完成后，支付宝系统发送该交易状态通知
                //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            }
            echo "success";
            die();
        }else{
            echo "fail";
            die();
        }
    }
}