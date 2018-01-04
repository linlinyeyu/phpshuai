<?php
namespace app\admin\controller;
use app\admin\Admin;
class Essay extends Admin
{
	public function index()
	{
		$map = ['e.is_deleted' => 0];
		
		$title = I("title");
		!empty($title) && $map['e.title'] = ['like', "%".$title ."%" ];
		$min_date = I("min_date");
		$max_date = I("max_date");
		
		if(!empty($min_date)){
			$map['e.date_add'] = [['egt', strtotime($min_date)]];
		}
		if(!empty($max_date)){
			if (!empty($map['e.date_add']) ) {
				$map['e.date_add'][] =  ['elt', strtotime($max_date) ];
			}else{
				$map['e.date_add'] = ['elt', strtotime($max_date)];
			}
		}
		
		$count = M("essay")->alias("e")->where($map)->count();
		$page = new \com\Page(15,$count);
		$host = $this->image_url;
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 100);
		$essays = M("essay")
		->alias("e")
		->join("user u","u.id = e.user_id")
		->order("e.date_add desc")
		->where($map)
		->field("e.*,u.username")
		->limit($page->firstRow ."," . $page->listRows)
		->select();
		$this->assign("essays", $essays);
		$this->assign("page", $page->show());
		$this->display();
	}

	public function add()
	{
		if(IS_POST){
			$id = I("post.id");
			$image = I("image");
			
			!($name = I("title")) && $this->error("请填入标题");
			!$image && $this->error("请传入图片");
			$text = htmlspecialchars(I('post.editorValue'), ENT_QUOTES);
			empty($text) && $this->error("请输入内容");
			$sort = (int)I("sort");
			$data = [
					'cover' => $image,
					'title' => $name,
					'sort' => (int)I("sort"),
					'content' => $text,
			];
			if(empty($id)){
				$data['user_id'] = session("userid");
				$data['date_add'] = time();
				$id = M("essay")->add($data);
				$this->log("添加文章：" . $data['title'] .";id：" . $id);
			}else{
				M("essay")->where(['id' => $id] )->save($data);
				$this->log("修改文章：" . $data['title'] .";id：" . $id);
			}
			$this->success("操作成功",null,U("essay/index"));
		}else{
			$id = I("get.id");
			if(!empty($id)){
				$essay = M("essay")->where(['id' => $id])->find();
			}else{
				$essay = M("essay")->getEmptyFields();
			}
			$this->assign("essay", $essay );
			$this->display();
		}
	}

	public function del()
	{
		!($id = (int)I('get.id')) && $this->error('参数错误');
		M('essay')->where(['id'=>$id])->save(['is_deleted' => 1]);
		$this->log("删除文章：" . M("essay")->where(['id' => $id])->getField("title"));
		$this->success("成功");
	}
//新增分类列表
	public function category(){
		$cat = M('essay_categorys');
		$res = $cat->where(array('parentid'=>0)) -> order('id asc') ->select();
		//print_r($res);
		//parent ID
		$categorys = array();
		foreach ($res as $key => $v) {
			$twos = $cat->where(array('parentid'=>$v['id'])) -> order('sort asc') ->select();
			$second = array();
			foreach ($twos as $key2 => $v2) {
				$three =  $cat->where(array('parentid'=>$v2['id'])) -> order('id asc') ->select();
				$second[$key2] = $v2;
				$second[$key2]['three'] = $three;
			}
			$categorys[$key] = $v;
			$categorys[$key]['two'] = $second;
		}
		$this->assign('categorys',$categorys);
		$this->display();
	}

	//添加分类
	public function toAddCategory(){
		$ecate = M('essay_categorys');
		$id = (int)I('get.id');
		$parentid = (int)I('get.parentid');
		if($id){
			// update
			$result = $ecate->where(array("id"=>$id))->find();
		}else{
			$result = $ecate->where(array("id"=>$parentid)) ->find();
			$result['id'] = '';
			$result['name'] = '';
			$result['article_number'] = '';
			$result['create_time'] = '';
			$result['update_time'] = '';
			$result['parentid'] = $parentid;
			$result['level'] = I('get.level');
			$result['sort'] = '';
			$result['active'] = 1;
		}
		$this->assign('info',$result);
		$this->display();
    }

    //将分类写入数据库(提交)
	public function addCategory(){
		$id = (int)I('post.id');
		$parentid = intval(I('post.parentid'));
		$ecate = M('essay_categorys');
		$data['name'] = I('post.name');
		$data['article_number'] = intval(I('post.article_number'));
		$data['create_time'] = time();
		$data['update_time'] = time();
		$data['parentid'] = $parentid;
		$data['level'] = I('post.level');
		$data['sort'] = I('post.sort');
		$data['active'] = I('post.active');
        if(I('post.level')) $data['level'] = I('post.level');
		if($id){
			//update
            D("user_action")->saveAction(session("userid"),6,$id,"修改分类".$id .$data['name']);
			$ecate->where(array('id'=>$id))->save($data);
			$json = ['code'=>1,'msg'=>'更新成功'];
		}else{
			//add
		  	$id = $ecate->add($data);
          	D("user_action")->saveAction(session("userid"),6,$id,"添加分类".$id .$data['name']);
          	$json = ['code'=>1,'msg'=>'添加成功'];
		}
				
		$this->success("操作成功", null, U('essay/category'));		
    }

	public function is_active()
    {
		!($id = (int)I('post.id')) && $this->error('参数错误!','',U('essay/category'));
     	$active = (int)I('post.active');
		$ecate = D('essay_categorys');
		$res = $ecate->where(array('id'=>$id))->save(array('active'=>$active));
		$res ? $json = array('status'=>1) : $json = array('status'=>0);
		$this->ajaxReturn($json);
    }

    public function edel()
    {
		!($id = (int)I('post.id')) && $this->error('参数错误!','',U('category/index'));	
		$this->dels($id);
		$json = array('status'=>1);
		$this->ajaxReturn($json);	
    }

	private function dels($id)
    {	
		$ecate = M('essay_categorys');
		$result = $ecate->where(array('id'=>$id))->find();
		
		if(count($result)>0) {
			D("user_action")->saveAction(session("userid"),6,0,"删除分类".json_encode($id));
			$ecate->where(array('id'=>$id))->delete();
			$ecates = $ecate->where(array('parentid'=>$result['id']))->select();
			foreach ($ecates as $key => $value) {
				$this->dels($value['id']);
			}
			
		}
    }

    //修改
	public function editName()
    {
		header('Content-type: application/json');
		isset($_POST['id']) && $id = (int)$_POST['id'];
		isset($_POST['catName']) && $name = h($_POST['catName']);
		$ecate = M('essay_categorys');
		$res = $ecate->where(array('id'=>$id))->save(array('name'=>$name));
		D("user_action")->saveAction(session("userid"),6,$id,"修改分类名为".$name);
		$res ? $json = array('status'=>1) : $json = array('status'=>0);
		echo json_encode($json);
    }        	
}
