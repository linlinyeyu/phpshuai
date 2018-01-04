<?php
namespace app\oauth;
use think\Cache;
use org\oauth\driver\Weixin;
class WeixinGzOauth extends YZLOauth{
	protected $oauth_name = "微信公众号";
	protected $oauth_type = 3;
	protected $is_sub;
	
	protected $infos;
	
	public function setSub($is_sub = true){
		$this->is_sub = $is_sub;
	}
	
	public function isLoginOrRegsiter(){
		$res = array('errcode' => 0 , 'message' => '成功');
		$code = $this->param['code'];
		$openid = $this->param['openid'];
		$unionid = $this->param['unionid'];
		$access_token = $this->param['access_token'];
		if(empty($code) && empty($openid)){
			return ['errcode' => -101, 'message' => '请传入微信凭证'];
		}
		$config = \app\library\SettingHelper::get_pay_params(4);
		$weixin_config = array(
					'app_key' => $config['appid'],
					'app_secret' => $config['app_secret'],
					'authorize' => 'authorization_code'
				);
		$token = [];
		$weixin = new Weixin($weixin_config);
		if (!empty($code)) {
			$token = $weixin->getAccessToken($code);
			if(!isset($token['access_token'])){
				return ['errcode' => -101, 'message' => '请求失败'];
			}
			
			$this->token = $token;
			$unionid = isset($token['unionid']) ? $token['unionid'] : '';
			$openid = $token['openid'];
		}else{
			$token['openid'] = $openid;
			$token['unionid'] = $unionid;
			$token['access_token'] = $access_token;
			$weixin->setToken($token);
		}
		
		$this->weixin = $weixin;
		$this->token = $token;
		if($this->is_sub){
			$this->infos = $this->weixin->getOauthInfo($this->is_sub);
			if(isset($this->infos['unionid'])){
				$unionid = $this->infos['unionid'];
			}
		}
		
		$condition = array('wx_gz_openid' => $openid);
		if(!empty($unionid)){
			$condition['wx_unionid'] = $unionid;
			$condition['_logic'] = 'OR';
		}
		$this->unionid = $unionid;
		$this->openid = $openid;
		setcookie("openid", $openid, time() + 60 * 60 * 24 * 365, "/");
		$customer = M("customer")->where($condition)->field("customer_id,wx_gz_openid,wx_unionid,is_subscribe")->find();
		
		if(!empty($customer)){
			
			$data = [];
			if($customer['is_subscribe'] == 0 && $this->is_sub){
				$data['is_subscribe'] = 1;
			}
			
			if(empty($customer['wx_gz_openid']) || empty($customer['wx_unionid'])){
				$data['wx_gz_openid'] = $openid ;
				if (!empty($unionid)){
					$data['wx_unionid'] = $unionid;
				}
			}
			if(!empty($data)){
				M("customer")->where(['customer_id' => $customer['customer_id']])->save($data);
			}
			$this->customer_id = $customer['customer_id'];
			$res['content'] = true;
		}else{
			$res['content'] = false;
		}
		return $res;
	}

	protected function getRegParams(){
		$info = array();
		$info['wx_gz_openid'] = $this->openid;
		if(isset($this->unionid)){
			$info['wx_unionid'] = $this->unionid;
		}
		if(isset($this->weixin) && isset($this->token['access_token'])){
			$infos = $this->infos ? $this->infos : $this->weixin->getOauthInfo($this->is_sub);
			if(isset($infos['nickname'])){
				$info['nickname'] = $infos['nickname'];
				$info['sex'] = $infos['sex'] == 1 ? "男" :  ($infos['sex'] == 2 ? "女" : "");
				$info['province'] = $infos['province'];
				$info['city'] = $infos['city'];
			}
			
			if(isset($infos['subscribe']) && $infos['subscribe'] == 1){
				$info['is_subscribe'] = 1;
			}
			if(isset($infos['unionid'])){
				$info['wx_unionid'] = $infos['unionid'];
			}
			
			if(isset($infos['headimgurl'])){
				$info['avater'] = $infos['headimgurl'];
				/*$upload = \app\library\UploadHelper::getInstance();
				$result = $upload->download_image($infos['headimgurl']);
				if($result['errcode'] >= 0){
					$info['avater'] = $result['content']['name'];
				}*/
			}
		}
		return $info;
	}

	protected function checkParamValidate(){
		if(empty($this->param['code']) && empty($this->param['openid'])){
			return ['errcode' => -101, 'message' => '请传入微信凭证'];
		}
		return ['errcode' => 0, 'message' => '验证成功'];
	}

	protected function checkParamBeforeRegister(){
		return array('errcode' => 0, 'message' => '成功');
	}

	protected function checkParamBeforeLogin(){
		return array('errcode' => 0, 'message' => '成功');
	}

	protected function checkEmptyParams(){
		return ['code','openid','unionid','access_token'];
	}

}