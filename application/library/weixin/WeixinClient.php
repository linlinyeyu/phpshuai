<?php
namespace app\library\weixin;
use think\Cache;
use think\Log;
use app\library\weixin\msg\TextMessage;
class WeixinClient{
	
	protected $getTokenUrl = "https://api.weixin.qq.com/cgi-bin/token"; 
	
	public $types = array(
			'view', 'click', 'scancode_push',
			'scancode_waitmsg', 'pic_sysphoto', 'pic_photo_or_album',
			'pic_weixin', 'location_select'
	);
	
	protected $appid;
	
	protected $appsecret;
	
	protected $token;
	
	public function valid(){
		$echoStr = I("echostr");
		if($this->checkSignature()){
			header('content-type:text');
			ob_clean();
			echo $echoStr;
			exit;
		}
	}
	
	private static $instance = [];
	
	
	
	public static function getInstance($config = []){
		if(empty($config)){
			$config = \app\library\SettingHelper::get("bear_weixin_gz_config",[]);
		}
		$key = md5(serialize($config));
		if(!isset(self::$instance[$key])){
			self::$instance[$key] = new WeixinClient($config);
		}
		return self::$instance[$key];
	}
	
	private function __construct($config = []){
		if(empty($config)){
			$config = \app\library\SettingHelper::get("bear_weixin_gz_config",[]);
		}
		$this->appid = $config['appid'];
		$this->appsecret = $config['app_secret'];
		if(isset($config['token'])){
			$this->token = $config['token'];
		}
		
	}
	
	public function dealMessage(){
		$xml = file_get_contents("php://input");
		
		/*$xml = <<<EOF
<xml><ToUserName><![CDATA[gh_04e4e467e0d5]]></ToUserName>
<FromUserName><![CDATA[oRlQ8uCLAMb01_ag2E2R0mtwTltw]]></FromUserName>
<CreateTime>1476327355</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[我的物流]]></Content>
<Event><![CDATA[SCAN]]></Event>
<EventKey><![CDATA[7079899]]></EventKey>
<Ticket><![CDATA[gQG58DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BFTVZUZURtRzd0aU9lM0VhbXVDAAIEpgr_VwMEAAAAAA==]]></Ticket>
</xml>
EOF;*/
		$params = [];
		if(!empty($xml)){
			$params = $this->xmlstr_to_array($xml);
		}
		
		if(empty($params)){
			return;
		}
		$clsname = "";
		
		if($params['MsgType'] == 'event'){
			$clsname = "\\app\\library\\weixin\\event\\" . ucfirst(strtolower($params['Event']) ) . "Event";
		}else{
			$clsname = "\\app\\library\\weixin\\msg\\" . ucfirst(strtolower($params['MsgType'])) . "Message";
		}
		if(class_exists($clsname)){
			$cls = new $clsname();
			$params['access_token'] = $this->getAccessToken();
			$msg = $cls->notify($params);
			if(!empty($msg)){
				return $this->sendMessage($msg);
			}
		}
		if($params['MsgType'] != 'event'){
			$seller_info = \app\library\SettingHelper::get("shuaibo_seller_info",['address' => '杭州市' ,'qq' => '123456789']);
			$msg = new TextMessage();
			$msg->setParams("FromUserName", $params['ToUserName']);
			$msg->setParams("ToUserName", $params['FromUserName']);
			$msg->setParams("Content", "如您有任何疑问，欢迎拨打客服热线：" . $seller_info['qq']);
			$res = $this->sendMessage($msg);
			return $res;
		}
		
		return;
	}
	
	public function sendMessage($message){
		if(!is_subclass_of($message, "\\app\\library\\weixin\\msg\\Message")){
			return ;
		}
		$params = $message->getParams();
		if(empty($params) || !isset($params['ToUserName'])){
			return;
		}
		$params['CreateTime'] = time();
		$xml = $this->arrayToXml($params);
		return $xml;
	}
	
	/**
	 * 获取access_token
	 * 当appid或appsecret为空或者错误时，会给出相应错误提示
	 * */
	public function getAccessToken($force = false){
		$token = Cache::get("bear_weixin_access_token_" . $this->appid);
		if(!empty($token) && !$force){
			return $token;
		}
		$url = $this->getTokenUrl;
		$params = ['grant_type' => 'client_credential',
				'appid' => $this->appid,
				'secret' => $this->appsecret
		];
		$data = $this->http($url, $params);
		$data = json_decode($data, true);
		if(isset($data['access_token'])){
			Cache::set("bear_weixin_access_token_". $this->appid, $data['access_token'], $data['expires_in']);
			return $data['access_token'];
		}else if(isset($data['errmsg'])){
			throw new \Exception($data['errmsg']);
		}
		throw new \Exception("请求失败");
	}
	
