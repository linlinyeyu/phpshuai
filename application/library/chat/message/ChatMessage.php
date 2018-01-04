<?php

namespace app\library\chat\message;

abstract class ChatMessage{
	
	const SINGLE = 1;
	
	const GROUP = 2;
	
	const CHATROOM = 3;
	
	private $target_type = self::SINGLE;
	
	private $from ;
	
	private $to = [];
	
	private $extra = [];
	
	public function __construct($to){
		$this->setTo($to);
	}
	
	public function setFrom($from){
		$this->from = $from;
	}
	
	public function getFrom(){
		return $this->from;
	}
	
	
	public function setTo($to){
		if(!is_array($to)){
			$to = [$to];
		}
		
		$this->to = $to;
	}
	
	public function getTo(){
		return $this->to;
	}
	
	public function setAttr($name , $value){
		if(isset($this->extra)){
			$this->extra[$name] = $value;
		}
	}
	
	public function removeAttrs($name){
		if(isset($this->extra[$name])){
			unset($this->extra[$name]);
		}
	}
	
	public function getAttr($name){
		if(isset($this->extra[$name])){
			return $this->extra[$name];
		}
		return null;
	}
	
	public function setTargetType($target_type){
		$this->target_type = $target_type;
	}
	
	public function getTargetType(){
		return $this->target_type ;
	}
		
	public function getExtras(){
		return $this->extra;
	}
	
	public function setExtra($extra = []){
		if(is_array($extra)){
			$this->extra = $extra;
		}
	}
	
	public abstract function getMobParams();
}