<?php
namespace app\library\weixin\template;
abstract class Template{
	
	protected $openid;
	
	protected $url;
	
	protected $params;
	
	protected $tempalte_id;
	
	public static function getTemplateByName($name ,$params){
		$clsname =  "\\app\\library\\weixin\\template\\" . convertUnderline($name) ."Template";
		if(!class_exists($clsname)){
			return null;
		}
		return new $clsname($params);
	}
	
	public abstract function get_template();
	
	public function __construct($params){
		if(isset($params['openid'])){
			$this->openid = $params['openid'];
		}
		$templates = \app\library\SettingHelper::get("bear_weixin_templates",[]);
		if(isset($templates[$this->get_template()])){
			$this->tempalte_id = $templates[$this->get_template()];
		}
		$this->params = $params;
	}
	
	public function getTemplate(){
		$data = $this->getData();
		if($data['errcode'] != 0){
			return $data;
		}
		
		if(isset($this->params['ignore_url'])){
			$this->url = "";
		}
		
		$template = [
				'touser' => $this->openid,
				'template_id' => $this->tempalte_id,
				'data' => $data['content'],
				'url' => $this->url
		];
		
		return ['errcode' => 0, 'message' => 'success' , 'content' => $template];
	}
	
	public abstract function getData();
}