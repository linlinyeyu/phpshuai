<?php
namespace app\oauth;
class PhoneOauth extends YZLOauth{
	protected $oauth_name = "密码";
	protected $oauth_type = 0;
/**
 * 登陆还是注册(non-PHPdoc)
 * @see \app\oauth\YZLOauth::isLoginOrRegsiter()
 */
	public function isLoginOrRegsiter(){
		$res = ['errcode' => 0, 'message' => '成功'];
		$filed = verifyParam($this->param['param']);
		$password = $this->param['passwd'];
		$customer = D("customer_model")->checkCustomer($this->param['param'],$password,$filed);
		if(!empty($customer)){
			$this->customer_id = $customer['customer_id'];
			$res['content']['result'] = true;
		}else{
            // 判断是否是lg账号
            $password = md5(sha1($password));
			$pass = M("customer")->where([$filed => $this->param['param']])->getField("passwd");
			if (!empty($pass)){
                if ($pass != $password) {
                    return ['errcode' => -201,'message' => '密码错误'];
                }
            }
			$res['errcode'] = -101;
			$res['message'] = '用户名或密码错误';
		}
		
		return $res;
	}
/**
 * 获取注册方式(non-PHPdoc)
 * @see \app\oauth\YZLOauth::getRegParams()
 */
	protected function getRegParams(){
		return ['errcode' => 0,'message' => '成功'];
	}
/**
 * 注册方式是否可行(non-PHPdoc)
 * @see \app\oauth\YZLOauth::checkParamValidate()
 */
	protected function checkParamValidate(){
		if(empty($this->param['param'])){
			return ['errcode' => -101, 'message' => '请填写用户名或邮箱或手机号'];
		}
		if(empty($this->param['passwd'])){
			return ['errcode' => -101, 'message' => '请填写密码'];
		}
		
		return ['errcode' => 0, 'message' => '成功'];
	}
/**
 * 注册并查看是否已经注册过(non-PHPdoc)
 * @see \app\oauth\YZLOauth::checkParamBeforeRegister()
 */
	protected function checkParamBeforeRegister(){
		$passwd = $this->param['passwd'];
		$confirm_passwd = $this->param['confirm_passwd'];
		$res = D("verify_code_model")->verify($this->param['phone'],$this->param['verify_code'],0);
		if($res['errcode'] < 0){
			return $res;
		}
		
		if(empty($passwd)){
			return ['errcode' => -101, 'message' => '请传入密码'];
		}
		if($passwd != $confirm_passwd){
			return ['errcode' => -101, 'message' => '密码不一致'];
		}
		
		$customer_id = D("customer_model")->myFind(['phone' => $this->param['phone']],'customer_id');
		if(!empty($customer_id)){
			return ['errcode' => -101, 'message' => '该用户已注册'];
		}
		return ['errcode' => 0, 'message' => '成功'];
	}
/**
 * 登录(non-PHPdoc)
 * @see \app\oauth\YZLOauth::checkParamBeforeLogin()
 */
	public function checkParamBeforeLogin(){
		$res = array('errcode' => 0, 'message' => '成功');
		return $res;
	}
/**
 * 手机号注册(non-PHPdoc)
 * @see \app\oauth\YZLOauth::checkEmptyParams()
 */
	public function checkEmptyParams(){
		return ['param','passwd','verify_code','phone','confirm_passwd'];
	}
}