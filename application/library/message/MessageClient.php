<?php 
namespace app\library\message;
class MessageClient{
	
	private static $client;
	public static function getInstance(){
		if(empty(self::$client)){
			self::$client = new MessageClient();
		}
		return self::$client;
	}
	
	public function pushCache($message){
		$message = serialize($message);
		Cache::rpush("shuaibo_send_message",$message);
	}
	
	public function send($message){
		if(get_class($message) != "app\\library\\message\\Message"){
			return ['errcode' => -101, 'message' => '消息类型错误'];
		}
		
		$platforms = $message->getPlatform();
		if(in_array(Message::PLATFORM_ALL, $platforms)){
			$this->push($message);
			$this->send_message($message);
			//$this->send_sms($message);
			return ['errcode' => 0,'message' => '发送完毕'];
		}
		if(in_array(Message::PLATFORM_PUSH, $platforms)){
			$this->push($message);
		}
		
		if(in_array(Message::PLATFORM_MESSAGE, $platforms)){
			$this->send_message($message);
		}
		
		if(in_array(Message::PLATFORM_SMS, $platforms)){
			$this->send_sms($message);
		}
		
		if(in_array(Message::PLATFORM_WEIXIN, $platforms)){
		}
		return ['errcode' => 0,'message' => '发送完毕'];
		
	}
	
	private function push($message){
		$jpush = \app\library\SettingHelper::get("shuaibo_jpush",['is_open' => 0]);
		if($jpush['is_open'] == 0){
			return ['errcode' => -101, 'message' =>'推送未开启'];
		}
		$extra = $message->getPushExtra();
		
		$customer_ids = $message->getTargetIds();
	
		$customer_id = 0;
		if(!empty($customer_ids) ){
			$customer_id = $customer_ids[0];
		}
		
		if(!isset($extra['title'])){
			return ['errcode' => -101,'message' => '请传入标题'];
		}
		if(!isset($extra['content'])){
			return ['errcode' => -101,'message' => '请传入内容'];
		}
		$title = $extra['title'];
		$content = $extra['content'];
		$sys = isset($extra['is_sys']) ? $extra['is_sys'] : 0;
		unset($extra['title']);
		unset($extra['content']);
		
		$action_id = $message->getAction();
		$action = \app\library\SettingHelper::get_action($action_id);
		
		$ex = [];
		if(!empty($extra)){
			foreach ($extra as $k => $e){
				$ex[] = ['key' => $k , 'value' => $e];	
			}
		}
		empty($action) && $action = ['ios_param' => '','android_param' => '','need_login' => 0];
		vendor("JPush.JPush");
		$dir = getcwd() . "/data/jpush/" . date('Y_m_d') . "/";
		mkDirs($dir);
		$client = new \JPush($jpush['app_key'], $jpush['app_secret'],$dir . "jpush.log");
		$client = $client->push()
		->setPlatform('all')
		->setNotificationAlert($content)
		->addAndroidNotification($content, $title, 1, array_merge(['params' => $ex],['jump' => $action['android_param'], 'need_login' => $action['need_login']]))
		->addIosNotification($content, \JPush::DISABLE_SOUND, 1, true, 'iOS category', array_merge(['params' => $ex],['jump' => $action['ios_param'] , 'need_login' => $action['need_login']]));
		if($customer_id > 0){
			$customer = M("customer")->where(['customer_id' => $customer_id])->field("jpush_token,need_push")->find();
			if (!empty($customer)){
				if($customer['need_push'] == 0){
					return ['errcode' => -101,'message' =>'该用户禁止发送消息'];
				}
				if(empty($customer['jpush_token'])){
					return ['message' => '该用户暂未注册极光推送' , 'errcode' => -102];
				}
				$client->addRegistrationId($customer['jpush_token']);
				try{
					$res = $client->send();
				}catch(\Exception $e){
				}
			}
		}else{
			$client->addAllAudience();
			$res = $client->send();
			
		}
		return ['errcode' => 0, 'message' => '发送成功'];
	}
	