	public function createMenu($menu){
		$dat = $this->buildMenu($menu);
		$token = $this->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$token}";
		$content = $this->http($url, $dat,"POST");
		$content = json_decode($content, true);
		return $content;
	}
	
	public function buildMenu($menu){
		$set = array();
		$set['button'] = array();
		foreach($menu as $m) {
			$entry = array();
			$entry['name'] = $m['title'];
			if(!empty($m['subMenus'])) {
				$entry['sub_button'] = array();
				foreach($m['subMenus'] as $s) {
					$e = array();
					if ($s['type'] == 'url') {
						$e['type'] = 'view';
					} elseif (in_array($s['type'], $this->types)) {
						$e['type'] = $s['type'];
					} else {
						$e['type'] = 'click';
					}
					$e['name'] = $s['title'];
					if($e['type'] == 'view') {
						$e['url'] = $s['url'];
					} else {
						$e['key'] = $s['forward'];
					}
					$entry['sub_button'][] = $e;
				}
			} else {
				if ($m['type'] == 'url') {
					$entry['type'] = 'view';
				} elseif (in_array($m['type'], $this->types)) {
					$entry['type'] = $m['type'];
				} else {
					$entry['type'] = 'click';
				}
				if($entry['type'] == 'view') {
					$entry['url'] = $m['url'];
				} else {
					$entry['key'] = $m['forward'];
				}
			}
			$set['button'][] = $entry;
		}
		$dat = json_encode($set,JSON_UNESCAPED_UNICODE);
		return $dat;
	}
	
	public function create_qrcode($uuid){
		$token = $this->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token={$token}";
		$dat = ['action_name' => "QR_LIMIT_STR_SCENE", 'action_info' => ['scene' => ['scene_str' => $uuid]]];
		$content = $this->http($url, $dat,"POST", true);
		return json_decode($content , true);
	}
	
	/**
	 xml转成数组
	 */
	protected function xmlstr_to_array($xmlstr) {
		$ob= simplexml_load_string($xmlstr,'SimpleXMLElement', LIBXML_NOCDATA);
		return json_decode(json_encode($ob), true);
	}
	
	
	//数组转xml
    public function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
             if (is_numeric($val))
             {
                $xml.="<".$key.">".$val."</".$key.">"; 

             }
             else
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";  
        }
        $xml.="</xml>";
        
        return $xml; 
    }
    
    
	/**
     * 发送HTTP请求方法，目前只支持CURL发送请求
     * @param  string $url    请求URL
     * @param  array  $params 请求参数
     * @param  string $method 请求方法GET/POST
     * @return array  $data   响应数据
     */
    protected function http($url, $params, $method = 'GET',  $json = false, $header = [], $asyn = false)
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
                //判断是否传输文件
                $params                   = $json ?  json_encode($params, JSON_UNESCAPED_UNICODE) : $params;
                $opts[CURLOPT_URL]        = $url;
                $opts[CURLOPT_POST]       = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                throw new \Exception('不支持的请求方式！');
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

        return $data;
    }
    
    function doRequest($url, $param=array()){       
    	$urlinfo = parse_url($url);       
    	$host = $urlinfo['host'];   
    	$path = $urlinfo['path'];   
    	$query = isset($param)? http_build_query($param) : '';       
    	$port = 80;   
    	$errno = 0;   
    	$errstr = '';   
    	$timeout = 10;       
    	$fp = fsockopen($host, $port, $errno, $errstr, $timeout);       
    	$out = "POST ".$path." HTTP/1.1\r\n";   
    	$out .= "host:".$host."\r\n";   
    	$out .= "content-length:".strlen($query)."\r\n";   
    	$out .= "content-type:application/x-www-form-urlencoded\r\n";  
    	$out .= "connection:close\r\n\r\n";   
    	$out .= $query;       
    	fputs($fp, $out);   
    	fclose($fp); 
    }
    
    private function checkSignature()
    {
    	if(!I("signature")){
    		return false;
    	}
    	$signature = I("signature");
    	$timestamp = I("timestamp");
    	$nonce = I("nonce");
    	$token = $this->token;
    	$tmpArr = array($token, $timestamp, $nonce);
    	sort($tmpArr,SORT_STRING);
    	$tmpStr = implode( $tmpArr );
    	$tmpStr = sha1( $tmpStr );
    
    	if( $tmpStr == $signature ){
    		return true;
    	}else{
    		return false;
    	}
    }
    
    public function sendTemplate($template, $asyn = false){
    	if(!is_subclass_of($template, "\\app\\library\\weixin\\template\\Template")){
    		return ['errcode' => -101 , 'message' => '模板消息不是Template子类，无法发送'];
    	}
    	$access_token = $this->getAccessToken();
    	$params = $template->getTemplate();
    	if($params['errcode'] != 0){
    		return $params;
    	}
    	
    	$params = $params['content'];
    	$url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
    	$res = $this->http($url, $params, "POST", true, [], $asyn);
    	$res = json_decode($res,true);
		
    	if($asyn){
    		return ['errcode' => 0 , 'message' =>'执行完毕'];
    	}
    	
    	return ['errcode' => $res['errcode'] * -1 , 'message' => $res['errmsg']];
    }
    
    public function sendCustomMessage($openid, $text){
    	
    	$access_token = $this->getAccessToken();
    	$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$access_token}";
    	$data = ['tourser' => $openid,'msgtype' => 'text','text'=>['content' => $text]];
    	$res = $this->http($url, $data , 'POST', true);
    }
    
    
	
}