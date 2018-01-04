<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Express extends Admin
{
    public function index(){
        $express=M("express")
        ->alias('e')
        ->field("e.*")
        ->order("e.sort, e.express_id asc")
        ->select();
        $this->assign("express",$express);
        $this->display();
    }
    public function add(){
        $id = I("get.id");
        if(IS_POST){
            !I("post.company_name")&&$this->error("请输入公司名");
            !I("post.code") && $this->error("请输入快递编号");
            $company_name=I("post.company_name");
            $company_url=I("post.company_url");
            $code = I("post.code");
            $id = I("get.id");
            $add_order_show = I("post.add_order_show");
            $data = [
                     'express_name'=>$company_name,
            		'code' => $code,
                    'company_url' => $company_url,
                    'add_order_show'=>$add_order_show];
            if(empty($id)){
                $data['date_add'] = time();
                $result = M('express')->add($data);
            }else{
                $result = M("express")->where(['express_id' => $id])->save($data);
            }
            $this->success('添加成功');
        }
        $express = M("express")->getEmptyFields();
        if(!empty($id)){
            $express = M("express")->alias('e')->where(['express_id' => $id])->field('e.*')->find();
        }
        $this->assign("express", $express);
        $this->display();
    }
    public function del(){
        $map=array();
        !($id=I('get.id')) && $this->error("操作失败");
        $map['express_id']=$id;
        
        
        $name = M("express")->where(['express_id' => $id])->getField("express_name");
        
        $result=M("express")
        ->where($map)
        ->delete();
        \app\library\SettingHelper::set_express();
        $this->log("删除快递{$name}：编号为：{$id}");
        $this->success("操作成功");
    }
    
    public function index_template()
    {
        $seller_id = session("sellerid");
        $count = M("express_template")->count();
    	$page = new \com\Page($count, 15);
    	$express = M("express_template")
    	    ->alias("et")
            ->where(['seller_id' => $seller_id])
    	    ->join("zone z", "et.province_id = z.zone_id","LEFT")
    	    ->limit($page->firstRow . ',' . $page->listRows)
    	    ->field("et.*, z.name as province_name")
    	    ->order("date_add desc")
    	    ->select();
    	$this->assign("express",$express);
    	$this->assign("page",$page->show());
    	$this->display();
    }
    
    public function add_template()
    {
    	$express_list = [];
    	$id = I("id");
    	$template = ['template_id'=> 0, 'name' => '','province_id' => '1', 'first' => 0, 'first_weight' => 0, 'additional' => 0, 'additional_weight' => 0];
    	if(IS_POST){
    		!($name = I('post.name')) && $this->error('名称不能为空');
    		!($province_id = I("post.province_id")) && $this->error("发货地不能为空");
            $first = I("post.first");
            $first_weight = I("post.first_weight");
//    		!($first = I("post.first")) && $this->error("首费不能为空");
//    		!($first_weight = I("post.first_weight")) && $this->error("首重不能为空");
    		$additional = I("post.additional");
    		$additional_weight = I("additional_weight");
            $seller_id = session("sellerid");

            $items = I("express");
    		
    		$items = json_decode($items, true);
    		
    		$data = [
    		        'seller_id' => $seller_id,
    				'name' => $name,
    				'province_id' => $province_id,
    				'first' => $first,
    				'first_weight' => $first_weight,
    				'additional' => $additional,
    				'additional_weight' => $additional_weight,
    				'items' => serialize($items)
    		];
    		if(empty($id)){
    			$data['date_add'] = time();
    			$id = M("express_template")->add($data);
    		}else{
    			M("express_template") ->where(['template_id' => $id])->save($data);
    		}
    		//D("user_action")->saveAction(session("userid"),5,$id,"添加轮播图".I("post.name"));
    		$id ? $this->success('添加成功','',U('express/index_template')) : $this->error('添加失败');
    	}else{
    		if(!empty($id)){
    			$template = M("express_template")->where(['template_id' => $id])->find();
    			$express_list = $template['items'];
    			if(!empty($express_list)){
    				$express_list = unserialize($express_list);
                    foreach ($express_list as $key => $val){
                        if (!empty($val['province_list']) && $val['province_list'] != '') {
                            $express_list[$key]['province_name'] = join(",", M("zone")->where(["zone_id" => ["in" , $val['province_list']]])->getField("name", true)) ;
                        }
    				}
    			}
    			
    		}
    	}
    	$area_group = M("area_group")->alias("ag")
    	->join("zone z","z.area_group_id = ag.area_group_id")
    	->where("z.level = 1")
    	->field("z.name , z.zone_id as province_id,ag.area_group_id, ag.name as group_name")
    	->select();
    	$area = [];
    	foreach($area_group as $val){
    		$data = ['name' => $val['name'],'province_id'=> $val['province_id'], 'checked' => false, 'disabled' => false];
    		$checked = false;
    		foreach( $area as $key => $a){
    			if($a['area_group_id'] == $val['area_group_id']){
    				$area[$key]['lists'][] = $data;
    				$checked = true;
    				break;
    			}
    		}
    		if(!$checked){
    			$area[] = ['area_group_id' => $val['area_group_id'], 'name'=> $val['group_name'], 'checked' => false, 'disabled' => false, 'lists' => [$data]];
    		}
    	}
    	
    	
    	if(!$express_list){
    		$express_list = [];
    	}
    	$province = M("zone")->where(['level' => 1])->select();
    	$this->assign("province", json_encode($province));
    	$this->assign("template", $template);
    	$this->assign("area", json_encode($area));
    	$this->assign("express_list", json_encode($express_list));
    	$this->display();
    }

    public function del_template(){
        !($id=I('get.id')) && $this->error("操作失败");
        M("express_template")->where(['template_id' => $id])->delete();

        $this->success("操作成功");
    }

    public function is_add_order_show()
    {
        !($id = (int)I('post.id')) && $this->error('参数错误!','',U('express/index'));
        $value = (int)I('post.value');
        $category = D('express');
        $res = $category->where(array('express_id'=>$id))->save(array('add_order_show'=>$value));
        $res ? $json = array('status'=>1) : $json = array('status'=>0);
        $this->ajaxReturn($json);
    }


    public function listorders(){
        $orders  = I("listorders/a");
        if(!empty($orders)){
            foreach ($orders as $k => $o){
                M("express")->where(['express_id' => $k])->save(['sort' => $o]);
            }
        }
        $this->success("操作成功");
    }

}