<?php
namespace app\admin\controller;
use app\admin\Admin;
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