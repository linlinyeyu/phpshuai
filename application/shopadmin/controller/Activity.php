<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Activity extends Admin
{
	
	public function index()
	{
		$count = M("activity")->where(['status' => 1])->count();
		$page = new \com\Page(15,$count);
		$host = $this->image_url;
		
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(100, 100);
		$activity = M("activity")
		->alias("ac")
		->join("action a","a.action_id = ac.action_id","LEFT")
		->field("ac.id, ac.sort,ac.name, IFNULL(a.name, '无动作') as action_name,concat('$host', ac.image,'$suffix') as image,a.action_id,ac.params")
		->order("ac.sort, ac.date_add desc")
		->where(['status' =>1])
		->limit($page->firstRow ."," . $page->listRows)
		->select();
		
		\app\library\ActionTransferHelper::transfer($activity);
		$this->assign("activity", $activity);
		$this->assign("page", $page->show());
		$this->display();
	}

	public function add()
	{
		if(IS_POST){
			$id = I("post.id");
			!($icon = I("image")) && $this->error("请传入图片");
			
			!($name = I("name")) && $this->error("请传入名字");
			
			!($action_id = I("action_id")) && $this->error("请传入类型");
			
			$params = I("params");
			if(!empty($params)){
				$params = json_decode($params, true);
				
				if($action_id == 4 && $params['goods_id'] == 0){
					$this->error("请选择商品");
				}
				
				if(isset($params['goods_id']) && $params['goods_id'] == 0){
					unset($params['goods_id']);
				}
				$params = serialize($params);
			}
			
			$data = [
					'image' => $icon,
					'name' => $name,
					'sort' => (int)I("sort"),
					'date_add' => time(),
					'action_id' => $action_id,
					'params' => $params
			];
			if(empty($id)){
				$id = M("activity")->add($data);
				$this->log("添加活动：" . $data['name'] .";id：" . $id);
			}else{
				M("activity")->where(['id' => $id] )->save($data);
				$this->log("修改活动：" . $data['name'] .";id：" . $id);
			}
			D("activity")->SetActivity();
			$this->success("操作成功");
		}else{
			$id = I("get.id");
			
			if(!empty($id)){
				$activity = M("activity")->where(['id' => $id])->find();
			}else{
				$activity = M("activity")->getEmptyFields();
			}
			$params = \app\library\ActionTransferHelper::get_action_params($activity);
			$goods = M("goods")->where(['is_deleted' => 0])->field("name, goods_id")->select();
			$essay = M("essay")->where(['is_deleted' => 0])->field("title, id,date_add")->select();
			$this->assign("essay", $essay);
			$action_id = $activity['action_id'];
			$this->assign("action_id", $action_id ? $action_id : 1);
			$this->assign("params", $params);
			$this->assign("goods", $goods);
			$this->assign("activity", $activity );
			$this->display();
		}
	}
	
	public function listorders(){
		$orders  = I("listorders/a");
		if(!empty($orders)){
			foreach ($orders as $k => $o){
				M("activity")->where(['id' => $k])->save(['sort' => $o]);
			}
		}
		D("activity")->SetActivity();
		$this->success("操作成功");
	}
	
	public function del()
	{
		!($id = (int)I('get.id')) && $this->error('参数错误');
		M('activity')->where(['id'=>$id])->save(['status' => 0]) ? $this->success('删除成功') : $this->error('删除失败');
		D("activity")->SetActivity();
		$this->log("删除活动：" . M("activity")->where(['id' => $id])->getField("name") .";id：". $id);
		$this->success("操作成功");
	}

}
