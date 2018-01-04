<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
use org\upload;
class Banner extends Admin
{
	public function index()
	{
		$position =  (int)I("position");
		$count = M("banner")->where(['status' => 1])->count();
		$page = new \com\Page(15,$count);
		$host = $this->image_url;
		
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 100);
		$banners = M("banner")
		->alias("b")
		->join("action a","a.action_id = b.action_id","LEFT")
		->field("b.banner_id, b.sort,b.name, IFNULL(a.name, '无动作') as action_name,concat('$host', b.image,'$suffix') as image,a.action_id,b.params")
		->order("b.sort, b.date_add desc")
		->where(['status' =>1, 'position' => $position])
		->limit($page->firstRow ."," . $page->listRows)
		->select();
		\app\library\ActionTransferHelper::transfer($banners);
		$this->assign("banners", $banners);
		$this->assign("page", $page->show());
		$this->display();
	}

	public function web_banner()
	{
		$position =  2;
		$count = M("banner")->where(['status' => 1])->count();
		$page = new \com\Page(15,$count);
		$host = $this->image_url;

		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 100);
		$banners = M("banner")
			->alias("b")
			->join("action a","a.action_id = b.action_id","LEFT")
			->field("b.banner_id, b.sort,b.name, IFNULL(a.name, '无动作') as action_name,concat('$host', b.image,'$suffix') as image,a.action_id,b.params")
			->order("b.sort, b.date_add desc")
			->where(['status' =>1, 'position' => $position])
			->limit($page->firstRow ."," . $page->listRows)
			->select();
		\app\library\ActionTransferHelper::transfer($banners);
		$this->assign("banners", $banners);
		$this->assign("page", $page->show());
		$this->display();
	}

	public function listorders(){
		$orders  = I("listorders/a");
		if(!empty($orders)){
			foreach ($orders as $k => $o){
				M("banner")->where(['banner_id' => $k])->save(['sort' => $o]);
			}
		}
		D("banner")->SetBanners(0);
		$this->success("操作成功");
	}
	public function add()
	{
		$position = (int)I("position");
		if(IS_POST){
			$id = I("post.id");
			$image = I("image");
			
			!($name = I("name")) && $this->error("请传入名字");
			!$image && $this->error("请传入图片");
			
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
					'image' => $image,
					'name' => $name,
					'sort' => (int)I("sort"),
					'action_id' => $action_id,
					'params' => $params,
					'position' => $position
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
			$this->success("操作成功");
		}else{
			$id = I("get.id");
			
			if(!empty($id)){
				$banner = M("banner")->where(['banner_id' => $id])->find();
			}else{
				$banner = M("banner")->getEmptyFields();
			}
			\app\library\ActionTransferHelper::get_action_params($banner);
			$goods = M("goods")->where(['is_deleted' => 0])->select();
			$essay = M("essay")->where(['is_deleted' => 0])->field("title, id,date_add")->select();
			$this->assign("essay", $essay);
			$action_id = $banner['action_id'];
			$params = $banner['params'];
			$this->assign("banner", $banner );
			$this->assign("action_id", $action_id ? $action_id : 1);
			$this->assign("goods", $goods);
			$this->assign("params", $params);
			$this->display();
		}
	}
	public function web_banner_add()
	{
		$position = 2;
		if(IS_POST){
			$id = I("post.id");
			$image = I("image");

			!($name = I("name")) && $this->error("请传入名字");
			!$image && $this->error("请传入图片");

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
				'image' => $image,
				'name' => $name,
				'sort' => (int)I("sort"),
				'action_id' => $action_id,
				'params' => $params,
				'position' => $position
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
			$this->success("操作成功");
		}else{
			$id = I("get.id");

			if(!empty($id)){
				$banner = M("banner")->where(['banner_id' => $id])->find();
			}else{
				$banner = M("banner")->getEmptyFields();
			}
			\app\library\ActionTransferHelper::get_action_params($banner);
			$goods = M("goods")->where(['is_deleted' => 0])->select();
			$essay = M("essay")->where(['is_deleted' => 0])->field("title, id,date_add")->select();
			$this->assign("essay", $essay);
			$action_id = $banner['action_id'];
			$params = $banner['params'];
			$this->assign("banner", $banner );
			$this->assign("action_id", $action_id ? $action_id : 1);
			$this->assign("goods", $goods);
			$this->assign("params", $params);
			$this->display();
		}
	}

	public function edit()
	{
		if(IS_POST){
			!I('post.name') && $this->error('横幅名称不能为空');
			$data = ['banner_name'=>I('post.name'),'banner_key'=>I('post.key'),'banner_image'=>I('post.image'),'type'=>I('post.type'),'sort'=>(int)I('post.sort'),'position'=>(int)I('post.position')];
			D("user_action")->saveAction(session("userid"),5,I("post.id"),"修改轮播图".I("post.id")."为".I("post.name"));
			M('banner')->where(['banner_id'=>(int)I('post.id')])->save($data) ? $this->success('横幅修改成功','',U('banner/index')) : $this->error('横幅修改失败');
		}else{
			!($id = (int)I('get.id')) && $this->error('参数错误');
			$banner = M('banner')->where(['banner_id'=>$id])->find();
			$this->assign('banner',$banner);
			$this->display();
		}
	}

	public function del()
	{
		$position = (int)I("position");
		!($id = (int)I('get.id')) && $this->error('参数错误');
		$this->log("删除轮播图". I("get.id"));
		M('banner')->where(['banner_id'=>$id])->delete() ;
		D("banner")->SetBanners($position);
		$this->success("成功");
	}
}
