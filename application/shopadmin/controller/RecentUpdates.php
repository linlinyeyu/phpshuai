<?php
namespace app\shopadmin\controller;

use app\shopadmin\Admin;
class RecentUpdates extends Admin{
	public function detail(){
		!($update_id = I("update_id")) && $this->error("请传入id");
		$detail = M("recent_updates")->where(['update_id' => $update_id])->find();
		
		$this->assign("detail",$detail);
		$this->display();
	}
}