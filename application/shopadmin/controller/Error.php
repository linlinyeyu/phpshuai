<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Error extends Admin
{
	public function index()
	{
		if( CONTROLLER_NAME == strtolower(C("default_controller"))){
			$this->redirect(U("login/index"));
		}else{
			die("404 http code");
		}
		
	}
}