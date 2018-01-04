<?php
namespace app\library\chat\message;
class ChatCardMessage extends ChatMessage{
	
	const CARD_USER = 1;
	
	const CARD_GROUP = 2;
	
	const CARD_MOMENT = 3;
	
	const CARD_ARTICLE = 4;
	
	const CARD_ESSAY = 5;
	
	public function __construct( $to, $id, $type){
		parent::__construct($to);
		$this->setAttr("type", "card");
		$this->setAttr("card_type", $type);
		if(self::CARD_USER == $type){
			$customer = D("customer")->GetUserInfo($id, "uuid, avater, nickname,introduce");
			$this->setAttr("image", $customer['avater']);
			$this->setAttr("content", $customer['introduce'] ? $customer['introduce'] : '他什么都没有写');
			$this->setAttr("title", $customer['nickname'] . "的名片");
			$this->setAttr("watch", "用户详情");
			$this->setAttr("id", $customer['uuid']);
		}else if(self::CARD_GROUP == $type){
			$groups = D("groups")->getOneRecordById($id, "group_id, image, name,desc");
			$this->setAttr("image", $groups['image']);
			$this->setAttr("content", $groups['desc']);
			$this->setAttr("watch", "圈子详情");
			$this->setAttr("title", $groups['name'] . "的名片");
			$this->setAttr("id", $groups['group_id']);
		}else if(in_array($type, [self::CARD_ARTICLE, self::CARD_ESSAY, self::CARD_MOMENT])){
			$article = D("article")->getOneRecordById($id);
			$content = "";
			$title = "";
			$watch = "";
			switch ($type){
				case self::CARD_MOMENT:
					$customer = D("customer")->GetUserInfo($article['customer_id'], "nickname");
					$title = $customer['nickname'] . "的动态";
					$content = $article['content'];
					$watch = "动态详情";
					BREAK;
				case self::CARD_ARTICLE:
					$content = $article['first_content'] ? $article['first_content'] : '文章详情';
					$title = $article['title'] ;
					$watch = "文章详情";
					
					BREAK;
				case self::CARD_ESSAY:
					$content = $article['first_content'] ? $article['first_content'] : '文章详情';
					$title = $article['title'];
					$watch = "资讯详情";
					BREAK;
			}
			
			$host = \app\library\SettingHelper::get("leader_image_url");
			
			$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
			
			$this->setAttr("title", $title);
			$this->setAttr("image", $host . $article['first_image'] . $suffix);
			$this->setAttr("content", $content);
			$this->setAttr("watch", $watch);
			$this->setAttr("id", $article['article_id']);
		}
		
	}
	
	
	public function getMobParams(){
		return ['type' => 'txt'];
	}
	
	public function replace_emoji($str) {
		$new_str = preg_replace_callback(
				'/./u',
				function (array $match) {
					return strlen($match[0]) >= 4 ? '' : $match[0];
				},
				$str);
	
		return $new_str;
	}
}