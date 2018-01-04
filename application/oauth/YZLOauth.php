<?php
namespace app\oauth;
use org\oauth;
use think\Cache;
abstract class YZLOauth{
	protected $oauth_type = 0;
	protected $oauth_name = "";
	protected $param;
	protected $coupon_id;
	protected $customer_id;

	public function __construct($param){
		$this->param = $param;
		$params = $this->checkEmptyParams();
		if(!empty($params) && count($params) > 0){
			foreach ($params as  $value) {
				$this->param[$value] = isset($this->param[$value]) ? $this->param[$value] : '';
			}
		}
	}
/**
 * 通过oauth方法授权
 * @param unknown $method
 * @param unknown $params
 */
	public static function checkOauthMethod($method,$params){
		$clsname = "\\app\\oauth\\".$method."Oauth";
		if(class_exists($clsname)){
			return new $clsname($params);
		}
	}

// 注册或登录	
	public function loginOrRegsiter(){
		$res = array('errcode' => 0, 'message' => '成功');
		$res = $this->checkParamValidate();
		if($res['errcode'] < 0){
			return $res;
		}
		$res = $this->isLoginOrRegsiter();
		if($res['errcode'] == -201){
			return $res;
		}
		if($res['errcode'] != -201 && $res['errcode'] < 0){
		    // 判断是否是lg账号
            $LG = new \app\api\LGApi();
            $lg_user = $LG->getUsername($this->param['param']);
            if ($lg_user == true) {
                // 判断lg是否有这个账号
                $res = $LG->SearchLogin([
                    'userid' => $lg_user,
                    'userpass' => $this->param['passwd'],
                ]);
                $res = json_decode($res['return_content'],true);
                if ($res['code'] == 0) {
                    // 注册lg账号
                    $access_token = "";
                    while (1){
                        $access_token = token();
                        $id = M("customer")->where(['access_token' => $access_token])->getField("customer_id");
                        if (empty($id)){
                            break;
                        }
                    }
                    $data = array();
                    $data['uuid'] = build_uuid();
                    $data['date_add'] = time();
                    $data['date_upd'] = time();
                    $data['reg_ip'] = get_client_ip();
                    $data['last_ip'] = $data['reg_ip'];
                    $data['last_area'] = get_location($data['reg_ip']);
                    $data['access_token'] = $access_token;
                    $data['nickname'] = $this->param['param'];
                    $data['passwd'] = md5(sha1($this->param['passwd']));
                    $id = M('customer')->add($data);
                    if ($id) {
                        $this->customer_id = $id;
                        return $this->login(true);
                    }
                }
            }
			return ['errcode' => -200, 'message' => $res['message']];
		}
		return $res['content']['result'] ? $this->login(true) : $res;
	}
/**
 * 注册
 * @param unknown $params
 * @return multitype:number string |multitype:number string NULL
 */
	public function register($params){
		$res = array('errcode' => 0, 'message' => '成功');
		
		$check = $this->checkParamBeforeRegister();
		if ($check['errcode'] < 0){
			return $check;
		}
		if (isset($params['recommend'])){
			$params['is_recommend'] = 1;
		}
		if (isset($params['share_code'])) {
			$params['agent_id'] = M("customer")->where(['share_code' => $params['share_code']])->getField("customer_id");
			unset($params['share_code']);
		}
		
		$access_token = "";
		while (1){
			$access_token = token();
			$id = M("customer")->where(['access_token' => $access_token])->getField("customer_id");
			if (empty($id)){
				break;
			}
		}
		$data = array();
		$data['uuid'] = build_uuid();
		$data['date_add'] = time();
		$data['date_upd'] = time();
		$data['reg_ip'] = get_client_ip();
		$data['last_ip'] = $data['reg_ip'];
		$data['last_area'] = get_location($data['reg_ip']);
		$data['access_token'] = $access_token;

		//添加极光推送的token
		$jpush_token = I("jpush_token");
		
		if(!empty($jpush_token)){
			$data['jpush_token'] = $jpush_token;
		}
		
		//如果没有昵称，则按照手机号来，如果手机也没有，则随机生成一个
		if(!empty($params) && !isset($params['nickname'])){
			if(isset($params['phone'])){
                $data['nickname'] = $params['phone'];
//				$data['nickname'] = substr_replace($params['phone'], "****", 3,4);
			}else{
				$data['nickname'] = get_rand_name();
			}
		}
		
		if(!empty($params)){
			$data = array_merge($data,$params);
		}
		
		$params['openid'] = isset($params['qq_open_id'])?$params['qq_open_id']:(isset($params['wx_open_id'])?$params['wx_open_id']:
				(isset($params['wx_web_openid'])?$params['wx_web_openid']:null));
		
		if(isset($params['openid'])){
			$is_reg = Cache::get("shuaibo_reg_openid_" . $params['openid']);
			if($is_reg){
				return ['errcode' => 10 , 'message' => '请勿点击过快'];
			}
			Cache::set("shuaibo_reg_openid_".$params['openid'], 1, 10);
		}
		if(isset($params['phone'])){
			$is_reg = Cache::get("shuaibo_reg_phone_" . $params['phone']);
			if($is_reg){
				return ['errcode' => 10 , 'message' => '请勿点击过快'];
			}
			Cache::set("shuaibo_reg_phone_" . $params['phone'], 1, 10);
		}

        $data['passwd'] = md5(sha1($data['passwd']));
        unset($data['verify_code']);
		unset($data['oauth']);
		unset($data['confirm_passwd']);
		unset($data['recommend']);
		
		//存在phone且qq和微信未注册直接更新
		if(isset($check['is_update']) && $check['is_update'] == true){
			$condition['customer_id'] = $check['customer_id'];
			$count = true;
			if(isset($params['qq_open_id'])){
				$count = D("customer_model")->mySave($condition,['qq_open_id' => $data['qq_open_id']]);
			}
			
			if(isset($params['wx_web_openid'])){
				$count = D("customer_model")->mySave($condition,['wx_web_openid' => $data['wx_web_openid']]);
			}
			
			if (isset($params['wx_open_id'])){
				$count = D("customer_model")->mySave($condition,['wx_openid' => $data['wx_open_id']]);
			}
			
			if($count === false){
				return ['errcode' => -102,'message' => '更新失败'];
			}
			
			Cache::rm("shuaibo_reg_phone_".$params['openid']);
			$res['content'] = D("customer_model")->GetUserInfo($check['customer_id']);
			return $res;
		}
		
		if (isset($data['wx_open_id'])){
		    $data['wx_openid'] = $data['wx_open_id'];
		    unset($data['wx_open_id']);
		}
		$customer_id = D("customer_model")->add($data);
		if(empty($customer_id)){
			return ['errcode' => -102, 'message' => '添加失败'];
		}
		
		Cache::set($access_token,$customer_id , 365 * 24 * 60 * 60);
		
		//TODO 判断是否有优惠券
		/*if(!empty($this->coupon_id)){
			D("coupon")->trans_coupon($this->coupon_id, $this->customer_id);
		}*/
		/*$register_coupon = \app\library\SettingHelper::get("bear_register_coupon",['is_open'=> 0,'coupon_name' => '注册大礼包', 'time' => 7 , 'coupon_money' => 1, 'coupon_limit' => 0 ,'interval' => 60 * 60 * 24 , 'expire'=> 60 * 60 * 24]);
		if(!empty($register_coupon['is_open'])){
			$now = time();
			for($i = 0; $i < $register_coupon['time'] ; $i++){
				$data = [
						'customer_id' => $customer_id,
						'coupon_id' => 0,
						'coupon_name' => isset($register_coupon['coupon_name']) ? $register_coupon['coupon_name'] : "注册大礼包",
						'coupon_limit' => $register_coupon['coupon_limit'],
						'coupon_money' => $register_coupon['coupon_money'],
						'state' => 0,
						'date_add' => $now,
						'date_end' => ($i + 1) * $register_coupon['expire'] + $now,
						'date_start' => $i * $register_coupon['interval'] + $now
				];
				M("customer_coupon")->add($data);
			}
		}*/
		
		//D("user_statistics")->record($customer_id, $this->oauth_type, 0);
		/*$register_gift=\app\library\SettingHelper::get("register_gift",0);
		if(!empty($register_gift)){
			M("customer")->where(['customer_id'=>$customer_id])->setInc("carousel_num",$register_gift);
		}*/
		
		//注册赠送红包
		/*$register_money = \app\library\SettingHelper::get("bear_register_money", ['is_open' => 0, 'money' => 0]);
		
		if($register_money['is_open'] == 1 && $register_money['money'] > 0){
			D("finance_op")->record($customer_id, 2, 0 , $register_money['money'] );
		}*/
		//生成二维码
		$res['content'] = D("customer_model")->GetUserInfo($customer_id);
		if(isset($params['openid'])){
			Cache::rm("shuaibo_reg_openid_".$params['openid']);
		}
		if(isset($params['phone'])){
			Cache::rm("shuaibo_reg_phone_" . $params['phone'], 1, 1000);
		}
		
		return $res;
	}
/**
 * 登录
 * @param string $need_address
 * @param string $validate
 */
	public function login( $validate = true){
		$res = array('errcode' => 0, 'message' => '成功');
		
		if($validate){
			$res = $this->checkParamBeforeLogin();
			if($res['errcode'] < 0){
				return $res;
			}
		}
		if(empty($this->customer_id)){ 
			$res['errcode'] = -101;
			$res['message'] = "没有用户信息";
			return $res;
		}
		
		$data = ['last_ip' => get_client_ip(), 'date_upd' => time(), 'last_check_date' => time()];
		
		$jpush_token = I("jpush_token");

		if(!empty($jpush_token)){
			$data['jpush_token'] = $jpush_token;
		}
		D("customer_model")->where(['customer_id' => $this->customer_id])->save($data);
		
		//D("user_statistics")->record($this->customer_id, $this->oauth_type, 1);

        $res = D("user_model")->getUserInfo($this->customer_id);
        if ($res['errcode'] != 0) {
            return $res;
        }
		$customer = $res['content'];
		
		if(empty($customer)){
			return ['errcode' => -102 , 'message' => '找不到相关人员'];
		}
		if($customer['active'] == 0){
			$seller_info = \app\library\SettingHelper::get("shuaibo_seller_info",['address' => '杭州市' ,'qq' => '123456789']);
			return ['errcode' => -102, 'message' => '该用户已无法登录，如有疑问，请联系客服：' . $seller_info['qq']] ;
		}
		$res['content'] = $customer;
		
		return $res;
	}

	abstract public function isLoginOrRegsiter();

	abstract protected function getRegParams(); 

	abstract protected function checkEmptyParams();

	abstract protected function checkParamValidate();

	abstract protected function checkParamBeforeRegister();

	abstract protected function checkParamBeforeLogin();
}

