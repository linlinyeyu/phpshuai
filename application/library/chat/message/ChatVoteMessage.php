<?php
namespace app\library\chat\message;
class ChatVoteMessage extends ChatMessage{
	
	private $text ;
	
	public function __construct($text, $to, $id){
		parent::__construct($to);
		$this->text = $text;
		$this->setAttr("vote_id", $id);
		$this->setAttr("type", "vote");
		$vote = D("vote")->getOneRecordById($id, "type");
		if(!empty($vote)){
			$this->setAttr("vote_type", $vote['type']);
		}
		
	}
	
	public function getText(){
		return $this->text;
	}
	
	public function setText($text){
		$this->text = $text;
	}
	
	public function getMobParams(){
		return ['type' => 'txt', 'msg' => $this->text];
	}
}