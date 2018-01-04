<?php
namespace app\web\controller;

use think\Cache;
class CustomerAction{
    private $appid = "wx06db8000a8ef09e3";
	//用户信息
	public function getUserInfo(){
		$customer_id = get_customer_id();
		$customer = D("customer_model")->getUserInfo($customer_id);
		
		return ['errcode' => 0, 'message' => '获取成功','content' => $customer];
	}
	
	//微信登录跳转
	public function getWeixinUrl(){
	    $curr = "http://app.shuaibomall.net/web/customerAction/webWeixinLogin";
	    $res = httpGetData('https://open.weixin.qq.com/connect/qrconnect?appid='.$this->appid."&redirect_uri=".urlencode($curr)."&response_type=code&scope=snsapi_login&state=STATE#wechat_redirect");
	    return ['errcode' => 0,'message' => '请求成功','content' => $res];
	}
	//网页微信登录
	public function webweixinlogin(){
	    $oauth = \app\oauth\YZLOauth::checkOauthMethod("WeixinWeb", I(""));
	    return $oauth->loginOrRegsiter();
	}
	//网页qq登录
	public function qqWebLogin(){
	    $oauth = \app\oauth\YZLOauth::checkOauthMethod("QqWeb", I(""));
	    return $oauth->loginOrRegsiter();
	}
	
	//登录
	public function login(){
		$oauthMethod = I("oauth");
		
		$oauth = \app\oauth\YZLOauth::checkOauthMethod($oauthMethod, I(""));
		if(empty($oauth)){
			$res['errcode'] = -101;
			$res['message'] = "没有对应的方法";
			return $res;
		}
		return $oauth->loginOrRegsiter();
	}
	
	//图片验证码token
	public function createToken(){
		$token = token();
		return ['errcode' => 0, 'message' => '生成成功', 'content' => ['token' => $token]];
	}
	
	//生成验证码图片
	public function createVerify(){
		$token = I("token");
		if(empty($token)){
			return ['errcode' => -101, 'message' => '请传入token'];
		}
		$verify = new \org\Verify();
		$code = $verify->entry();
		S($token,$code);
		
		die();
	}
	
	//发送验证码
	public function sendCode(){
	    /*
		$param = I("param");
		$type = I("type");
		if(empty($param)){
			return ['errcode' => -101, 'message' => '请输入邮箱或手机号'];
		}
		//防止频繁发送
		$ip = get_client_ip();
		$times = (int)Cache::get("bear_send_code_". $ip . "_" . $param);
		if($times > 3){
			return ['errcode' => -101, 'message' => '请慢点发送短信'];
		}
		
		Cache::set("bear_send_code_". $ip . "_" . $param, ++$times, 60);
		
		$field = verifyParam($param);
		if ($field != 'phone' && $field != 'email'){
			return ['errcode' => -101, 'message' => '请填入正确格式手机号或邮箱'];
		}
		
		$customer = M("customer")->where([$field => $param])->find();
		if(empty($customer)){
			$type = 0;
		}
		
		if ($field == 'phone'){
			$customer = D("customer_model")->where(['phone' => $param])->find();
			if(empty($customer)){
				$type = 0;
			}
		}
		$code = D("verify_code_model")->send($param,$type,$field);
			
		return ['errcode' => 0, 'message' => '发送成功'];
	    */

        $param = I("param");
        $type = I("type");
        $time = I('time');
		$sign = I('sign');
        if(empty($param)){
            return ['errcode' => -101, 'message' => '请输入邮箱或手机号'];
        }
        if (empty($time)) {
            return ['errcode' => -101, 'message' => '请传入时间'];
        }
        if (empty($sign)) {
            return ['errcode' => -101, 'message' => '请传入sign'];
        }
        $params = [
            'param' => $param,
            'type' => $type,
            'time' => $time,
            'content' => 'ShuaiBo2017'
        ];
        $result = getSign($params);
        if ($sign != $result) {
            return ['errcode' => -101, 'message' => 'sign验证错误'];
        }
        //防止频繁发送
        $ip = get_client_ip();
        $times = (int)Cache::get("shuaibo_send_code_". $ip . "_" . $param);
        if($times > 3){
            return ['errcode' => -101, 'message' => '请慢点发送短信'];
        }

        Cache::set("shuaibo_send_code_". $ip . "_" . $param, ++$times, 60);

        $field = verifyParam($param);
        if ($field != 'phone' && $field != 'email'){
            return ['errcode' => -101, 'message' => '请填入正确格式手机号或邮箱'];
        }

        $customer = M("customer")->where([$field => $param])->find();
//        if(empty($customer)){
//			$type = 0;
//		}

        if ($type == 0){
            $customer_id = M("customer")->where([$field => $param])->getField("customer_id");
            if (!empty($customer_id)){
                return ['errcode' => -101,'message' => '该账号已注册'];
            }
        }
//		if ($field == 'phone'){
//			$customer = D("customer_model")->where(['phone' => $param])->find();
//			if(empty($customer)){
//				$type = 0;
//			}
//		}

        $code = D("verify_code_model")->send($param,$type,$field);

        vendor("Weixin.WxLog");

        $log_ = new \Log_();
        $dir = "data/code/";
        $log_name= $dir. "code_log.log";//log文件路径
        mkDirs($_SERVER['DOCUMENT_ROOT'] . "/" . $dir);
        $log_->log_result($log_name,json_encode($_SERVER)."\n");

        return ['errcode' => 0, 'message' => '发送成功'];
	}
	
