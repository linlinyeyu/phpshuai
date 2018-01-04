<?php
namespace app\library\weixin\msg;
class TextMessage extends Message{
	private $msg = "";
	
	public function __construct(){
		$this->setParams("MsgType", "text");
	}
	
	public function notify($params){

		$reply=array();
		$content = $params['Content'];
		if(!empty($content)){
			$results = M("auto_reply")
				->alias("ar")
				->join("auto_reply_keyword ark","ar.id = ark.group_id")
				->order("match_rule asc")
				->select();
			

			foreach ($results as $res){
				switch ($res['match_rule']){
					case 1:
						if($res['keyword'] == $content){//精确匹配
							$reply=$res;
						}
						break;
					case 2:
						break;
					case 3:
						if(strpos($content,$res['keyword']) !== false){
							$reply=$res;
						}
						break;
				}
				if(!empty($reply)) {
					break;
				}
			}

		}

		if(empty($reply)){//查询结果为空
			return ;
		}else{
			if($reply['reply_type']=="1")  //回复文字信息
			{
				$msg = new TextMessage();
				/*
				 *  <xml>
					<ToUserName><![CDATA[toUser]]></ToUserName>
					<FromUserName><![CDATA[fromUser]]></FromUserName>
					<CreateTime>12345678</CreateTime>
					<MsgType><![CDATA[text]]></MsgType>
					<Content><![CDATA[你好]]></Content>
					</xml>
				 */
				$msg->setParams("FromUserName", $params['ToUserName']);
				$msg->setParams("ToUserName", $params['FromUserName']);
				$msg->setParams("Content", $reply['content']);
				return $msg;
			}

		}
	}
}