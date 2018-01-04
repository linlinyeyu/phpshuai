<?php
namespace app\oauth;

class WebOauth extends YZLOauth{
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
			$res['errcode'] = -102;
			$res['message'] = '用户名或密码错误';
		}
		
		return $res;
	}
	
	public function checkParamValidate(){
		if(empty($this->param['param'])){
			return ['errcode' => -101, 'message' => '请填写用户名'];
		}
		if(empty($this->param['passwd'])){
			return ['errcode' => -101, 'message' => '请填写密码'];
		}
		if(empty($this->param['verify_code'])){
			return ['errcode' => -101, 'message' => '请填写验证码'];
		}
		if(empty($this->param['token'])){
			return ['errcode' => -101, 'message' => '请传入token'];
		}
		
		$verify = new \org\Verify();
		if(!$verify->checkFromCache($this->param['verify_code'],$this->param['token'])){
			return ['errcode' => -101, 'message' => '验证码错误'];
		}
		
		S($this->param['token'],null);
		
		return ['errcode' => 0, 'message' => '成功'];
	}
	
	public function checkParamBeforeLogin(){
		return ['errcode' => 0, 'message' => '成功'];
	}
	
	public function checkParamBeforeRegister(){
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
	
	public function getRegParams(){
		return ['errcode' => 0, 'message' => '成功'];
	}
	
	public function checkEmptyParams(){
		return ['param','passwd','verify_code','token','phone','confirm_passwd'];
	}
}