	//重新设置密码
	public function resetPasswd(){
		$param = I("param");
        $passwd = I("passwd");
        $confirm_passwd = I("confirm_passwd");
		$verify = I("verify");
		$field = verifyParam($param);
		
		if ($field != 'phone' && $field != 'email'){
			return ['errcode' => -101, 'message' => '请填入正确格式手机号或邮箱'];
		}
		$res = D("verify_code_model")->verify($param,$verify,1,$field);
		if ($res['errcode'] < 0){
			return $res;
		}
		
		if($passwd != $confirm_passwd){
			return ['errcode' => -101, 'message' => '密码不一致'];
		}
		
		$condition = array(
				$field => $param
		);
		
		$customer = D("customer_model")->myFind($condition);
		if(empty($customer)){
			return ['errcode' => 0, 'message' => '没有该用户'];
		}
		if ($customer['passwd'] == md5(sha1($passwd))){
			return ['errcode' => -101, 'message' => '新旧密码不能一样'];
		}
		
		$data = array(
				'passwd' => md5(sha1($passwd))
		);
		$res = D("customer_model")->mySave($condition,$data);
		if($res === false){
			return ['errcode' => -201, 'message' => '重置密码失败'];
		}
        // 同步lg修改密码
        $LG = new \app\api\LGApi();
        $lg_user = $LG->getUsername($customer['nickname']);
        if ($lg_user == true) {
            $res = $LG->EditUpdatePass([
                'userid' => $lg_user,
                'userpass' => $passwd,
                'passtype' => 1
            ]);
            $res = json_decode($res['return_content'],true);
            if ($res['code'] != 0) {
                return ['errcode' => -100, 'message' => $res['message']];
            }
        }
		return ['errcode' => 0, 'message' => '重置密码成功'];
	}
	
	//注册
	public function register(){
		$oauthMethod = I("oauth");
		$oauth = \app\oauth\YZLOauth::checkOauthMethod($oauthMethod, I(""));
		if(empty($oauth)){
			$res['errcode'] = -101;
			$res['message'] = "没有对应的方法";
			return $res;
		}
		$res = $oauth->register(I(""));
		if($res['errcode'] < 0){
			return $res;
		}
		
		$email = I("email");
		
		if(!empty($email)){
			if(!preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $email)){
				return ['errcode' => -101 ,'message' => '邮箱格式错误'];
			}
		}
		
