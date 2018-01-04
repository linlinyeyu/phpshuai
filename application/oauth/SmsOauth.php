<?php
namespace app\oauth;
class SmsOauth extends YZLOauth{
	protected $oauth_name = "短信";
	protected $oauth_type = 1;
// 登录或注册
	public function isLoginOrRegsiter(){
		$res = array('errcode' => 0 , 'message' => '成功');
		$condition = array('phone' => $this->param['phone']);
		$customer = M("customer")->where($condition)->field("customer_id, passwd")->find();
		if(!empty($customer)){
			$this->customer_id = $customer['customer_id'];
			$res['content'] = true;
		}else{
			$res['errcode'] = -101;
			$res['message'] = "该手机暂未注册";
			$res['content'] = false;
		}
		return $res;
	}
// 获取注册方式
	protected function getRegParams(){
		$coupon = M("coupon")->where(['is_publish' => 1, 'type' => \app\model\Coupon::PHONE_REGISTER])->order(" coupon_money desc ")->find();
		if(!empty($coupon)){
			$this->coupon_id = $coupon['coupon_id'];
		}
		return array('phone' => $this->param['phone']);
	}

	protected function checkParamValidate(){
		
		$phone = isset($this->param['phone']) ? $this->param['phone'] : '';

		$verify_code = isset($this->param['verify_code']) ? $this->param['verify_code'] : '';

		return D("verify_code")->verify($phone, $verify_code, 2);
	}
// 注册
	protected function checkParamBeforeRegister(){
		return array('errcode' => 0, 'message' => '成功');
	}
// 登录
	protected function checkParamBeforeLogin(){
		return array('errcode' => 0, 'message' => '成功');
	}

	protected function checkEmptyParams(){
		return ['phone', 'verify_code'];
	}
}