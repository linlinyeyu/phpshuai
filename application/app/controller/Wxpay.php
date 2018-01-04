<?php
namespace app\app\controller;
use think\Cache;
class Wxpay{
/**
 * 微信支付的一些通知信息
 */	
	public function notify(){
		
		vendor("Weixin.WxPayHelper");
		vendor("Weixin.WxLog");
		//存储微信的回调
		$xml = file_get_contents("php://input");
		if(empty($xml)){
			return ;
		}
		
		$config = \app\library\SettingHelper::get_pay_params(3);
		$notify = new \WxPayHelper($config);
		
		
		$log_ = new \Log_();
		
		$dir = "data/pay/";
		$log_name= $dir. "wx_notify_url.log";//log文件路径
		mkDirs($_SERVER['DOCUMENT_ROOT'] . "/" . $dir);
		$log_->log_result($log_name,"【接收到的notify通知】:\n".$xml."\n");
		
		//验证签名，并回应微信。
		//对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
		//微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
		//尽可能提高通知的成功率，但微信不保证通知最终能成功。
		
		$code = "FAIL";
		$message = "";
		if($notify->checkSign($xml) == FALSE){
			$message = "签名失败";
		}else{
			if ($notify->data["return_code"] == "FAIL") {
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【通信出错】:\n".$xml."\n");
				$message = "通信出错";
			}
			
			else{
				//此处应该更新一下订单状态，商户自行增删操作
				$out_trade_no = $notify->data['out_trade_no'];
				$trade_no = $notify->data['transaction_id'];
				$total_fee = $notify->data['total_fee'] / 100;
				$order_notify = new \app\library\order\OrderNotify($out_trade_no,$total_fee, 3);
				$order_notify->setOutTradeNo($trade_no);
				$order_notify->notify();
				$code = "SUCCESS" ;
				$message = "请求成功";
			}
		}
		$returnXml = $notify->returnXml($code, $message);
		
		//==商户根据实际情况设置相应的处理流程，此处仅作举例=======
		
		//以log文件形式记录回调信息
		echo $returnXml;
		die();
	} 
	
	public function notifypub(){
		vendor("Weixin.WxPayHelper");
		vendor("Weixin.WxLog");
		//存储微信的回调
		$xml = file_get_contents("php://input");
		if(empty($xml)){
			return ;
		}
		
		$config = \app\library\SettingHelper::get_pay_params(5);
		$notify = new \WxPayHelper($config);
		
		
		$log_ = new \Log_();
		
		$dir = "data/pay/";
		$log_name= $dir. "wx_pub_notify_url.log";//log文件路径
		mkDirs($_SERVER['DOCUMENT_ROOT'] . "/" . $dir);
		$log_->log_result($log_name,"【接收到的notify通知】:\n".$xml."\n");
		
		//验证签名，并回应微信。
		//对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
		//微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
		//尽可能提高通知的成功率，但微信不保证通知最终能成功。
		
		$code = "FAIL";
		$message = "";
		if($notify->checkSign($xml) == FALSE){
			$message = "签名失败";
		}else{
			$log_->log_result($log_name,"\n". json_encode($notify->data));
			if ($notify->data["return_code"] == "FAIL") {
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【通信出错】:\n".$xml."\n");
				$message = "通信出错";
			}
			
			else{
				//此处应该更新一下订单状态，商户自行增删操作
				$out_trade_no = $notify->data['out_trade_no'];
				$trade_no = $notify->data['transaction_id'];
				$total_fee = $notify->data['total_fee'] / 100;
				$order_notify = new \app\library\order\OrderNotify($out_trade_no,$total_fee, 5);
				$order_notify->setOutTradeNo($trade_no);
				$order_notify->notify();
				$code = "SUCCESS" ;
				$message = "请求成功";
			}
		}
		$returnXml = $notify->returnXml($code, $message);
		$log_->log_result($log_name,"回复信息:\n".$returnXml."\n");
		
		//==商户根据实际情况设置相应的处理流程，此处仅作举例=======
		
		//以log文件形式记录回调信息
		echo $returnXml;
		die();
	}


}