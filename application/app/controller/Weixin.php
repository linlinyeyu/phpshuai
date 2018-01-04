<?php
namespace app\app\controller;

class Weixin{

	public function api(){
		$client = \app\library\weixin\WeixinClient::getInstance();
		$client->valid();
		$res = $client->dealMessage();
		if(!empty($res)){
			echo $res;
		}else{
			echo 'success';
		}
		exit;
	}
}