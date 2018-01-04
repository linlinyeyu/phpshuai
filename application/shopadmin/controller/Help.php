<?php
namespace app\shopadmin\controller;

use app\shopadmin\Admin;
class Help extends Admin{
	public function index(){
		$map = array();
		$title = I("title");
		if (!empty($title)){
			$map['title'] = ['like',"%$title%"];
		}
		$help = M("help")->where($map)->field("help_id,title,date_add,content")->select();
		$this->assign("help",$help);
		$this->display();
	}
}