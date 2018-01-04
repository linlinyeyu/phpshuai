<?php
namespace app\admin\controller;

use app\admin\Admin;
class Help extends Admin{
	public function index(){
		$map = array();
		$title = I("title");
		if (!empty($title)){
			$map['title'] = ['like',"%$title%"];
		}
		$help = M("help")->where($map)->field("help_id,title,date_add,left(content,20) as content")->select();
		$this->assign("help",$help);
		$this->display();
	}
	
	public function edit(){
		$help_id = I("help_id");
		if(IS_POST){
			$res = false;
			$data['title'] = I("title");
			$data['content'] = htmlspecialchars(I('post.editorValue'), ENT_QUOTES);
			if ($help_id){
				$data['date_upd'] = time();
				$res = M("help")->where(['help_id' => $help_id])->save($data);
			}else {
				$data['date_add'] = time();
				$data['date_upd'] = time();
				$res = M("help")->add($data);
			}
			if ($res === false){
				$this->error("操作失败");
			}
			$this->success("修改成功",null,U("help/index"));
		}else {
			if ($help_id){
				$help = M("help")->where(['help_id' => $help_id])->find();
				
				$this->assign("help",$help);
				$this->display();
			}else {
				$help = array(
						'help_id' => 0,
						'title' => '',
						'content' => ''
				);
				
				$this->assign("help",$help);
				$this->display();
			}
		}
	}
	
	public function delete(){
		!($help_id = I("help_id")) && $this->error("请传入id");
		$res = M("help")->where(['help_id' => $help_id])->delete();
		
		if ($res === false){
			$this->error("操作失败");
		}
		$this->success("操作成功");
	}
}