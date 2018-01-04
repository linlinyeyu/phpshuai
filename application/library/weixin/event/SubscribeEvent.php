<?php
namespace app\library\weixin\event;
class SubscribeEvent extends Event{
	public function notify($params){
		$openid = $params['FromUserName'];
		
		$data = ['openid' => $openid,'access_token' => $params['access_token']];
		
		$pcustomer = [];
		if(isset($params['Ticket'])){
			$ticket = $params['Ticket'];
			$pcustomer =  M("customer")->where(['ticket' => $ticket])->field("uuid, nickname")->find();
			if(!empty($pcustomer)){
				$data['pid'] = $pcustomer['uuid'];
			}
		}
		
		$msg = new \app\library\weixin\msg\TextMessage();
		$msg->setParams("FromUserName", $params['ToUserName']);
		$msg->setParams("ToUserName", $params['FromUserName']);
		$msg->setParams("Content", \app\library\SettingHelper::get("bear_weixin_subscribe","欢迎关注零美云合公众号"));
		
		$oauth = new \app\oauth\WeixinGzOauth($data);
		$oauth->setSub(true);
		$res = $oauth->isLoginOrRegsiter();
		if(!$res['content']){
			$oauth->register(false);
			if(!empty($pcustomer)){
				$msg->setParams("Content",  "您扫描了{$pcustomer['nickname']}的二维码关注了零美云合公众号。");
			}
		}else if(isset($data['pid'])){
			$customer = M("customer")->where(['wx_gz_openid' => $openid])->field("agent_id, customer_id, phone")->find();
			if(!empty($customer) && $customer['agent_id'] == 0 && empty($customer['phone'])){
				$result = D("customer_extend")->bind_extend($customer['customer_id'], $data['pid']);
				if($result ){
					$msg->setParams("Content",  "您扫描了{$pcustomer['nickname']}的二维码关注了零美云合公众号。");
				}
			}
		}
		return $msg;
	}
	
}