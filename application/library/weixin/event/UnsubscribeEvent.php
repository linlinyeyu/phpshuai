<?php
namespace app\library\weixin\event;
class UnsubscribeEvent extends Event{
	public function notify($params){
		$openid = $params['FromUserName'];
		M("customer")->where(['wx_gz_openid' => $openid])->save(['is_subscribe' => 0]);
	}
}