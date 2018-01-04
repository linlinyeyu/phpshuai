<?php
namespace app\app\model;
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
/**
 * 获取客户信息
 * @param unknown $customer_id
 * @param number $page
 */
	public function getCustomerMessage($customer_id,$page = 1 , $t = 0){
		$customer = M("customer")->where(array("customer_id" => $customer_id))-> field("date_add")->find();
		if(empty($customer)){
			return [];
		}
		$page = "";
		//终端
		switch ($t){
			//默认
			case 0:
				$page = ",a.ios_param,a.android_param,a.wap_param,a.web_param";
				break;
			//苹果终端
			case 1:
				$page = ",a.ios_param as page";
				break;
			case 2:
				$page = ",a.android_param as page";
				break;
			case 3:
				$page = ",a.wap_param as page";
				break;
			case 4:
				$page = ",a.web_param as page";
				break;
			default:
				$page = ",a.ios_param,a.android_param,a.wap_param,a.web_param";
				break;
		}
		
		
		$messages = $this
		->alias("m")
		->join("message_property mp","mp.message_id = m.message_id and mp.customer_id = ".$customer_id ,"LEFT")
		->join("action a"," a.action_id = m.action_id","LEFT")
		->field("m.message_id,m.content,m.title,m.date_add,m.action_id,m.params,m.customer_id, ifnull(mp.is_read , 0) as is_read" . $page)
		->limit(20)
		->page($page)
		->order("m.date_add desc")
		->where("m.date_add >= ". $customer['date_add']. " and (m.customer_id = ".$customer_id." or m.message_type = 1) and (mp.state = 1 or mp.state is null)")
		->select();
		return $messages;

	}
/**
 * 读取客户信息
 * @param unknown $customer_id
 * @param unknown $message_id
 */
	public function readMessage($customer_id, $message_id){
		$property = M("message_property")->where(array("customer_id" => $customer_id, "message_id" => $message_id))->find();
		$new_pro = array(
				'message_id' => $message_id,
				'read_time' => time(),
				'customer_id' => $customer_id,
				'is_read' => 1,
				'state' => 1
			);
		if(empty($property)){
			M("message_property")->add($new_pro);
		}else{
			M("message_property")->where(array("message_id" => $message_id, "customer_id" => $customer_id))->save($new_pro);
		}
	}
/**
 * 删除所有信息
 * @param unknown $customer_id
 */
	public function deletAllMessage($customer_id){
		$customer = M("customer")->where(array("customer_id" => $customer_id))->field("date_add")->find();
        if(empty($customer)){
        	return;
        }
        $messages = $this
        ->alias("m")
        ->join("message_property mp"," m.message_id = mp.message_id and mp.customer_id = ". $customer_id, "LEFT")
        ->where("m.date_add >= ".$customer['date_add'] . " and (m.customer_id =".$customer_id." or m.message_type = 1 ) and mp.message_id is null")
        ->field("m.message_id")
        ->select();        
        if(!empty($messages)){
        	for($i = 0; $i < count($messages) ; $i ++){
        		$id = $messages[$i]['message_id'];
        		$new_pro = array(
					'message_id' => $id,
					'read_time' => time(),
					'customer_id' => $customer_id,
					'is_read' => 1,
					'state' => 0
				);
				M("message_property")->add($new_pro);
        	}
        }
        M("message_property")
        ->where(array("customer_id" => $customer_id))
        ->save(array("is_read" => 1, "state" => 0));
	}
/**
 * 获取未读信息数量
 * @param unknown $customer_id
 */
	public function getUnReadCount($customer_id){
		$customer = M("customer")->where(array("customer_id" => $customer_id))->field("date_add")->find();
		if(empty($customer)){
			return 0;
		}
		return $this
		->alias("m")
		->join("message_property mp"," mp.message_id = m.message_id and mp.customer_id = ". $customer_id, "LEFT")
		->where("m.date_add >=".$customer['date_add']." and (m.customer_id = ".$customer_id." or m.message_type = 1) and (mp.is_read = 0 or mp.is_read is null)")
		->count();
	}
}