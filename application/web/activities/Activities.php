<?php
namespace app\web\activities;

abstract class Activities{
	protected $page = 1;
	protected $type = 0;
	protected $ip = '';
	
	public function __construct($page = 1,$type=0,$ip=""){
		$this->page = $page;
		$this->type = $type;
		$this->ip = $ip;
	}
	
	public static function checkMethod($page,$type,$ip=""){
		$special_type = S("shuaibo_special_type");
		$special_type = unserialize($special_type);
		if (empty($special_type)){
			$special_type = D("special_model")->getSpecialType();
			S("shuaibo_special_type",serialize($special_type));
		}
		
		$method = "";
		if (!empty($special_type)){
			foreach ($special_type as $v){
				if ($v['type'] == $type){
					$method = $v['activities'];
					break;
				}
			}
		}
		
		$clsname = "\\app\\web\\activities\\".$method."Activity";
		if(class_exists($clsname)){
			return new $clsname($page,$type,$ip);
		}
	}
	
	public abstract function getActivity();
}