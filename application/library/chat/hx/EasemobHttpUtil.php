<?php
namespace app\library\chat\hx;
use think\Cache;
class EasemobHttpUtil{
	
	private $config = [];
	
	private function __construct($config = []){
		if(empty($config)){
			$config = \app\library\SettingHelper::get("leader_easemob");
			if(empty($config)){
				$config = [
						'client_id' => Constants::APP_CLIENT_ID , 
						'client_secret' => Constants::APP_CLIENT_SECRET,
						'app_key' => Constants::APPKEY
				];
			}
		}
		$this->config = $config;
	}
	
	public static function getInstance($config = []){
		return new EasemobHttpUtil($config);
	}
	
	private function getToken(){
		$key = $this->config['client_id'] + "#" + $this->config['client_secret'];
		$token = S("leader_easemob_token_" . $key);

		if(empty($this->config['app_key'])){
			return null;
		}
		
		if(!empty($token)){
			$token = unserialize($token);
		}
		if(empty($token) || $token['create_time'] + $token['expires_in'] - 1000 < time()){
			$body = ["grant_type" => 'client_credentials',
					'client_id' => $this->config['client_id'],
					'client_secret' => $this->config['client_secret']
			];
			$headers = ['Content-Type:application/json'];
			$url = $this->replace(EndPoints::TOKEN_APP_URL);
			$response = $this->http($url, $body, "POST", true, $headers);
			
			if(!isset($response['access_token'])){
				return null;
			}
			
			$access_token = $response['access_token'];
			if(empty($access_token) ){
				return null;
			}
			$token = $response;
			$token['create_time'] = time();
			Cache::set("leader_easemob_token_" . $key, serialize($token), $token['expires_in']);
		}
		return $token['access_token'];
	}
	
	private function replace($url){
		return str_replace("%1", str_replace("#", "/", $this->config['app_key']), $url);
	}
	
	
	public function sendHttpRequest($url, $body, $method){
		$token = $this->getToken();
		if(empty($token)){
			return [];
		}
	
		if(empty($body)){
			$body = [];
		}
		$header = [
				'Content-Type:application/json', 
				'Authorization:Bearer ' . $token
		];
		$url = $this->replace($url);
		return $this->http($url, $body, $method, true, $header);
		
	}
	
	protected function http($url, $params, $method = 'GET',  $json = false, $header = [])
	{
		$opts = [
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_HTTPHEADER     => $header,
		];
	
		/* 根据请求类型设置特定参数 */
		switch (strtoupper($method)) {
			case 'GET':
				$opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
				break;
			case 'POST':
				$params                   = $json ?  json_encode($params, JSON_UNESCAPED_UNICODE) : $params;
				$opts[CURLOPT_URL]        = $url;
				$opts[CURLOPT_POST]       = 1;
				$opts[CURLOPT_POSTFIELDS] = $params;
				break;
			case "PUT" : 
				$params                   = $json ?  json_encode($params, JSON_UNESCAPED_UNICODE) : $params;
				$opts[CURLOPT_URL]        = $url;
				$opts[CURLOPT_CUSTOMREQUEST]   = 'PUT';
				$opts[CURLOPT_POSTFIELDS] = $params;
				break;
			case 'DELETE' : 
				$params                   = $json ?  json_encode($params, JSON_UNESCAPED_UNICODE) : $params;
				$opts[CURLOPT_URL]        = $url;
				$opts[CURLOPT_CUSTOMREQUEST]   = 'DELETE';
				$opts[CURLOPT_POSTFIELDS] = $params;
				break;
		}
		/* 初始化并执行curl请求 */
		$ch = curl_init();
		curl_setopt_array($ch, $opts);
		$data  = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		if ($error) {
			return json_encode(['errcode' => -101 , 'message' => $error]);
		}
	
		return json_decode($data, true);
	}
}