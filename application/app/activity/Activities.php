<?php
namespace app\app\activity;

abstract class Activities{
	protected $page = 1;
	protected $type = 0;
	protected $ip = '';
	protected $terminal = 1;
	
	public function __construct($page = 1,$type=0,$ip="",$terminal=1){
		$this->page = $page;
		$this->type = $type;
		$this->ip = $ip;
		$this->terminal = $terminal;
	}
	
	public static function checkMethod($page,$type,$ip="",$terminal){
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
		
		$clsname = "\\app\\app\\activity\\".$method."Activity";
		if(class_exists($clsname)){
			return new $clsname($page,$type,$ip,$terminal);
		}
	}
	
	public abstract function getActivity();
}