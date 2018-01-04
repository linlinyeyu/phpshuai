<?php
/**
*   
* author: oliver  
* date: 2016-3-5
*/
namespace app\admin\controller;
use app\admin\Admin;
class Category extends Admin {
    /*
    *
    * category list
    */
    public function index(){
    		$cat = M('category');
    		
    		//print_r($res);
    		//parent ID
    		$list = S("shuaibo_admin_categories");
    		$list = unserialize($list);
    		if (empty($list)){
    			$res = $cat->where(array('pid'=>0))->select();
    			foreach ($res as $key => $v) {
    				$twos = $cat->where(array('pid'=>$v['category_id']))->select();
    				$second = array();
    				foreach ($twos as $key2 => $v2) {
    					$three =  $cat->where(array('pid'=>$v2['category_id']))->select();
    					$second[$key2] = $v2;
    					$second[$key2]['three'] = $three;
    				}
    				$list[$key] = $v;
    				$list[$key]['two'] = $second;
    			}
    			S("shuaibo_admin_categories",serialize($list));
    		}
	
    		$this->assign('List',$list);
    		$this->display();
    }

    public function toEdit(){
    		$cat = M('category');
    		$id = (int)I('get.id');
    		$pid = (int)I('get.pid');
    		if($id){
    			// update
    			$result = $cat->where(array("category_id"=>$id))->find();
    		}else{
    			$result = $cat->where(array("category_id"=>$pid))->find();
              $result['level'] = I('get.level') + 1;
    			$result['pid'] = $pid;
    			$result['category_id'] = '';
    			$result['name'] = '';
    			$result['active'] = 1;
    			$result['date_add'] = '';
    			$result['sort'] = '';
    			$result['icon'] = "";
    			$result['selected_icon'] = '';
			
    		}
  	   		
    		$this->assign('info',$result);
    		$this->display();
    }	

    /*
    * 
    * insert  and  edit
    * return json string
    */
    public function edit(){
    		$cat_id = (int)I('post.category_id');
    		$pid = (int)I('post.pid');
    		$category = M('category');
    		$data['name'] = I('post.name');
    		$data['pid'] = $pid;
    		$data['level'] = (int)I('post.level');
            $data['icon'] = h(I("post.icon"));
            $data['selected_icon'] = h(I("post.selected_icon"));
    		$data['sort'] = (int)I('post.sort');
    		$data['date_add'] = time();
    		$data['active'] = (int)I('post.active');
            if(I('post.level')) $data['level'] = I('post.level');
    		if($cat_id){
    			//update
                D("user_action")->saveAction(session("userid"),6,$cat_id,"修改菜单".$cat_id .$data['name']);
    			$category->where(array('category_id'=>$cat_id))->save($data);
    			$json = ['code'=>1,'msg'=>'更新成功'];
    		}else{
    			//add
    		  $cat_id = $category->add($data);
              D("user_action")->saveAction(session("userid"),6,$cat_id,"添加菜单".$cat_id .$data['name']);
              $json = ['code'=>1,'msg'=>'添加成功'];
    		}
    		S("shuaibo_admin_categories",null);
    		$this->success("操作成功", null, U('category/index'));
    }

    public function is_active()
    {
    		!($id = (int)I('post.id')) && $this->error('参数错误!','',U('category/index'));
         $active = (int)I('post.active');
    		$category = D('category');
    		$res = $category->where(array('category_id'=>$id))->save(array('active'=>$active));
    		$res ? $json = array('status'=>1) : $json = array('status'=>0);
    		S("shuaibo_admin_categories",null);
    		$this->ajaxReturn($json);
    }

    public function del()
    {
    		!($id = (int)I('post.id')) && $this->error('参数错误!','',U('category/index'));
    		
    		$this->dels($id);
    		$json = array('status'=>1);
    		S("shuaibo_admin_categories",null);
    		$this->ajaxReturn($json);
    }
    /*
    * @param $id  
    * @return mixed
    */
    private function dels($id)
    {	
    		$category = M('category');
    		$result = $category->where(array('category_id'=>$id))->find();
    		
    		if(count($result)>0) {
    			D("user_action")->saveAction(session("userid"),6,0,"删除菜单".json_encode($id));
    			$category->where(array('category_id'=>$id))->delete();
    			$cats = $category->where(array('pid'=>$result['category_id']))->select();
    			foreach ($cats as $key => $value) {
    				$this->dels($value['category_id']);
    			}
    			
    		}
    		S("shuaibo_admin_categories",null);
    }
    public function editName()
    {
    		header('Content-type: application/json');
    		isset($_POST['id']) && $id = (int)$_POST['id'];
    		isset($_POST['catName']) && $name = h($_POST['catName']);
    		$category = M('category');
    		$res = $category->where(array('category_id'=>$id))->save(array('name'=>$name));
    		D("user_action")->saveAction(session("userid"),6,$id,"修改菜单名为".$name);
    		$res ? $json = array('status'=>1) : $json = array('status'=>0);
    		S("shuaibo_admin_categories",null);
    		echo json_encode($json);
    }
}