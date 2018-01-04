<?php
namespace app\library;
use think\Cache;
class TokenHelper{

	public static function refreshToken($refresh_token, $customer_id = 0)
		{
			if(empty($customer_id)){
				$customer_id = Cache::get($refresh_token);
			}
			$refresh_expire = 0;
			$last_refresh_token = "";
			$customer = [];
			if(empty($customer_id)){
				$customer = M("customer")->where("( refresh_token = '$refresh_token' or last_refresh_token = '$refresh_token' ) and refresh_expire > ".time()." ")->find();
			}else{
				$customer = M("customer")->where(['customer_id' => $customer_id])->find();
			}
			if(empty($customer)){
				return ['message'=>'重新登录','errcode'=>99];
			}
			
			$refresh_token = $customer['refresh_token'];
			$refresh_expire = $customer['refresh_expire'];
			$customer_id = $customer['customer_id'];
			$last_refresh_token = $customer['last_refresh_token'];
			$refresh_expire_time = C("refresh_expire");
			//如果refresh已经过了一个月的时间，那么需要重新获取下refresh_token，但老的token依然可以获取customer_id
			if($refresh_expire < time() + $refresh_expire_time / 2){
				$new_refresh_token = token();
				Cache::set($new_refresh_token, $customer_id, $refresh_expire_time);
				$data = ['refresh_token' => $new_refresh_token,'last_refresh_token' => $refresh_token, 'refresh_expire' => $refresh_expire_time + time()];
				M("customer")->where(['customer_id' => $customer_id]) ->save($data);
				$refresh_token = $new_refresh_token;
			}
			$access_token = token();
			$expire = C("token_expire") ? C("token_expire") : 7200;
			Cache::set($access_token, $customer_id, $expire);
			return ['errcode' => 0, 'message' => '请求成功','content' => ['access_token' => $access_token ,'refresh_token' => $refresh_token]];
		}
		
		public function refresh($customer_id){
			
		}
}
