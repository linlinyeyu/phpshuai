<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
use \org\Rbac;
use \org\net\IpLocation;
class Login extends Admin
{
	public function index()
	{
		if (session("id")){
			$this->redirect(U("panel/index"));
		}
         $username = cookie('shop_admin_username') ? cookie('shop_admin_username') : '';
         $this->assign('username',$username);
		$this->display();
	}

    // 登录检测
    public function checkLogin() {
         $username = I('post.username');
         $password = I('post.password');
         $verify   = I('post.verify');
         //生成认证条件
         $map = array();
         // 支持使用绑定帐号登录
         $field = verifyParam($username);
         if ($field == 'nickname'){
         	$field = 'username';
         }
         $map[$field] = $username;
         $map['status']        = 1;

         if(!sp_check_verify_code()) {
             $this->error(L('CAPTCHA_NOT_RIGHT'));
         }

         $authInfo = M("seller_user")->where($map)->find();

         //使用用户名、密码和状态的方式进行认证
         if(false == $authInfo) {
           	$this->error(L('USERNAME_NOT_EXIST'));
         }else {
         	if($authInfo['password'] != md5(md5($password).$authInfo['ec_salt']) ) {
         		$this->error(L('PASSWORD_NOT_RIGHT'));
         	}
			session("id",$authInfo['id']);
         	session('sellerid',$authInfo['seller_id']);  //商户ID
			session('shop_username',$authInfo['username']);   //商户名
            
            //保存登录信息
            $User	=	M("seller_user");
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
			
            setcookie("shop_admin_username",$username,time()+30*24*3600,"/");
            
            $this->success(L('LOGIN_SUCCESS'));
        }
    }
	
    // 用户登出
    public function logout() {
        if(session("id")) {
            session(null);
            $this->redirect(U('login/index'));
        }else {
            $this->error(L('LOGOUT'));
        }
    }

}