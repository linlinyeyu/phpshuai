<?php
namespace app\shopadmin\model;
use think\Model;
class Message extends Model{
/**
 * 发送信息
 * @param unknown $customer_id
 * @param unknown $title
 * @param unknown $content
 * @param number $type
 * @param string $id
 */	
	public function send($customer_id,$title, $content, $type = 0, $id = null){
		$param = array(
				'customer_id' => $customer_id,
				'title' => $title,
				'content' => $content,
				'type' => $type,
				'foreign_id' => $id,
				'date_add' => time(),
				'is_sys' => 0,
				'message_type' => 0
			);
		$id = $this->add($param);

		$new_pro = array(
				'message_id' => $id,
				'customer_id' => $customer_id,
				'is_read' => 0,
				'state' => 1
			);

		M("message_property") ->add($new_pro);

		vendor("JPush.JPush");
		$client = new \JPush(C("jpush.app_key"), C("jpush.app_secret"));
		$param  = array(); 
		if(!empty($type)){
			$param['type'] = $type;
		}
		if(!empty($id)){
			$param['id'] = $id;
		}
		$client = $client->push()
		    ->setPlatform('all')
		    ->setNotificationAlert($content)
		    ->addAndroidNotification($content, $title, 1, $param)
		    ->addIosNotification($content, \JPush::DISABLE_SOUND, 1, true, 'iOS category', $param);
		if($customer_id > 0){
			$customer = M("customer")->where(['customer_id' => $customer_id])->field("uuid, jpush_token")->find();
			if (!empty($customer['jpush_token'])){
				$client->addRegistrationId($customer['jpush_token']);
				try{
					$client->send();
				}catch(\Exception $e){
				}
			}
		}else{
			$client->addAllAudience();
			$client->send();
		}
	}
}