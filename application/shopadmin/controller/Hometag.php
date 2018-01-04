<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Hometag extends Admin
{
	public function index()
	{	
		$count = M("home_tag")->where(['status' => 1])->count();
		$page = new \com\Page(15,$count);
		$host = $this->image_url;
		
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(100, 100);
		$tags = M("home_tag")
		->alias("t")
		->join("action a","a.action_id = t.action_id","LEFT")
		->field("t.id, t.sort,t.name, IFNULL(a.name, '无动作') as action_name,concat('$host', t.icon,'$suffix') as image,a.action_id,t.params")
		->order("t.sort, t.date_add desc")
		->where(['status' =>1])
		->limit($page->firstRow ."," . $page->listRows)
		->select();
		\app\library\ActionTransferHelper::transfer($tags);
		$this->assign("tags", $tags);
		$this->assign("page", $page->show());
		$this->display();
	}

	public function add()
	{
		if(IS_POST){
			$id = I("post.id");
			!($icon = I("icon")) && $this->error("请传入图标");
			
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
					'icon' => $icon,
					'name' => $name,
					'sort' => (int)I("sort"),
					'date_add' => time(),
					'action_id' => $action_id,
					'params' => $params
			];
			if(empty($id)){
				$id = M("home_tag")->add($data);
				$this->log("添加标签：" . $data['name'] .";id：" . $id);
			}else{
				M("home_tag")->where(['id' => $id] )->save($data);
				$this->log("修改标签：" . $data['name'] .";id：" . $id);
			}
			D("home_tag")->SetTags();
			$this->success("操作成功");
		}else{
			$id = I("get.id");
			
			if(!empty($id)){
				$tag = M("home_tag")->where(['id' => $id])->find();
			}else{
				$tag = M("home_tag")->getEmptyFields();
			}
			$params = \app\library\ActionTransferHelper::get_action_params($tag);
			
			$goods = M("goods")->where(['is_deleted' => 0])->select();
			$essay = M("essay")->where(['is_deleted' => 0])->field("title, id,date_add")->select();
			$this->assign("essay", $essay);
			$action_id = $tag['action_id'];
			$this->assign("action_id", $action_id ? $action_id : 1);
			$this->assign("goods", $goods);
			$this->assign("params", $params);
			$this->assign("tag", $tag );
			$this->display();
		}
	}
	
	public function listorders(){
		$orders  = I("listorders/a");
		if(!empty($orders)){
			foreach ($orders as $k => $o){
				M("home_tag")->where(['id' => $k])->save(['sort' => $o]);
			}
		}
		$this->success("操作成功");
	}
	
	public function del()
	{
		!($id = (int)I('get.id')) && $this->error('参数错误');
		M('home_tag')->where(['id'=>$id])->save(['status' => 0]) ? $this->success('删除成功') : $this->error('删除失败');
		D("home_tag")->SetTags();
		$this->log("删除标签：" . M("home_tag")->where(['id' => $id])->getField("name") .";id：". $id);
		$this->success("操作成功");
	}

}
