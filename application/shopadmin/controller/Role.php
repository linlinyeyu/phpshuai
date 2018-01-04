<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Role extends Admin
{
	public function index()
	{
		$map = array();
		$count=D('role')->count();
		$page = new \com\Page($count, 15);
		$roles = D('role')->where($map)
		->limit($page->firstRow . ',' . $page->listRows)
		->select();

		$this->assign("page", $page->show());
		$this->assign("roles",$roles);
		$this->display();;
	}

	public function add()
	{
        $RoleDB = D("Role");
        if(IS_POST) {
            //根据表单提交的POST数据创建数据对象
            if($RoleDB->create()){
            	$id = $RoleDB->add();
                if($id){
                	D("user_action")->saveAction(session("userid"),11,$id,"添加角色". I("post.name"));
                    $this->success('添加成功！','',U('role/index'));
                }else{
                     $this->error('添加失败!');
                }
            }else{
                $this->error($RoleDB->getError());
            }
        }else{
            $this->display();
        }
	}
	//权限浏览
	public function access()
	{
        $roleid = (int)I('get.id');
        !$roleid && $this->error('参数错误!');
        
        $Tree = new \com\Tree();
        $Tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
        $Tree->nbsp = '&nbsp;&nbsp;&nbsp;';

        $node = D('node')->select();

	     $AccessDB = D('Access');
		$access = $AccessDB->getAllAccess('','role_id,node_id,pid,level');

        foreach ($node as $n=>$t) {
            $node[$n]['checked'] = ($AccessDB->is_checked($t,$roleid,$access))? ' checked' : '';
            $node[$n]['depth'] = $AccessDB->get_level($t['id'],$node);
            $node[$n]['pid_node'] = ($t['pid'])? ' class="tr lt child-of-node-'.$t['pid'].'"' : '';
        }
        $str  = "<tr id='node-\$id' \$pid_node>
                    <td style='padding-left:30px;'>\$spacer<input type='checkbox' name='nodeid[]' value='\$id' class='radio' level='\$depth' \$checked onclick='javascript:checknode(this);' > \$title \$name</td>
                </tr>";

        $Tree->init($node);
        $html_tree = $Tree->get_tree(0, $str);
        $this->assign('roleid',$roleid);
        $this->assign('html_tree',$html_tree);

        $this->display();
	}

	public function edit()
	{
        $roleid = (int)I('post.roleid');
        $nodeid = I('post.nodeid/a');
        !$roleid && $this->error('参数错误!');
        $AccessDB = D('Access');

        if (is_array($nodeid) && count($nodeid) > 0) {  //提交得有数据，则修改原权限配置
            $AccessDB -> delAccess(array('role_id'=>$roleid));  //先删除原用户组的权限配置

            $NodeDB = D('Node');
            $node = $NodeDB->getAllNode();
			$nn = [];
            foreach ($node as $_v) $nn[$_v['id']] = $_v;
            foreach($nodeid as $k => $node_id){
                $data[$k] = $AccessDB -> get_nodeinfo($node_id,$nn);
                $data[$k]['role_id'] = $roleid;
            }
            $AccessDB->addAll($data);
        } else {    //提交的数据为空，则删除权限配置
            $AccessDB -> delAccess(array('role_id'=>$roleid));
        }

        $this->success('设置成功！','',U('role/index'));
		
	}

	public function del()
	{
         $id = (int)I('get.id');
        if(!$id)$this->error('参数错误!');
        $RoleDB = D('Role');
        if($RoleDB->delRole('id='.$id)){
        	D("user_action")->saveAction(session("userid"),11,$id,"删除角色". $id);
            $this->success('删除成功！','',U('role/index'));
        }else{
            $this->error('删除失败!');
        }
	}

    public function role_edit()
    {
        $RoleDB = D("Role");
        if(IS_POST) {
            //根据表单提交的POST数据创建数据对象
            if($RoleDB->create()){
                if($RoleDB->save()){
                	D("user_action")->saveAction(session("userid"),11,I("post.id"),"修改角色". I("post.name"));
                    $this->success('编辑成功！','',U('role/index'));
                }else{
                    $this->error('编辑失败!');
                }
            }else{
                $this->error($RoleDB->getError());
            }
        }else{
            $id = (int)I('get.id');
            if(!$id)$this->error('参数错误!');
            $info = $RoleDB->getRole(array('id'=>$id));
            $this->assign('info',$info);
            $this->display();
        }
    }
}