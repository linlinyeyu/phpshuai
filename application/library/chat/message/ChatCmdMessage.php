<?php
namespace app\library\chat\message;
class ChatCmdMessage extends ChatMessage{
	
	const ACTION_UPDATE_CUSTOMER = "update_customer";
	
	const ACTION_UPDATE_GROUP = "update_group";
	
	const ACTION_SYSTEM = "system";
	
	private $action ;
	
	public function __construct($action, $to){
		parent::__construct($to);
		$this->action = $action;
	}
	
	public function getMobParams(){
		return ['type' => 'cmd', 'action' => $this->action];
	}
}