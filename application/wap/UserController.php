<?php
namespace app\wap;
use think\Controller;
class UserController extends BaseController
{
    public function __construct(){
    	parent::__construct();
    	
    	if(empty($this->customer_id)){
    		$url = U("home/login");
    		header('Location: ' . $url);
    	}
    }

}