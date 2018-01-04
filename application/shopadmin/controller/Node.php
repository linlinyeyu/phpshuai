<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Node extends Admin
{
	//菜单列表
	public function index(){
		
		$html_tree = $this->_Tree();
		$this->assign('html_tree',$html_tree);
		$this->display();
	}

	//添加菜单
	public function add(){
		if(IS_POST) {
			$NodeDB = D("shop_node");
			$pid = (int)I("pid");
			//根据表单提交的POST数据创建数据对象
			//!I('action') && $this->error('缺少参数!','',U('node/index'));
			$action = I('action');
			$level = (int)I('level');
			$p = explode("/", $action);
			$name = '';
			if ($level == 2) {
				(count($p)<2) && $this->error('缺少参数!');
				$name = $p[0];
			}elseif ($level == 3)
			{
				(count($p)<2) && $this->error('缺少参数!');
				$name = $p[1];
			}
			$data = [
				'pid' => (int)I('pid'),
				'name' => $name,
				'title' => I('title'),
				'status' => (int)I('status'),
				'remark' => I('remark'),
				'level' => $level,
				'data' => '/'.$action,
				'display' => (int)I('display')
			];
			
			$res = D('shop_node')->add($data);
				if($res){
    					$this->success('添加成功！','',U('node/index'));
				}else{
					 $this->error('添加失败!');
				}

			
		}else{
			$Node = D('shop_node')->getAllNode();
			$pid = I('get.pid','intval');	//选择子菜单
			$array = array();
			foreach($Node as $k => $r) {
				$r['id']         = $r['id'];
				$r['title']      = $r['title'];
				$r['name']       = $r['name'];
				$r['disabled']   = $r['level']==3 ? 'disabled' : '';
				$array[$r['id']] = $r;
			}
			$str  = "<option value='\$id' \$selected \$disabled >\$spacer \$title</option>";
			$Tree = new \com\Tree();
			$Tree->init($array);
			$select_categorys = $Tree->get_tree(0, $str, $pid);

			$this->assign('select_categorys',$select_categorys);
			$this->display();
		}

	}

	//编辑菜单
	public function edit(){
		$NodeDB = D('shop_node');
		if(IS_POST) {
			//根据表单提交的POST数据创建数据对象
			$action = I('action');
			$level = (int)I('level');
			$p = explode("/", $action);
			$name = '';
			if ($level == 2) {
				(count($p)<2) && $this->error('缺少参数!');
				$name = $p[0];
			}elseif ($level == 3)
			{
				(count($p)<2) && $this->error('缺少参数!');
				$name = $p[1];
			}
			$pid = (int)I("pid");
			$data = [
				'pid' => (int)I('pid'),
				'name' => $name,
				'title' => I('title'),
				'status' => (int)I('status'),
				'remark' => I('remark'),
				'level' => $level,
				'data' => '/'.$action,
				'display' => (int)I('display')
			];
			$res = D('shop_node')->where(['id'=>(int)I('id')])->save($data);
				if($res){
    					$this->success('添加成功！','',U('node/index'));
				}else{
					 $this->error('添加失败!');
				}
			
		}else{
			$id = I('get.id','intval');
			$pid = I('get.pid','intval');	//选择子菜单
			if(!$id)$this->error('参数错误!');
			$allNode = $NodeDB->getAllNode();
			$array = array();
			foreach($allNode as $k => $r) {
				$r['id']         = $r['id'];
				$r['title']      = $r['title'];
				$r['name']       = $r['name'];
				$r['disabled']   = $r['level']==3 ? 'disabled' : '';
				$array[$r['id']] = $r;
			}
			$str  = "<option value='\$id' \$selected \$disabled >\$spacer \$title</option>";
			$Tree = new \com\Tree();
			$Tree->init($array);
			$select_categorys = $Tree->get_tree(0, $str, $pid);
			$info = $NodeDB->getNode('id='.$id);
			if($info['data']){
				$param = explode("/", $info['data']);

				if(isset($param[2])){
					$info['action'] = $param[1].'/'.$param[2]; 
				}else{
					$info['action'] = '';
				}
				if(isset($param[3])){
					$info['param'] = $param[3];
				}else{
					$info['param'] = '';	
				}
			}else{
				$info['action'] = ''; 
				$info['param'] = '';
			}
			
			$this->assign('select_categorys',$select_categorys);
			$this->assign('info', $info);
			$this->display();
		}

	}
	
	//删除菜单
	public function del(){
		$id = I('get.id','intval');
		if(!$id)$this->error('参数错误!');
		$NodeDB = D('shop_node');
		$info = $NodeDB -> getNode(array('id'=>$id),'id');
		if($NodeDB->childNode($info['id'])){
			$this->error('存在子菜单，不可删除!');
		}
		if($NodeDB->delNode('id='.$id)){
			$this->assign("jumpUrl",U('node/index'));
			$this->success('删除成功！');
		}else{
			$this->error('删除失败!');
		}
	}
	public function lists()
	{
		$html_tree = $this->_Tree();
		$this->assign('menus',$html_tree);
		$this->display();
	}

	//菜单排序权重更新
	public function sort(){
		$sorts = I('post.sort/a');
		if(!is_array($sorts))
			$this->error(L('_PARAM_ERROR_'));
		foreach ($sorts as $id => $sort) {
			D('shop_node')->upNode( array('id' =>$id , 'sort' =>intval($sort) ) );
		}
		$this->assign("jumpUrl",U('node/index'));
		$this->success(L('UPDATE_SUCCESS'));
	}

	public function get_child($Node,$myid) {
        $a = $newarr = array();
            foreach ($Node as $id => $a) {
                if ($a['pid'] == $myid)
                    $newarr[$id] = $a;
            }
        return $newarr ? $newarr : false;
    }
    
    private function digui($Node, $pid){
    	$child = $this->get_child($Node, $pid);
    	if(!empty($child)){
    		foreach($child as $k => $v){
    			$childs = $this->digui($Node, $v['id']);
    			$child[$k]['childs'] = $childs;
    		}
    	}
    	return $child;
    }
    
    private function get_digui(&$nodes){
    	$arr = [];
    	
    	foreach ($nodes as $k => &$node ){
    		$childs = [];
    		if(!empty($node['childs'])){
    			$childs = $node['childs'];
    		}
    		unset($node['childs']);
    		$arr[] = $node;
    		$arr = array_merge($arr,$this->get_digui($childs)) ;
    	}
    	return $arr;
    }
	
	private function _Tree()
	{
		if (!$Node = S('shopadmin_menus')){
         	$Node = D('shop_node')->getAllNode();
			S('shopadmin_menus',$Node);
         }
         $Node = $this->digui($Node, 0);
         $Node =  $this->get_digui($Node);
         
		// 构建生成树中所需的数据
		foreach($Node as $k => $r) {
			
			$Node[$k]['id']      = $r['id'];
			$Node[$k]['title']   = $r['title'];
			$Node[$k]['name']    = $r['name'];
			$Node[$k]['status']  = $r['status']==1 ? '<font color="red">√</font>' :'<font color="blue">×</font>';
			$Node[$k]['submenu'] = $r['level']==3 ? '<font color="#cccccc">'.L('ADD_SUB_MENU').'</font>' : '<a href="' . U("node/add", array("pid" => $r['id'])) .'">'.L('ADD_SUB_MENU').'</a>';
			$Node[$k]['edit']    = $r['level']==1 ? '<font color="#cccccc">'.L('EDIT').'</font>' : '<a href="' . U("node/edit", array("id" => $r['id'], "pid" => $r['pid'])) . '">'.L('EDIT').'</a>';
			$Node[$k]['del']     = $r['level']==1 ? '<font color="#cccccc">'.L('DELETE').'</font>' : '<a class="js-ajax-delete" href="' . U("node/del", array("id" => $r['id']) ). '">'.L('DELETE').'</a> ';
			$Node[$k]['pid']     = ($r['pid']) ? ' class="child-of-node-' . $r['pid'] . '"' : '';
			switch ($r['display']) {
				case 0:
					$Node[$k]['display'] = '不显示';
					break;
				case 1:
					$Node[$k]['display'] = '主菜单';
					break;
				case 2:
					$Node[$k]['display'] = '子菜单';
					break;
			}
			switch ($Node[$k]['level']) {
				case 0:
					$Node[$k]['level'] = '非节点';
					break;
				case 1:
					$Node[$k]['level'] = '应用';
					break;
				case 2:
					$Node[$k]['level'] = '模块';
					break;
				case 3:
					$Node[$k]['level'] = '方法';
					break;
			}

		}

		$str = "<tr id='node-\$id' \$pid>
					<td style='padding-left:20px;'><input name='sort[\$id]' type='text' size='3' value='\$sort' class='input input-order'></td>
					<td>\$id</td>
					<td>\$spacer \$title \$name</td>
					<td>\$level</td> 
				    <td>\$status</td>
				    <td>\$display</td> 
					<td>\$submenu | \$edit | \$del</td>
				</tr>";
  		$Tree = new \com\Tree();
		$Tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
		$Tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$Tree->init($Node);
		$html_tree = $Tree->get_tree(0, $str);
		return $html_tree;
	}

	public function bulidsort()
	{

		$nodes = $this->recurse();
		$this->success(L('BULID_SORT_SUCCESS'));
	}

	private function recurse($pid=0)
	{
		$nodes = D('shop_node')->where(array('pid'=>$pid))->order('id ASC')->select();
		static $count = 0;
		foreach($nodes as $k=>$node){	
			$count++;
			$nodes[$k] = $node;
			$nodes[$k]['sort'] = $count;
			D('shop_node')->where(array('id'=>$node['id']))->save(array('sort'=>$count));
			$nodes[$k]['child'] = $this->recurse($node['id']);
		}
		return $nodes;
	}
}