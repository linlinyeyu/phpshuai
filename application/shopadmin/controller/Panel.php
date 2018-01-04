<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;

class Panel extends Admin
{
	public function index()
	{
        $username = session('username');    // 用户名
        $seller_id   = session('sellerid');      // 商家id
        $menu = array();
        if (!$menu = S('panel_menu')){
        	$parent_menu = M("shop_node")
        	->where(['status' => 1 , 'display' => 1, 'level' => ['neq', "1"]])
        	->field("id, title, remark")
        	->order("sort")
        	->select();
        	$prefix = "";
        	if(APP_MULTI_MODULE){
        		$prefix = "/" . MODULE_NAME ;
        	}
        	
            // 主菜单
            foreach($parent_menu as $key=>$val)
            {
                 $second_menu = $this->left_child_menu($val['id']);
               	
            		$sub = array();
            		foreach($second_menu as $key=>$child)
            		{
            			//三级菜单
                      $third_menu = $this->left_child_menu($child['id']);
               
            			$sub_child = array();
            			foreach ($third_menu as $c) {
            				$sub_child[] = array(
            					'id'=> $c['id'],
            					'data'=> $prefix.  $c['data'],
            					'title'=>$c['title'],
            					);
            			}
            			$sub[] = array(
    	         			'id'=> $child['id'],
    	        			'data'=> $prefix. $child['data'],
    	        			'title'=>$child['title'],
    	        			'three'=>$sub_child      				
            				);
            		}
            		$menu[] = array(
            			'id'=> $val['id'],
            			'title'=>$val['title'],
                      'remark'=>$val['remark'],
            			'second_menu'=>$sub
            		);
            	}
            S('panel_menu',$menu);
         }
         $seller = [];
         if (!$seller = S("shopadmin_shop_".$seller_id)){
         	$seller = M("seller_shopinfo")->where(['seller_id' => session('sellerid')])->find();
         	S("shopadmin_shop_".$seller_id,$seller);
         }
         $this->assign("image_url", \app\library\SettingHelper::get("shuaibo_image_url"));
		$this->assign('menus',$menu);
		$this->assign("shop",$seller);
        $this->assign("seller_id",$seller_id);
		$this->display();
	}

    /**
     * 按父ID查找菜单子项
     * @param integer $parentid   父菜单ID  
     * @param integer $with_self  是否包括他自己
     */
    private function left_child_menu($pid, $with_self = 0) {
        $pid = intval($pid);
        $prefix = C("database.prefix");
        $result = [];
        
        $result = M("shop_node")
        ->where(['status' => 1 , 'display' => 2, 'level' => ['neq', "1"],"pid" => $pid])
        ->field("id,data, title, remark")
        ->order("sort")
        ->select();
        /*if($username == C('SPECIAL_USER')){     //如果是无视权限限制的用户，则获取所有主菜单
            $sql = "SELECT `id`,`data`,`title`,`remark` FROM `{$prefix}node` WHERE ( `status` =1 AND `display`=2 AND `level` <>1 AND `pid`=$pid ) ORDER BY sort DESC";
        }else{
            $sql = "SELECT `{$prefix}node`.`id` as `id` , `{$prefix}node`.`data` as `data`, `{$prefix}node`.`title` as `title` , `{$prefix}node`.`remark` as `remark` FROM `{$prefix}node`,`tp_access` WHERE `{$prefix}node`.id = `{$prefix}access`.node_id AND `{$prefix}access`.role_id = $roleid AND `{$prefix}node`.`pid` =$pid AND `{$prefix}node`.`status` =1 AND `{$prefix}node`.`display` =2 AND `{$prefix}node`.`level` <>1 ORDER BY `{$prefix}node`.sort DESC";
        }
        $result = M()->query($sql);*/
        if($with_self) {
            $NodeDB = D('shop_node');
            $result2[] = $NodeDB->getNode(array('id'=>$pid),`id`,`data`,`title`);
            $result = array_merge($result2,$result);
        }
        return $result;
    }
}