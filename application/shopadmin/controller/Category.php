<?php
/**
*   
* author: oliver  
* date: 2016-3-5
*/
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Category extends Admin {
    /*
    *
    * category list
    */
    public function index(){
    		$seller_id = session("sellerid");
    		$cat = M('seller_category');
    		$res = $cat->where(array('pid'=>0,"seller_id" => $seller_id))->select();
    		//print_r($res);
    		//parent ID
    		$list = array();
    		foreach ($res as $key => $v) {
    			$twos = $cat->where(array('pid'=>$v['seller_cat_id'],'seller_id' => $seller_id))->select();
    			$second = array();
    			foreach ($twos as $key2 => $v2) {
    				$three =  $cat->where(array('pid'=>$v2['seller_cat_id'],'seller_id' => $seller_id))->select();
    				$second[$key2] = $v2;
    				$second[$key2]['three'] = $three;
    			}
    			$list[$key] = $v;
    			$list[$key]['two'] = $second;

    		}
    		
    
    		$this->assign('List',$list);
    		$this->display();
    }

    public function toEdit(){
    		$cat = M('seller_category');
    		$id = (int)I('get.id');
    		$pid = (int)I('get.pid');
    		if($id){
    			// update
    			$result = $cat->where(array("seller_cat_id"=>$id))->find();
    		}else{
    			$result = $cat->where(array("seller_cat_id"=>$pid))->find();
              	$result['level'] = I('get.level');
    			$result['pid'] = $pid;
    			$result['seller_cat_id'] = '';
    			$result['cat_name'] = '';
    			$result['is_show'] = 1;
    			$result['date_add'] = '';
    			$result['sort_order'] = '';
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
    		$cat_id = (int)I('post.seller_cat_id');
    		$pid = (int)I('post.pid');
    		$category = M("seller_category");
    		$data['cat_name'] = I('post.cat_name');
    		$data['pid'] = $pid;
    		$data['sort_order'] = (int)I('post.sort_order');
    		$data['is_show'] = (int)I('post.is_show');
            if(I('post.level')){
            	$data['level'] = I('post.level')+1;
            } 
    		if($cat_id){
    			//update
    			$data['date_upd'] = time();
                D("seller_action")->saveAction(session("id"),6,$cat_id,"修改菜单".$cat_id .$data['cat_name']);
    			$category->where(array('seller_cat_id'=>$cat_id))->save($data);
    			$json = ['code'=>1,'msg'=>'更新成功'];
    		}else{
    			//add
    		  $data['date_add'] = time();
    		  $data['seller_id'] = session("sellerid");
			  $cat_id = $category->add($data);
              D("seller_action")->saveAction(session("id"),6,$cat_id,"添加菜单".$cat_id .$data['cat_name']);
              $json = ['code'=>1,'msg'=>'添加成功'];
    		}
    		$this->success("操作成功", null, U('category/index'));
    }

    public function is_active()
    {
    		!($id = (int)I('post.id')) && $this->error('参数错误!','',U('category/index'));
         $active = (int)I('post.active');
    		$category = D('seller_category');
    		$res = $category->where(array('seller_cat_id'=>$id))->save(array('is_show'=>$active));
    		$res ? $json = array('status'=>1) : $json = array('status'=>0);
    		$this->ajaxReturn($json);
    }

    public function del()
    {
    		!($id = (int)I('post.id')) && $this->error('参数错误!','',U('category/index'));
    		
    		$this->dels($id);
    		$json = array('status'=>1);
    		$this->ajaxReturn($json);
    }
    /*
    * @param $id  
    * @return mixed
    */
    private function dels($id)
    {	
    		$category = M('seller_category');
    		$result = $category->where(array('seller_cat_id'=>$id))->find();
    		
    		if(count($result)>0) {
    			D("seller_action")->saveAction(session("id"),6,0,"删除菜单".json_encode($id));
    			$category->where(array('seller_cat_id'=>$id))->delete();
    			$cats = $category->where(array('pid'=>$result['seller_cat_id']))->select();
    			foreach ($cats as $key => $value) {
    				$this->dels($value['seller_cat_id']);
    			}
    			
    		}
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
    		echo json_encode($json);
    }
}