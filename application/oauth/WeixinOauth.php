<?php
namespace app\oauth;
use think\Cache;
use org\oauth\driver\Weixin;
class WeixinOauth extends YZLOauth{
	protected $oauth_name = "微信APP";
	protected $oauth_type = 2;
// 登录或注册
	public function isLoginOrRegsiter(){
		$res = array('errcode' => 0 , 'message' => '成功' , 'oauth' => 'Weixin');
		$code = $this->param['code'];
		if(empty($code)){
			return ['errcode' => -101, 'message' => '请传入微信凭证'];
		}
		$config = \app\library\SettingHelper::get_pay_params(3);
		$weixin_config = array(
				'app_key' => $config['appid'],
				'app_secret' => $config['app_secret'],
				'authorize' => 'authorization_code'
			);
		try{
			$weixin = new Weixin($weixin_config);
			$token = $weixin->getAccessToken($code);
			
		}catch(\Exception $e){
			return ['errcode' => -102, 'message' => '获取微信信息失败'];
		}
		
		if(is_string($token)){
			return ['errcode' => -103 , 'message' => '请求失败'];
		}
		$this->weixin = $weixin;
		$this->token = $token;
		
		$this->openid = $token['openid'];
		
		$unionid = isset($token['unionid']) ? $token['unionid'] : "";
		
		$condition = array('wx_openid' => $token['openid']);
		
		if(!empty($unionid)){
			$condition['wx_unionid'] = $unionid;
			$condition['_logic'] = 'OR';
		}
		$customer = M("customer")->where($condition)->field("customer_id, wx_openid, wx_unionid, phone")->find();
		$res = array_merge($res,$this->getRegParams());
		if (isset($this->param['agent_id'])) {
			$res['agent_id'] = $this->param['agent_id'];
		}
		
		if(!empty($customer)){
			if(empty($customer['phone'])){
				$res['content'] = ['result' => false];
				return $res;
			}
			
			if(empty($customer['wx_openid']) || empty($customer['wx_unionid'])){
				$data = ['wx_openid' => $token['openid']];
				if (isset($token['unionid'])){
					$data['wx_unionid'] = $token['unionid'];
				}
				M("customer")->where(['customer_id' =>$customer['customer_id']])->save($data);
			}
			
			$this->customer_id = $customer['customer_id'];

			$res['content']['result'] = true;
		}else{
			$res['content'] = ['result' => false];
		}
		return $res;
	}
// 获取注册方式
	protected function getRegParams(){
		
		$info = array();
		$info['wx_open_id'] = $this->token["openid"];
		if(isset($this->token["unionid"])){
			$info['wx_unionid'] = $this->token["unionid"];
		}
		try{
			$infos = $this->weixin->getOauthInfo();
			$info['nickname'] = $infos['nickname'];
			$info['sex'] = $infos['sex'] == 1 ? "男" :  ($infos['sex'] == 2 ? "女" : "");
			$info['province'] = $infos['province'];
			$info['city'] = $infos['city'];
			if(isset($infos['headimgurl'])){
				$upload = \app\library\UploadHelper::getInstance();
				$result = $upload->set_thumb_size(200, 200)->download_image($infos['headimgurl']);
				if($result['errcode'] >= 0){
					$info['avater'] = $result['content']['name'];
				}
			}
		}catch(\Exception $e){
		}
		return $info;
	}
// 验证注册方式
	protected function checkParamValidate(){
		if(empty($this->param['code'])){
			return ['errcode' => -101, 'message' => '请传入微信凭证'];
		}
		return ['errcode' => 0, 'message' => '验证成功'];
	}
// 注册
	protected function checkParamBeforeRegister(){
		$res = array('errcode' => 0, 'message' => '成功');
		$phone = isset($this->param['phone']) ? $this->param['phone'] : '';
		$passwd = isset($this->param['passwd']) ? $this->param['passwd'] : '';
		$verify_code = $this->param['verify_code'];
		$confirm_passwd = $this->param['confirm_passwd'];
		
		if(!$phone){
			$res['message'] = "请输入手机号";
			$res['errcode'] = -401;
			return $res;
		}
		if(!preg_match("/^1\d{10}$/", $phone)){
			$res['message'] = "手机号格式错误";
			$res['errcode'] = -402;
			return $res;
		}
		
		if(!$passwd){
			$res['message'] = "请输入密码";
			$res['errcode'] = -405;
			return $res;
		}
		
		if ($passwd != $confirm_passwd){
			return ['errcode' => -101 ,'message' => '密码不一致'];
		}
		
		if(empty($verify_code)){
			$res['message'] = "请传入验证码";
			$res['errcode'] = -407;
			return $res;
		}
		
		$condition = array('phone' => $phone,'_string' => 'wx_openid is not null');
		$count = M("customer")->where($condition)->count();
		if($count > 0){
			$res['message'] = "手机号已被使用";
			$res['errcode'] = -403;
			return $res;
		}
		
		$condition= array('phone' => $phone,'_string' => 'wx_openid is null');
		$customer = M("customer")->field("customer_id,passwd")->where($condition)->find();
		if(isset($customer['customer_id'])){
			$res = D("verify_code_model")->verify($phone, $verify_code,3);
			if($res['errcode'] < 0){
				return $res;
			}
			if($customer['passwd'] != $passwd){
				return array('errcode' => -103, 'message' => "手机号已注册，密码错误");
			}
			return array('errcode' => 0, 'message' => '成功', 'is_update' => true, 'customer_id' => $customer['customer_id']);
		}else {
			$res = D("verify_code_model")->verify($phone, $verify_code,3);
			if($res['errcode'] < 0){
				return $res;
			}
		}
		return array('errcode' => 0, 'message' => '成功');
	}
// 登录
	protected function checkParamBeforeLogin(){
		return array('errcode' => 0, 'message' => '成功');
	}

	protected function checkEmptyParams(){
		return ['code','phone','verify_code','passwd','confirm_passwd'];
	}

}