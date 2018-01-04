<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Rank extends Admin{
	public function index()
	{
		$count = M("banner")->where(['status' => 1])->count();
		$page = new \com\Page(15,$count);
		$host = $this->image_url;
	
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 100);
		$banners = M("banner")
		->alias("b")
		->join("action a","a.action_id = b.action_id","LEFT")
		->field("b.banner_id, b.sort,b.name, IFNULL(a.name, '无动作') as action_name,concat('$host', b.image,'$suffix') as image,a.action_id,b.params")
		->order("b.sort, b.date_add desc")
		->where(['status' =>1, 'position' => 1])
		->limit($page->firstRow ."," . $page->listRows)
		->select();
		\app\library\ActionTransferHelper::transfer($banners);
		$this->assign("banners", $banners);
		$this->assign("page", $page->show());
		$this->display();
	}
	
	public function add()
	{
		$position = 1;
		if(IS_POST){
			$id = I("post.id");
			!($icon = I("image")) && $this->error("请传入图片");
				
			!($name = I("name")) && $this->error("请传入名字");
				
				
			!($action_id = I("action_id")) && $this->error("请传入动作类型");
				
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
					'action_id' => $action_id,
					'params' => $params,
					'position' => 1,
					'status' => 1
			];
			if(empty($id)){
				$data['date_add'] = time();
				$id = M("banner")->add($data);
				$this->log("添加轮播图：" . $data['name'] .";id：" . $id);
			}else{
				M("banner")->where(['banner_id' => $id] )->save($data);
				$this->log("修改轮播图：" . $data['name'] .";id：" . $id);
			}
			$banners = D("banner")->SetBanners($position);
			$this->success("操作成功", U("rank/index"));
		}else{
			$id = I("get.id");
				
			if(!empty($id)){
				$banner = M("banner")->where(['banner_id' => $id])->find();
			}else{
				$banner = M("banner")->getEmptyFields();
			}
			\app\library\ActionTransferHelper::get_action_params($banner);
			$essay = M("essay")->where(['is_deleted' => 0])->field("title, id,date_add")->select();
			$this->assign("essay", $essay);
			$goods = M("goods")->where(['is_deleted' => 0])->select();
			$action_id = $banner['action_id'];
			$params = $banner['params'];
			$this->assign("goods", $goods);
			$this->assign("banner", $banner );
			$this->assign("action_id", $action_id ? $action_id : 1);
			$this->assign("params", $params);
			$this->assign("goods_name", isset($banner['goods_name']) ? $banner['goods_name'] : '');
			$this->display();
		}
	}
	
	public function week_list(){
		$rank = \app\library\SettingHelper::get_rank(1);
		$this->assign("rank", $rank);
		$this->assign("type", 0);
		$this->display("rank/list");
	}
	
	public function month_list(){
		$rank = \app\library\SettingHelper::get_rank(2);
		$this->assign("rank", $rank);
		$this->assign("type", 1);
		$this->display("rank/list");
	}
	
	public function total_list(){
		$rank = \app\library\SettingHelper::get_rank(0);
		$this->assign("rank", $rank);
		$this->assign("type", 2);
		$this->display("rank/list");
	}
	
	public function refresh(){
		\app\library\SettingHelper::set_rank(0);
		\app\library\SettingHelper::set_rank(1);
		\app\library\SettingHelper::set_rank(2);
		$this->success("刷新成功");
	}
}