		return $res;
	}
	
	//完善信息
	public function perInfomation(){
		$oauthMethod = I("oauth");
		$oauth = \app\oauth\YZLOauth::checkOauthMethod($oauthMethod, I(""));
		if(empty($oauth)){
			$res['errcode'] = -101;
			$res['message'] = "没有对应的方法";
			return $res;
		}
		return $oauth->register(I(""));
	}
	
	//同步LG系统
	private function  synLgSystem($recommend){
        if(empty($recommend['tuijian'])){
			return ['errcode' => -101, 'message' => '请传入推荐编号'];
		}
		if (empty($recommend['userid'])){
			return ['errcode' => -101, 'message' => '请传入姓名'];
		}
//		if (empty($recommend['anzhi'])){
//			return ['errcode' => -101, 'message' => '请传入身份证号'];
//		}
		$LG = new \app\api\LGApi();
		$check = $LG->CheckTuiJianAndAnZhi($recommend);

        if ($check['errcode'] < 0){
			return $check;
		}
		$check = json_decode($check['return_content'],true);
		if ($check['code'] == 1){
			return ['errcode' => -101 ,'message' => $check['message']];
		}
		
		$res = $LG->AddMemberInfo($recommend);
		if ($res['errcode'] < 0){
			return $res;
		}
		$res = json_decode($res['return_content'],true);
		if($res['code'] == 1){
			return ['errcode' => -101, 'message' => $check['message']];
		}
		return ['errcode' => 0, 'message' => '同步系统成功'];
	}
	
	//验证绑定邮箱
	public function bindSendEmail(){
		$email = I("email");
		$customer_id = get_customer_id();
		$access_token = I("access_token");
		if(empty($customer_id)){
			return ['errcode' => 99, 'message' => '请重新登录'];
		}
		D("verify_code_model")->send($email,5,'email',$access_token);
		return ['errcode' => 0, 'message' => '发送成功'];
	}
	
	//绑定邮箱
	public function bindEmail(){
		$customer_id = get_customer_id();
		$verify = I("verify");
		$email = I("email");
		if(empty($customer_id)){
			return ['errcode' => 99, 'message' => '请重新登录'];
		}
		$res = D("verify_code_model")->verify($email,$verify,5,'email');
		if($res['errcode'] < 0){
			return $res;
		}
		$condition = ['customer_id' => $customer_id];
		$data = ['email' => $email];
		$res = D("customer_model")->mySave($condition,$data);
		if($res === false){
			return ['errcode' => -201, 'message' => '绑定邮箱失败'];
		}
		return ['errcode' => 0, 'message' => '绑定成功','content' => $email];
	}

	/**
    *分享
    */
    public function share(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
        	return ['errcode' => 99,'message' => '请重新登录'];
        }
        return ['errcode' => 0,'message' => '请求成功','content' => $customer_id];
    }

    /**
    *推荐码
    **/
    public function share_code(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99,'message' => '请重新登录'];
        }
        $share_code = M("customer")->where(['customer_id' => $customer_id])->getField("share_code");
        if ($share_code == null) {
            while (true) {
                $share_code = make_promotion();
                $is_exist = M("customer")->where(['share_code' => $share_code])->count();
                if ($is_exist) {
                    continue;
                }else{
                    break;
                }
            }
            M("customer")->where(['customer_id' => $customer_id])->save(['share_code' => $share_code]);
            return ['errcode' => 0,'message' => '请求成功','content' => $share_code];
        }

        return ['errcode' => 0,'message' => '请求成功','content' => $share_code];
    }

    /**
     *设置转账密码
     */
    public function setTransferPasswd(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => -101,'message' => '请重新登录'];
        }

        $password = I("password");
        $verify_code = I("verify_code");
        $phone = I("phone");

        $correct_phone = M("customer")->where(['customer_id' => $customer_id])->getField('phone');
        if ($correct_phone != $phone){
            return ['errcode' => -201,'message' => '不是当前注册手机号'];
        }
        $res = D("verify_code_model")->verify($phone,$verify_code,11);
        if ($res['errcode'] < 0){
            return $res;
        }
        M("customer")->where(['customer_id' => $customer_id])->save(['transfer_passwd' => $password]);
        return ['errcode' => 0,'message' => '请求成功'];
    }

    /**
     *设置支付密码
     */
    public function setPayPasswd(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => -101,'message' => '请重新登录'];
        }
        $password = I("password");
        $verify_code = I("verify_code");
        $phone = I("phone");
        $correct_phone = M("customer")->where(['customer_id' => $customer_id])->getField('phone');
        if ($correct_phone != $phone){
            return ['errcode' => -201,'message' => '不是当前注册手机号'];
        }
        $res = D("verify_code_model")->verify($phone,$verify_code,10);
        if ($res['errcode'] < 0){
            return $res;
        }

        M("customer")->where(['customer_id' => $customer_id])->save(['pay_passwd' => $password]);
        return ['errcode' => 0,'message' => '请求成功'];
    }
	
}