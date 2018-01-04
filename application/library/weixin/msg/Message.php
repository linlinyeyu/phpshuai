<?php
namespace app\library\weixin\msg;
abstract class Message{
	protected $params = [];
	
	public abstract function notify($params);
	
	public function getParams(){
		return $this->params;
	}
	
	public function setParams($key , $value){
		$this->params[$key] = $value;
		return $this;
	}
	
}