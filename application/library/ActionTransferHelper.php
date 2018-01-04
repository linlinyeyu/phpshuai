<?php
namespace app\library;
class ActionTransferHelper{
	public static function transfer(&$params = []){
		if(empty($params) || !is_array($params)){
			return [];
		}
		$res = [];
		foreach ($params as &$p){
			$p['content'] = "";
			if(!isset($p['action_id']) || $p['action_id'] == 1 || !isset($p['params'])){
				continue;
			}
			$pp = unserialize($p['params']);
			switch ($p['action_id']){
				case 2 :
					if(isset($pp['goods_id'])){
						$p['content'] = M("goods")->where(['goods_id' => $pp['goods_id']])->getField("name");
					}
					break;
				case 7:
					if (isset($pp['seller_id'])){
						$p['content'] = M("seller_shopinfo")->where(['seller_id' => $pp['seller_id']])->getField("shop_name");
					}
					break;
			}
			unset($p['params']);
		}
		return $params;
	}
	
	
	public static function display(&$s){
		$s['action'] = [];
		
		if(isset($s['need_login'])){
			$action['need_login'] = $s['need_login'];
			unset($s['need_login']);
		}
		
		if(isset($s['params']) && !empty($s['params'])){
			$pp = [];
			$params = unserialize($s['params']);
			foreach ($params as $k => $v){
				$pp[] = ['key' => $k, 'value' => $v];
			}
			$action['params'] = $pp;
		}else{
			$action['params'] = [];
		}
		unset($s['params']);
		if(isset($s['action_id'])){
			$action['action_id'] = $s['action_id'];
			unset($s['action_id']);
		}

		
		$s['action'] = $action;
	}
	
	public static function get_action_params(&$s){
		$params = [];
		
		if(isset($s['params']) && !empty($s['params'])){
			$pp = unserialize($s['params']);
			$params = $pp;
			if(isset($s['action_id']) && $s['action_id'] == 2 && isset($params['goods_id'])){
				$s['goods_name'] = M("goods")->where(['goods_id' => $params['goods_id']])->getField("name");
				$s['seller_name'] = M("goods")->alias('g')->join("seller_shopinfo ss",'ss.seller_id = g.seller_id')->getField("ss.shop_name");
				if(empty($s['seller_name'])){
					$s['seller_name'] = '未找到关联店铺';
				}
				$s['shop_name'] = '';
				$s['goods_id'] = $params['goods_id'];
				$s['seller_id'] = '';
			}
			if(isset($s['action_id']) && $s['action_id'] == 7 && isset($params['seller_id'])){
				$s['shop_name'] = M("seller_shopinfo")->where(['seller_id' => $params['seller_id']])->getField("shop_name");
				$s['goods_name'] = "";
				$s['seller_name'] = '';
				$s['seller_id'] = $params['seller_id'];
				$s['goods_id'] = '';
			}
		}else{
			$params = [];
		}
		
		$s['params'] = $params;
		return $params;
	}
	
	public static function get_terminal_params($params, $t){
		$terminal = "wap_param";
		switch ($t){
			case 1:
				$terminal = "ios_param";
				break;
				//安卓终端
			case 2:
				$terminal = "android_param";
				break;
				//移动端
			case 3:
				$terminal = "wap_param";
				break;
				//微信端
			case 4:
				$terminal = "wap_param";
				break;
				//网页端
			case 5:
				$terminal = "web_param";
				break;
		}
		foreach ($params as &$p){			
			if(isset($p[$terminal])){
				$p['action']['jump'] = $p[$terminal];
			}
			unset($p['wap_param']);
			unset($p['web_param']);
			unset($p['android_param']);
			unset($p['ios_param']);
		}
		
		return $params;
	}
	
}