	private function send_message($message){

		$target_type = $message->getTargetType();
		$extra = $message->getPushExtra();
		
		if(!isset($extra['title']) || !isset($extra['content'])){
			return ;
		}
		
		$action_id = $message->getAction();

		$title = $extra['title'];
		$content = $extra['content'];
		unset($extra['title']);
		unset($extra['content']);
		
		$new_message = ["title" =>$title , "content" => $content ,"date_add" => time(), 'action_id' => $action_id, 'params' => serialize($extra)]; //内容

        if (!empty($extra['order_sn'])) {
            $new_message['order_sn'] = $extra['order_sn'];
        }
        if (!empty($extra['type'])) {
            $new_message['type'] = $extra['type'];
        }

		$message_id = M("message") -> add($new_message);

		if($target_type == Message::TARGET_ALL){
			//发送给全部
			$dataList[] = ["message_id" =>  $message_id, "tag_id" => 0];
			if(!empty($dataList)){
				M("message_tag")->addAll($dataList);
			}
		}else if($target_type == Message::TARGET_CUSTOMERS){
			//发送给个别用户
			$customer_ids = $message -> getTargetIds();
			$dataList = [];
			foreach ($customer_ids as $l){
				if($l>0){
					$dataList[] = ["message_id" => $message_id  , "customer_id" => $l];
				}
			}
			if(!empty($dataList)){
				M("message_customer")->addAll($dataList);
			}
		}else if($target_type == Message::TARGET_TAGS){
			//发送给个别标签
			$target_ids = $message -> getTargetIds();

			$tags_is_and = $message->getTagsIsAnd();

			if($tags_is_and == true){
				$count = count($target_ids);
				$data = ["and_or_or" => 1,"tags_count" =>$count];
				M("message") ->where(["message_id" => $message_id]) -> save($data);
			}
			
			$dataList = [];
			foreach ($target_ids as $l){
				if($l>0){
					$dataList[] = ["message_id" =>  $message_id, "tag_id" => $l];
				}
			}
			if(!empty($dataList)){
				M("message_tag")->addAll($dataList);
			}
		}
	}




	private function send_sms($message){
		$customer_ids = $message->getTargetIds();

		$customer_id = $customer_ids[0];
		$phone = M("customer")->where(['customer_id' => $customer_id])->getField("phone");
		if(empty($phone)){
			return ['errcode'=> -101 , 'message' => '该用户未绑定手机'];
		}
		$extra = $message->getExtra();
		if(!isset($extra['content'])){
			return ['errcode' => -102 , 'message' => '请传入短信内容'];
		}
		$content = $extra['content'];
		$helper = \app\library\SmsHelper::getInstance();
		return $helper->send($phone, $content);
	}
	
	private function send_weixin($message){
		$template = $message->getTemplate();
		if(empty($template)){
			return ['errcode' => -102,'message' => '请传入模板'];
		}
		$customer_ids = $message->getTargetIds();
		
		$customer_id = $customer_ids[0];
		
		$extra = $message->getWeixinExtra();
		
		$openid = "";
		if(!isset($extra['openid']) || !isset($extra['has_openid']) ){
			$openid = M("customer")->where(['customer_id' => $customer_id,'need_push' => 1, 'is_subscribe' => 1])->getField("wx_gz_openid");
		}else{
			$openid = $extra['openid'];
		}
		if(empty($openid)){
			return ['errcode' => -101, 'message' => '用户未绑定绑定公众号'];
		}
		
		$asyn = $message->getIsAsyn();
		$extra['openid'] = $openid;
		$template = \app\library\weixin\template\Template::getTemplateByName($template, $extra);
		$mid = microtime_float();
		if(empty($template)){
			return ['errcode' => -103 ,'message' => '找不到对应的模板'];
		}
		$client = \app\library\weixin\WeixinClient::getInstance();
		
		$res = $client->sendTemplate($template, $asyn);
		return $res;
	}
	
	
}