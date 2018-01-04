<?php
namespace app\admin\controller;
use app\admin\Admin;
use \org\Rbac;
use \org\net\IpLocation;
class Login extends Admin
{
	public function index()
	{
		if (session(C('USER_AUTH_KEY'))){
			$this->redirect(U("panel/index"));
		}
         $username = cookie('admin_username') ? cookie('admin_username') : '';
         $this->assign('username',$username);
		$this->display();
	}

    // 登录检测
    public function checkLogin() {
         $username = I('post.username');
         $password = I('post.password');
         $verify   = I('post.verify');
         //生成认证条件
         $map            =   array();
         // 支持使用绑定帐号登录
         $map['username'] = $username;
         $map['status']        = 1;

         if(!sp_check_verify_code()) {
             $this->error(L('CAPTCHA_NOT_RIGHT'));
         }

         $authInfo = RBAC::authenticate($map);
         //使用用户名、密码和状态的方式进行认证
         if(false == $authInfo) {
           	$this->error(L('USERNAME_NOT_EXIST'));
         }else {
         	if($authInfo['password'] != md5($password) ) {
         		$this->error(L('PASSWORD_NOT_RIGHT'));
         	}
			session(C('USER_AUTH_KEY'), $authInfo['id']);
         	session('userid',$authInfo['id']);  //用户ID
			session('username',$authInfo['username']);   //用户名
         	session('roleid',$authInfo['role']);    //角色ID
            if($authInfo['username']==C('SPECIAL_USER') || $authInfo['role'] == 1) {
         		session(C('ADMIN_AUTH_KEY'), true);
         	}
            
            //保存登录信息
            $User	=	M("user");
            $ip		=	get_client_ip();
            $data = array();
            if($ip){    //如果获取到客户端IP，则获取其物理位置
                $Ip = new IpLocation(); // 实例化类
                $location = $Ip->getlocation($ip); // 获取某个IP地址所在的位置
                $data['last_location'] = '';
                if($location['country'] && $location['country']!='CZ88.NET') $data['last_location'].=$location['country'];
                if($location['area'] && $location['area']!='CZ88.NET') $data['last_location'].=' '.$location['area'];
            }
            $data['id']	=	$authInfo['id'];
            $data['last_login_time']	=	time();
            $data['last_login_ip']	=	get_client_ip();
            $User->save($data);
			
            // 缓存访问权限
            RBAC::saveAccessList();
            setcookie("admin_username",$username,time()+30*24*3600,"/");
            
            $this->success(L('LOGIN_SUCCESS'));
        }
    }
	
    // 用户登出
    public function logout() {
        if(session('?'.C('USER_AUTH_KEY'))) {
            session(C('USER_AUTH_KEY'),null);
            session(null);
            $this->redirect(U('login/index'));
        }else {
            $this->error(L('LOGOUT'));
        }
    }
}