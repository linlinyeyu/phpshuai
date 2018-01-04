<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Feedback extends Admin
{
	public function index(){
		$count = M('feedback')->count();
		$page = new \com\Page($count, 15);
		$lists = M("feedback")->alias("f")
		->join("customer c","c.customer_id = f.customer_id")
		->field("f.feedback_id,f.date_add, c.nickname, f.content,f.customer_id")
		->order("f.date_add desc")
		->limit($page->firstRow . ',' . $page->listRows)
		->select();
		$this->assign("lists", $lists);
		$this->assign('page',$page->show());
		$this->display();
	}
}
