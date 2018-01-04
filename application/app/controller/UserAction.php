<?php
namespace app\app\controller;
use app\validate\UserValidate;
use app\weixin\WxHelper;
use app\oauth\PhoneOauth;
use think\Cache;
class UserAction
{
	//发送验证码
	public function sendCode(){

//        $agent = C("IGNORE_AGENT");
//        if(MODULE_NAME == "app" && ACTION_NAME == "sendcode" && !empty($agent) && isset($_SERVER['HTTP_USER_AGENT'])){
//            $flag = false;
//            foreach($agent as $r){
//                if ($_SERVER['HTTP_USER_AGENT'] == 'okhttp/3.8.0') {
//                    $flag = true;
//                    break;
//                }
//                if(strpos($_SERVER['HTTP_USER_AGENT'], $r) !== false){
//                    $flag = true;
//                    break;
//                }
//            }
//            if (!$flag) {
//                die(json_encode(['errcode' => -100, 'message' => '拜拜']));
//            }
//        }
        
	    $param = I("param");
		$type = I("type");
		$time = I('time');
		$sign = I('sign');
		$v = (int)I('v');
		if(empty($param)){
			return ['errcode' => -101, 'message' => '请输入邮箱或手机号'];
		}
//		if ($v == 1) {
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
//        }
        // 验证sign
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
		return ['errcode' => 0, 'message' => '绑定成功'];
	}
	
	//验证解除绑定
	public function unBindSendEmail(){
		$email = I("email");
		$customer_id = get_customer_id();
		$access_token = I("access_token");
		if(empty($customer_id)){
			return ['errcode' => 99, 'message' => '请重新登录'];
		}
		D("verify_code_model")->send($email,7,'email',$access_token);
		return ['errcode' => 0, 'message' => '发送成功'];
	}
	
	//验证忘记密码
	public function verifyForgetPasswd(){
		$param = I("param");
		$verify = I("verify");
		$type = I('type');
		$field = verifyParam($param);
	
		if ($field != 'phone' && $field != 'email'){
			return ['errcode' => -101, 'message' => '请填入正确格式手机号或邮箱'];
		}
		return D("verify_code_model")->verify($param,$verify,$type,$field);
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
		
		if($res['errcode'] < 0){
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
			return ['errcode' => -101, 'message' => '没有该用户'];
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
	
	/**
	 * 登录
	 */
	public function OauthLogin(){
		$res = array('message'=> '请求成功', 'errcode' => 0);
		$oauthMethod = I("oauth");
	
		$oauth = \app\oauth\YZLOauth::checkOauthMethod($oauthMethod, I(""));
		if(empty($oauth)){
			$res['errcode'] = -101;
			$res['message'] = "没有对应的方法";
			return $res;
		}
		return $oauth->loginOrRegsiter();
	}
	
	//注册
	public function oauthRegister(){
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
	
	//同步LG系统
	private function  synLgSystem($recommend){
		if(empty($recommend['tuijian'])){
			return ['errcode' => -101, 'message' => '请传入推荐编号'];
		}
		if (empty($recommend['userid'])){
			return ['errcode' => -101, 'message' => '请传入姓名'];
		}
//		if (empty($recommend['card_id'])){
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

    /**
     * 获取用户信息
     * @return array
     */
	public function getUserInfo(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['errcode' => 99, 'message' => '请重新登录'];
		}
        $res = D("user_model")->getUserInfo($customer_id);
        if ($res['errcode'] != 0) {
            return $res;
        }
        $customer = $res['content'];
        unset($customer['access_token']);
        return ["errcode" => 0, 'message' => '成功', 'content' => $customer];
	}

    /**
     * 设置头像
     */
    public function changeAvater() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        // 上传用户图像，进行修改
        $avater = I("avater");
        if(empty($avater)){
            return ['errcode' => -100, 'message' => '请传入头像图片'];
        }

        D("customer_log_model")->Log($customer_id, 3, "修改头像，头像url为：". $avater);
        D("customer_model")->mySave(['customer_id' => $customer_id],['avater' => $avater]);

        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        return ['errcode' => 0, 'message' => '成功', 'content' => $host.$avater.$suffix];
    }

    /**
     * 修改用户名
     */
    public function changeUsername() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $username = I('nickname');
        if (empty($username)) {
            return ['errcode' => -100, 'message' => '请传入用户名'];
        }
        D("customer_log_model")->Log($customer_id, 3, "修改用户名，用户名为：". $username);
        D("user_model")->mySave(['customer_id' => $customer_id],['nickname' => $username]);
        return ['errcode' => 0, 'message' => '成功', 'content' => $username];
    }

    /**
     * 设置出生日期
     */
    public function changeBirthday() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $birthday = I('birthday');
        if (empty($birthday)) {
            return ['errcode' => -100, 'message' => '请传入出生日期'];
        }
        D("customer_log_model")->Log($customer_id, 3, "修改出生日期，出生日期为：". date("Y-m-d",$birthday));
        D("user_model")->mySave(['customer_id' => $customer_id],['birthday' => $birthday]);
        return ['errcode' => 0, 'message' => '成功', 'content' => $birthday];
    }

    /**
     * 设置性别
     * @return array
     */
    public function changeSex() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $type = (int)I('type');
        $sex = $type == 1 ? "男" : "女";
        D("customer_log_model")->Log($customer_id, 3, "修改性别，性别为：". $sex);
        D("user_model")->mySave(['customer_id' => $customer_id],['sex' => $sex]);
        return ['errcode' => 0, 'message' => '成功', 'content' => $sex];
    }

    /**
     * 绑定手机
     */
    public function bindPhone() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $phone = I('phone');
        $code = I("code");
        $res = D("verify_code_model")->verify($phone,$code, 8);
        if($res['errcode'] < 0){
            return $res;
        }
        $customer = D("customer_model")->myFind(['customer_id' => $customer_id],"phone");
        if (empty($customer)) {
            return ['errcode' => -100, 'message' => '未找到该用户'];
        }
        D("customer_log_model")->Log($customer_id, 3, "绑定手机，手机为：". $phone);
        D("customer_model")->mySave(['customer_id' => $customer_id],['phone' => $phone]);
        return ['errcode' => 0, 'message' => '绑定成功', 'content' => $phone];
    }

    /**
     * 更改绑定手机
     */
    public function changeBindPhone() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $phone = I('phone');
        $code = I("code");
        $res = D("verify_code_model")->verify($phone,$code, 9);
        if($res['errcode'] < 0){
            return $res;
        }
        $newphone = I('newphone');
        $newcode = I('newcode');
        $res = D("verify_code_model")->verify($newphone,$newcode, 9);
        if($res['errcode'] < 0){
            return $res;
        }
        $customer = D("customer_model")->myFind(['customer_id' => $customer_id],"phone");
        if (empty($customer)) {
            return ['errcode' => -100, 'message' => '未找到该用户'];
        }
        D("customer_log_model")->Log($customer_id, 3, "更改绑定手机，手机为：". $newphone);
        D("customer_model")->mySave(['customer_id' => $customer_id],['phone' => $newphone]);
        return ['errcode' => 0, 'message' => '更改绑定成功', 'content' => $newphone];
    }

    /**
     * 解绑邮箱
     */
    public function unbindEmail() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $email = I("email");
        $code = I('code');
        $res = D("verify_code_model")->verify($email,$code, 7, "email");
        if($res['errcode'] < 0){
            return $res;
        }
        $customer = D("customer_model")->myFind(['customer_id' => $customer_id],"phone");
        if (empty($customer)) {
            return ['errcode' => -100, 'message' => '未找到该用户'];
        }
        D("customer_log_model")->Log($customer_id, 3, "解绑邮箱". $email);
        D("customer_model")->mySave(['customer_id' => $customer_id],['email' => ""]);
        return ['errcode' => 0, 'message' => '解绑成功'];
    }

    /**
     * 可用优惠券列表
     * @return array
     */
    public function getCoupons() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        return D('user_model')->getCoupons($customer_id,1);
    }

    /**
     * 不可用优惠券列表
     * @return array
     */
    public function getUnableCoupons() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        return D('user_model')->getCoupons($customer_id,0);
    }

    /**
     * 余额
     * @return array
     */
    public function balance() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        return D('user_model')->balance($customer_id);
    }

    /**
     * 资金明细
     * @return array
     */
    public function finance() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }
        return D('user_model')->finance($customer_id,$page);
    }

    /**
     * 会员积分
     * @return array
     */
    public function shoppingCoin() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        return D('user_model')->shoppingCoin($customer_id);
    }

    /**
     * 会员积分明细
     * @return array
     */
    public function shoppingCoinDetail() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }
        return D('user_model')->shoppingCoinDetail($customer_id,$page);
    }

    /**
     * 积分
     * @return array
     */
    public function integration() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        return D('user_model')->integration($customer_id);
    }

    /**
     * 积分明细
     * @return array
     */
    public function integrationDetail() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }
        return D('user_model')->integrationDetail($customer_id,$page);
    }

    /**
     * 收藏列表
     * @return array
     */
    public function collection() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }
        return D('user_model')->collection($customer_id,$page);
    }

    /**
     * 取消收藏
     * @return array
     */
    public function cancelCollections() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $ids = I('ids');
        if (empty($ids)) {
            return ['errcode' => -100, 'message' => '请传入收藏信息'];
        }
        return D('user_model')->cancelCollections($customer_id,$ids);
    }

    /**
     * 我的关注
     * @return array
     */
    public function attention() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }
        return D('user_model')->attention($customer_id,$page);
    }

    /**
     * 取消关注
     * @return array
     */
    public function cancelAttentions() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $ids = I('ids');
        if (empty($ids)) {
            return ['errcode' => -100, 'message' => '请传入关注信息'];
        }
        return D('user_model')->cancelAttentions($customer_id,$ids);
    }

    /**
     * 我的足迹
     * @return array
     */
    public function footmark() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }
        return D('user_model')->footmark($customer_id,$page);
    }

    /**
     * 删除足迹
     * @return array
     */
    public function delFoots() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $ids = I('ids');
        if (empty($ids)) {
            return ['errcode' => -100, 'message' => '请传入足迹信息'];
        }
        return D('user_model')->delFoots($customer_id,$ids);
    }

    /**
     * 商家入驻
     * @return array
     */
    public function shopJoin() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $company = I('company');
        if (empty($company)) {
            return ['errcode' => -100, 'message' => '请输入公司名'];
        }
        $province = I('province');
        if (empty($province)) {
            return ['errcode' => -101, 'message' => '请传入省'];
        }
        $city = I('city');
        if (empty($city)) {
            return ['errcode' => -102, 'message' => '请传入市'];
        }
        $district = I('district');
        if (empty($district)) {
            return ['errcode' => -103, 'message' => '请传入区'];
        }
        $address = I('address');
        if (empty($address)) {
            return ['errcode' => -104, 'message' => '请输入详细地址'];
        }
        $licence = I('licence');
        if (empty($licence)) {
            return ['errcode' => -105, 'message' => '请传入营业执照'];
        }
        $name = I('name');
        if (empty($name)) {
            return ['errcode' => -106, 'message' => '请传入姓名'];
        }
        $phone = I('phone');
        if (empty($phone)) {
            return ['errcode' => -107, 'message' => '请传入联系电话'];
        }
        if(!preg_match("/^1\d{10}$/", $phone)){
            return ['errcode' => -108, 'message' => '手机号格式错误'];
        }
        $shop_name = I('shop_name');
        if (empty($shop_name)) {
            return ['errcode' => -109, 'message' => '请传入店铺名'];
        }
        $wx_qq = I('wx_qq');
        $card_f = I('card_f');
        if (empty($card_f)) {
            return ['errcode' => -110, 'message' => '请传入身份证正面照'];
        }
        $card_b = I('card_b');
        if (empty($card_b)) {
            return ['errcode' => -111, 'message' => '请传入身份证背面照'];
        }

        $shop = M('seller_check')->where(['customer_id' => $customer_id, 'state' => ['neq',3]])->find();
        if (!empty($shop)) {
            return ['errcode' => -300, 'message' => '不能重复提交'];
        }

        //     对输入的省份进行判断
        $addressVerify = D('address_model')->verify($province,$city,$district);

        $data = [
            'customer_id' => $customer_id,
            'company_name' => $company,
            'province' => $addressVerify['province_id'],
            'city' => $addressVerify['city_id'],
            'district' => $addressVerify['area_id'],
            'address' => $address,
            'licence' => $licence,
            'contact_people_name' => $name,
            'phone' => $phone,
            'shop_name' => $shop_name,
            'qq_wx' => $wx_qq,
            'card_f' => $card_f,
            'card_b' => $card_b,
            'date_add' => time(),
            'date_upd' => time(),
        ];

        $id = M('seller_check')->add($data);
        if (empty($id)) {
            return ['errcode' => -200, 'message' => '添加失败'];
        }

        return ['errcode' => 0, 'message' => '添加成功'];
    }

    /**
     * 投诉
     * @return array
     */
    public function complain() {
        $customer_id = get_customer_id();
        if(empty($customer_id)){
            return ['errcode' => 99 , 'message' => '请传入用户信息'];
        }
        $title = I('title');
        if (empty($title)) {
            return ['errcode' => -100, 'message' => '请传入投诉主题'];
        }
        $content = I('content');
        if (empty($content)) {
            return ['errcode' => -101, 'message' => '请传入具体内容'];
        }
        return D('user_model')->complain($customer_id,$title,$content);
    }

    /**
     * 店铺佣金
     * @return array
     */
    public function commission() {
        $customer_id = get_customer_id();
        if(empty($customer_id)){
            return ['errcode' => 99 , 'message' => '请传入用户信息'];
        }
        return D('user_model')->commission($customer_id);
    }

    /**
     * 佣金明细
     * @return array
     */
    public function commissionDetail() {
        $customer_id = get_customer_id();
        if(empty($customer_id)){
            return ['errcode' => 99 , 'message' => '请传入用户信息'];
        }
        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }
        return D('user_model')->commissionDetail($customer_id,$page);
    }

    /**
     * 缴纳保证金
     * @return array
     */
    public function bail() {
        $customer_id = get_customer_id();
        if(empty($customer_id)){
            return ['errcode' => 99 , 'message' => '请传入用户信息'];
        }
        $type = I("type");

        $sum = I('account');

        $bail = \app\library\SettingHelper::get("shuaibo_bail",['money' => 10000,'url' => "http://" . get_domain() ."/wap/home/protocol"]);
        if ($sum != $bail['money']) {
            return ['errcode' => -100, 'message' => '请传入正确的金额'];
        }

        return D('user_model')->bail($customer_id,$type,$sum);
    }

    /**
     * 充值
     * @return array
     */
    public function recharge() {
        $customer_id = get_customer_id();
        if(empty($customer_id)){
            return ['errcode' => 99 , 'message' => '请传入用户信息'];
        }
        $type = I("type");
        if ($type == 1) {
            return ['errcode' => -200, 'message' => '不能使用余额支付'];
        }

        $sum = I('account');

        return D('user_model')->recharge($customer_id,$type,$sum);
    }

    /*
     * 提现
     */
    public function withdraw() {
        $customer_id = get_customer_id();
        if(!$customer_id){
            return ['errcode' => 99 , 'message' => '请重新登录'];
        }
        $money = round(I('money'),2);
        if ($money <= 0) {
            return ['errcode' => -103 , 'message' => '请输入提现金额'];
        }
        $pay_type = I('pay_type');
        if (empty($pay_type)) {
            return ['errcode' => -101 , 'message' => '请输入提现类型'];
        }
        $account = I("account");
        if(!$account){
            return ['errcode' => -101 , 'message' => '请输入账号'];
        }
        $realname = I("realname");
        if(!$realname){
            return ['errcode' => -102 , 'message' => '请输入真实姓名'];
        }
        $subbranch = I('subbranch');
        if ($pay_type == 1 && empty($subbranch)) {
            return ['errcode' => -102 , 'message' => '请输入开户支行'];
        }
        $type = I('type');
        if (empty($type)) {
            return ['errcode' => -104 , 'message' => '请输入类型'];
        }

        // 可提现佣金
        $condition = [
            'op1.finance_type' => 1,
            'op1.is_minus' => 2,
            'o.order_state' => ['in','4,5,7'],
            'cwo.state' => 1
        ];
        $condition['_string'] = "op1.customer_id = fo.customer_id";
        $sql1 = M('finance_op')
            ->alias("op1")
            ->where($condition)
            ->join("order o","o.order_sn = op1.order_sn")
            ->join('customer_withdraw_order cwo','cwo.order_sn = op1.order_sn')
            ->field("IFNULL(sum(real_amount),0)")
            ->buildSql();

        $commission = M("finance_op")
            ->alias("fo")
            ->join("seller_shopinfo ss","ss.customer_id = fo.customer_id")
            ->where(['fo.customer_id' => $customer_id])
            ->field("ifnull($sql1,0) as amount")
            ->group("fo.customer_id")
            ->find();

        $customer = M("customer")->where(['customer_id' => $customer_id])->field("active, phone, account, commission")->find();
        $customer['commission'] = $commission['amount'];

        if(!$customer || $customer['active'] == 0){
//            $seller_info = \app\library\SettingHelper::get("store_seller_info",["service_phone" => '15906716507','address' => '杭州市']);
            return ['errcode' => -106, 'message' => '抱歉，您无法执行此操作，如有疑问，请拨打客服电话'];
        }
//        if(!$customer['phone']){
//            return ['errcode' => -107 , 'message' =>'抱歉，您需先绑定手机号，方可提现'];
//        }
        if ($type == 1) {
            if ($customer['account'] - $money < 0) {
                return ['errcode' => -108 ,'message' =>'可提现余额不足'];
            }
        } elseif ($type == 2) {
            if ($customer['commission'] - $money != 0) {
                return ['errcode' => -108 ,'message' =>'佣金必须全提'];
            }
            if ($customer['commission'] - $money != 0) {
                return ['errcode' => -108 ,'message' =>'佣金必须全额提现'];
            }
        } else {
            return ['errcode' => -104 , 'message' => '类型无法识别'];
        }

        $order_sn = createNo("customer_withdraw","order_sn", "CA");

        $data = [
            'order_sn' => $order_sn,
            'money' => $money,
            'date_add' => time(),
            'customer_id' => $customer_id,
            'account' => $account,
            'realname' => $realname,
            'subbranch' => $subbranch,
            'type' => $pay_type,
            'style' => $type,
        ];
        $id = M("customer_withdraw")->add($data);
        if ($type == 1) {
            M("customer")->where(['customer_id' => $customer_id])->setDec("account",$money);
        } elseif ($type == 2) {
            M("customer")->where(['customer_id' => $customer_id])->setDec("commission",$money);
            M("customer_withdraw_order")->where(['customer_id' => $customer_id,'state' => 1])->save(['state' => 2]);
        }

        return ['errcode' => 0, 'message' => '申请提现成功'];

        // 支付宝提现
//        return D("customer_withdraw")->withdraw($id, 0);
    }












	public function qrcode(){
		$customer_id = get_customer_id();
		if(empty($customer_id)){
			return ['errcode' => 99 , 'message' => '请传入用户信息'];
		}
		$content=D("user")->qrcode($customer_id);
		
		if($content['errcode'] < 0){
			return $content;
		}
		$host = \app\library\SettingHelper::get("bear_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(100, 100);
		$share = []; 
		$share = \app\library\SettingHelper::get("wx_share",['share_title' => '零美云和', 'share_desc'=>'欢迎关注零美云合' , 'share_image' => '']);
		$share['url'] = "http://" . get_domain() . "/wap/home/qrcode?uuid=" . $content['content']['uuid'];
		$share['share_image'] = $host . $share['share_image'] . $suffix;
		$content = ['share' => $share, 'images' =>  $host . $content['content']['name']];
		return ['errcode' => 0 , 'message' => '请求成功', 'content' => $content];
	}
	
/**
 * 修改头像
 */
	public function UploadAvater(){
		$res = array(
				'message' => "请求成功",
				'errcode' => 0
			);
		$customer_id = get_customer_id();
		if(empty($customer_id) ||$customer_id <= 0){
			return ['errcode' => 99, 'message' => '请重新登陆'];
		}
// 上传用户图像，进行修改
		$avater = I("avater");
		if(empty($avater)){
			return ['errcode' => -101, 'message' => '请传入图片'];
		}
        
		D("customer_log")->Log($customer_id, 3, "修改头像，头像url为：". $avater);
		M("customer")
		->where(array("customer_id" => $customer_id))
		->save(array("avater" => $avater));
		
		$host = \app\library\SettingHelper::get("bear_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		$res['content'] = ['avater' => $host.$avater.$suffix];
		return $res;
	}

/**
 * 修改昵称
 */
	public function EditName(){
		$res = array(
			'message' => '错误信息',
			'errcode' => 0
			);
		$customer_id = get_customer_id();
		
		if(empty($customer_id) ||$customer_id <= 0){
			return ['errcode' => 99, 'message' => '请重新登陆'];
		}
		
		$user = [
			'nickname' => I("nickname"),
			'customer_id' => $customer_id
		];
		
		
		if(!$user["nickname"]){
			$res['message'] = "请输入昵称";
			$res['errcode'] = -403;
			return $res;
		}
		
		$default=M("customer")->alias("c")->field("c.nickname")->where(['customer_id' => $customer_id])->find();
		
		if(empty($default)){
			return ['errcode' => 99, 'message' => '请重新登陆'];
		}
		$length = (strlen($user['nickname']) + mb_strlen($user['nickname'],'UTF8')) / 2;
		if( $length > 20){
			$res['message'] = "昵称长度必须在小于20";
			$res['errcode'] = -404;
			return $res;
		}
		
        D("customer_log")->Log($customer_id, 3, "修改昵称为：". $user['nickname']);
		M("customer")->where(array('customer_id' => $user['customer_id']))->save($user);
		D("customer")->SetCustomer($customer_id);
		return $res;
	}
	
	public function changeaddress(){
		$customer_id = get_customer_id();
		
		if(empty($customer_id) ||$customer_id <= 0){
			return ['errcode' => 99, 'message' => '请重新登陆'];
		}
		
		$province = I("province");
		
		$city = I("city");
		
		if(empty($province) || empty($city)){
			return ['errcode' => -101 , 'message' => '请选择正确的地区'];
		}
		M("customer")->where(['customer_id' => $customer_id])->save(['province' => $province, 'city' => $city]);
		D("customer")->SetCustomer($customer_id);
		return ['errcode' => 0, 'message' => '操作成功' ];
	}


/**
 * 修改密码
 */
	public function EditPwd(){
		$res = array(
			'message' => '修改成功',
			'errcode' => 0
			);
		$customer_id = get_customer_id();
		$user = [
			'passwd' => I("now_pwd"),
			'customer_id' => $customer_id
		];
		
		$old_pwd = I("old_pwd");
		if(empty($customer_id) ||$customer_id <= 0){
            return ['errcode' => 99, 'message' => '请重新登陆'];
        }
        $phone = I("phone");
        
		if(!$user['passwd']){
			$res['message'] = "请输入密码";
			$res['errcode'] = -405;
			return $res;
		}
		
		if(empty($phone)){
			return ['errocode' => -401, 'message' => '请输入手机号'];
		}

		$length = mb_strlen($user['passwd']);
		if($length < 6 || $length > 14){
			$res['message'] = "新密码长度必须在6-14之间";
			$res['errcode'] = -406;
			return $res;
		}

		$customer = M("customer")->where(array('customer_id'=> $user['customer_id']))->field("customer_id, phone,passwd")->find();
		if(empty($customer)){
			$res['message'] = "客户信息错误";
			$res['errcode'] = -407;
			return $res;
		}
		if(!empty($customer['passwd']) && md5($old_pwd) != $customer['passwd']){
			$res['message'] = "密码错误";
			$res['errcode'] = -407;
			return $res;
		}
		if(!empty($customer['phone']) && $phone != $customer['phone']){
			return ['errcode' => -401, 'message' => '手机号错误'];
		}
// 用户进行修改密码并保存
		
		$user['passwd'] = md5($user['passwd']);
		D("customer_log")->Log($customer_id, 2, "更改密码");
		M("customer")->where(array("customer_id" => $user['customer_id']))->save($user);

		return $res;
	}
	/**
	 * 生成推广码
	 */
	public function createPromoCode(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['errcode' => -101 , 'message' => '请重新登陆'];
		}
		
		$customer = M("customer")->where(['customer_id' => $customer_id])->field("promo_code")->find();
		if(!empty($customer['promo_code'])){
			return ['errcode' => -102, 'message' => '该用户已拥有推广码'];
		}
		
		$promo_code = "";
		while (1){
			$promo_code = get_rand_name();
			$customer = M("customer")->where(["promo_code" => $promo_code])->field("customer_id")->find();
			if(empty($customer)){
				break;
			}
		}
		M("customer")->where(['customer_id' => $customer_id])->save(['promo_code' => $promo_code]);
		return ['errcode' => 0, 'message' => '请求成功', 'content' => $promo_code];
	}
	/**
	 * 获取推广码
	 */
	public function getPromoCode(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['errcode' => 99, 'message' => '请重新登录'];
		}
		$promo_code = M("customer")
		->where(['customer_id' => $customer_id])
		->field("promo_code")
		->find();
		
		$host = \app\library\SettingHelper::get("bear_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		$friends = M("customer_extend")
		->alias("ce")
		->join("customer c","c.customer_id = ce.customer_id")
		->join("order o","o.customer_id = c.customer_id")
		->where(['ce.pid' => $customer_id, 'ce.level' => 1, 'o.order_state' => 5])
		->field("c.realname,c.phone,o.order_amount,c.customer_id,concat('$host',c.avater,'$suffix') as avater")
		->limit(3)
		->select();
		
		return ['errcode' => 0, 'message' => '请求成功', 'content'=> ['promo_code' => $promo_code, 'friends' => $friends]];
	}
	
	/**
	 * 获取填写推广码
	 */
	public function getWritePromoCode(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['errcode' => 99, 'message' => '请重新登录'];
		}
		
		$customer = M("customer")
		->alias("c")
		->join("customer cr","c.agent_id = cr.customer_id","LEFT")
		->where(['c.customer_id' => $customer_id])
		->field("cr.promo_code")
		->find();
		
		return ['errcode' => 0, 'message' => '请求成功', 'content' => $customer['promo_code']];
	}
	
	/**
	 * 填写推广码
	 */
	public function writePromoCode(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['errcode' => 99, 'message' => '请重新登录'];
		}
		
		$promo_code = I("promo_code");
		if(empty($promo_code)){
			return ['errcode' => -101, 'message' => '请填入推广码'];
		}
		
		$customer = M("customer")
		->alias("c")
		->join("customer cr","cr.customer_id = c.agent_id","LEFT")
		->join("customer crr","crr.customer_id = cr.agent_id","LEFT")
		->where(['c.promo_code' => $promo_code])
		->field("c.customer_id as c_id,cr.customer_id as cr_id,crr.customer_id as crr_id")
		->find();
		
		if(empty($customer['c_id'])){
			return ['errcode' => -101, 'message' => '该推广码无效'];
		}
		M("customer_extend")->add(["customer_id" => $customer_id,"pid" => $customer['c_id'],"level" => 1,"date_add" => time()]);
		
		if(!empty($customer['cr_id'])){
			M("customer_extend")->add(['customer_id' => $customer_id,"pid" => $customer['cr_id'],"level"=> 2,"date_add" => time()]);
		}
		
		if(!empty($customer['crr_id'])){
			M("customer_extend")->add(['customer_id' => $customer_id, "pid" => $customer['crr_id'], 'level' => 3, "date_add" => time()]);
		}
		
		M("customer")->where(["customer_id" => $customer_id])->save(['agent_id' => $customer['c_id']]);
		
		return ['errcode' => 0, 'message'=> '填写成功'];
	}
	
	/**
	 * 关于我们
	 */
	public function aboutUs(){
		$about = "http://".get_domain()."/wap/home/about_us";
		return ['errcode' => 0, 'message' => '请求成功', 'content' => $about];
	}
	/**
	 * 分红规则
	 */
	public function rule(){
		$rule = "http://".get_domain()."/wap/home/rule";
		return ['errcode' => 0, 'message' => '请求成功', 'content' =>$rule];
	}
	/**
	 * 推广码规则
	 */
	public function promoCodeRule(){
		$rule = "http://".get_domain()."/wap/home/promo_code_rule";
		return ['errcode' => 0, 'message' => '请求成功', 'content' => $rule];
	}
	
/**
 * 反馈意见
 */
	public function feedback(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['errcode' => 99 , 'message' => '请重新登陆'];
		}
		
		$feedback_reason_id = I("feedback_reason_id");
		if(empty($feedback_reason_id)){
			return ['errcode' => -101, 'message' => '请传入反馈问题'];
		}
		$content = I("content");
		if(empty($content)){
			return ['errcode' => -101, 'message' => '请输入反馈内容'];
		}
		$data = array(
				'customer_id' => $customer_id,
				'content' => $content,
				'feedback_reason_id' => $feedback_reason_id,
				'date_add' => time()
		);
// 		获取反馈意见并保存
		M("feedback")->add($data);
		return ['errcode' => 0, 'message' => '发送成功'];
	}
	/**
	 * 反馈问题内容
	 */
	public function feedbackReason(){
		$reason = M("feedback_reason")->field("feedback_reason_id,content")->select();
		return ['errcode' => 0, 'message' => '请求成功', 'content' => $reason];
	}
	
/**
 * 分享成功
 */	
	public function sharesuccess(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['errcode' => 99, 'message' => '请先登录'];
		}
		$platform = I("platform");
		
		$type = I("type");
		
		$id = I("id");
		$data = ['customer_id' => $customer_id,
				'platform' => $platform,
				'type' => $type,
				'id' => $id,
				'date_add' => time()
		];
// 		添加平台分享的信息
		M("platform_share")->add($data);
		return ['errcode' => 0,'message' => '分享成功'];
	}
	
	public function changephone_sendcode(){
	    return;
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['errcode' => 99, 'message' => '请先登录'];
		}
		$phone=M("customer")->where(['customer_id' => $customer_id])->getField("phone");
		if(empty($phone)){
			return ['errcode' => -102 , 'message' => '该账号暂未有手机绑定'];
		}
		
		
		$ip = get_client_ip();
		$times = (int)Cache::get("bear_send_code_". $ip . "_" . $phone);
		if($times > 3){
			return ['errcode' => -101, 'message' => '请慢点发送短信'];
		}
		Cache::set("bear_send_code_". $ip . "_" . $phone, $times++, 60);
		
		$code = D("verify_code")->send($phone, 3);
		return ['errcode' => 0 ,'message' =>'请求成功'];
	}
	
	
	public function changephone_first(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['errcode' => 99, 'message' => '请先登录'];
		}
		$phone=M("customer")->where(['customer_id' => $customer_id])->getField("phone");
		$verify_code=I("post.verify_code");
		$result=D("verify_code")->verify($phone,$verify_code,3);
		S("changephone_".$customer_id,md5($phone));
		return $result;
	}
	public function changephone_second(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['errcode' => 99, 'message' => '请先登录'];
		}
		
		$phone=I("post.phone");
		$verify_code=I("post.verify_code");
		
		
		$customer = M("customer")->where(['customer_id'=>$customer_id])->field("phone,customer_id")->find();
		
		if(!$customer['phone']){
			return ['errcode' => -102 ,'message' => '请先至商品详情点击购买完善资料后方可修改手机号'];
		}
		
		if($customer['phone'] && S("changephone_".$customer_id) != md5($customer['phone'])){
			return ['errcode' => -101 ,'message' => '请先验证您的手机号'];
		}
		
		$count = M("customer")->where(['customer_id' => ['neq' , $customer_id], 'phone' => $phone])->count();
		
		if($count > 0){
			return ['errcode' => -102 ,  'message' => '手机号已被注册，请更换绑定手机'];
		}
		
		
		
		$result=D("verify_code")->verify($phone,$verify_code,4);
		if($result['errcode']==0){
			M("customer")->where(['customer_id'=>$customer_id])->save(['phone'=>$phone]);
			S("changephone_".$customer_id, "0");
		}
		
		D("customer")->SetCustomer($customer_id);
		return $result;
	}
	
	public function get_goal_customer(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['errcode' => 99, 'message' => '请先登录'];
		}
		
		$page = (int)I("page");
		
		$level = (int)I("level");
		
		$map = [];
		$order = "";
		if($level == 0){
			$having = "ifnull(sum(cer.commission),0) = 0";
			$map['c.agent_id'] = $customer_id;
			$map['ce.level'] = 1;
			$order = "c.date_add desc";
		}else{
			$having = "ifnull(sum(cer.commission),0) > 0";
			if($level > 0){
				$map['ce.level'] = $level;
			}
			$map['ce.pid'] = $customer_id;
			$order = "c.date_sale desc";
		}
		
		$host = \app\library\SettingHelper::get("bear_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		$agents = M("customer")
		->alias("c")
		->join("customer_extend ce" ,"ce.customer_id = c.customer_id ")
		->join("customer_extend_record cer","cer.customer_extend_id = ce.customer_extend_id and cer.state in (1, 3)","LEFT")
		->group("c.customer_id")
		->where($map)
		->order($order)
		->field("ce.level, c.date_sale,c.uuid,c.commission, c.nickname, c.avater as image,c.phone, c.uuid")
		->having($having)
		->limit(20)
		->page($page)
		->select();
		foreach ($agents as &$a){
			if($a['image'] && strpos($a['image'], "http") !== 0){
				$a['image'] = $host . $a['image'] . $suffix;
			}
		}
		
		return ['errcode' => 0,'message' => '请求成功', 'content' => $agents];
	}


	public function get_goal_customer_web(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['errcode' => 99, 'message' => '请先登录'];
		}

		$page = (int)I("page");

		$level = (int)I("level");

		$map = [];
		$order = "";
		if($level == 0){
			$having = "ifnull(sum(cer.commission),0) = 0";
			$map['c.agent_id'] = $customer_id;
			$map['ce.level'] = 1;
			$order = "c.date_add desc";
		}else{
			$having = "ifnull(sum(cer.commission),0) > 0";
			if($level > 0){
				$map['ce.level'] = $level;
			}
			$map['ce.pid'] = $customer_id;
			$order = "c.date_sale desc";
		}

		$host = \app\library\SettingHelper::get("bear_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);


		$used_sql = M("customer_withdraw")
			->where(['customer_id = c.customer_id', 'state' => 1])
			->field("sum(money)")
			->buildSql();

		$customer_count_sql = M("customer_extend")
			->alias("ce")
			->where(['ce.level' => 1,'ce.commission' => ['gt', 0] ,'ce.pid = c.customer_id' ])
			->field("count(1)")
			->buildSql();

		$agents = M("customer")
			->alias("c")
			->join("customer_group cg","cg.group_id = c.group_id")
			->join("customer_extend ce" ,"ce.customer_id = c.customer_id ")
			->join("customer_extend_record cer","cer.customer_extend_id = ce.customer_extend_id and cer.state in (1, 3)","LEFT")
			->group("c.customer_id")
			->where($map)
			->order($order)
			->field("cg.name as group_name,c.uuid,c.commission, c.nickname, c.avater as image,c.phone, c.uuid, ifnull({$used_sql},0) as withdraw,ifnull({$customer_count_sql},0) as customer_count")
			->having($having)
			->limit(10)
			->page($page)
			->select();
		foreach ($agents as &$a){
			if($a['image'] && strpos($a['image'], "http") !== 0){
				$a['image'] = $host . $a['image'] . $suffix;
			}
		}

		return ['errcode' => 0,'message' => '请求成功', 'content' => $agents];
	}

	public function get_goal_customer_count(){
		$customer_id = get_customer_id();
		$type = (int)I("type");
		if(!$customer_id){
			return ['errcode' => 99 ,'message' => '请重新登录'];
		}
		
		$content = [];
		if($type == 0){
			$content['level_0'] = D("user")->getgoalcustomer($customer_id);
		}else{
			$content = D("user")->getmycustomer($customer_id);
		}
		
		return ['errcode' => 0 ,'message' => '请求成功', 'content' => $content];
	}
	
	
	public function need_push(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['errcode' => 99, 'message' => '请先登录'];
		}
		
		$need_push = (int)I("need_push");
		
		M("customer")->where(['customer_id' => $customer_id])->save(['need_push' => $need_push]);
		
		D("customer")->SetCustomer($customer_id);
		return ['errcode' => 0,'message' => '操作成功'];
	}
	
	public function member_rank(){
		$customer_id = get_customer_id();
		
		if(!$customer_id){
			return ['errcode' => 99 , 'message' =>'请重新登录'];
		}
		
		$levels = \app\library\SettingHelper::get_levels();
		$group_id = M("customer")->where(['customer_id' => $customer_id])->getField("group_id");
		$levels = array_reverse($levels);
		
		return ['errcode' => 0 ,'message' =>'请求成功', 'content' => ['levels' => $levels,'group_id' => $group_id]];
	}
	
	public function get_customer_detail(){
		$uuid = I("uuid");
		$customer = D("customer")->getCustomerDetail($uuid);
		if(is_string($customer)){
			return ['errcode' => -101,'message' => $customer];
		}
		
		return ['errcode' => 0 , 'message' =>'请求成功','content' => $customer];
	}
	
	public function edit_person(){
		$customer_id = get_customer_id();
		
		if(!$customer_id){
			return ['errcode' => 99 , 'message' =>'请重新登录'];
		}
		!($nickname = I("nickname"));
		if(empty($nickname)){
			return ['errcode' => -102 , 'message' => '请输入昵称'];
		}
		!($sex = I("sex"));
		if(empty($sex)){
			return ['errcode' => -102 , 'message' => '请选择性别'];
		}
		!($year = I("year"));
		if(empty($year)){
			return ['errcode' => -102 , 'message' => '请选择出生年'];
		}
		!($month = I("month"));
		if(empty($month)){
			return ['errcode' => -102 , 'message' => '请选择出生月'];
		}
		!($day = I("day"));
		if(empty($day)){
			return ['errcode' => -102 , 'message' => '请选择出生日'];
		}
		!($province = I("province"));
		if(empty($province)){
			return ['errcode' => -102 , 'message' => '请选择省'];
		}
		!($city = I("city"));
		if(empty($city)){
			return ['errcode' => -102 , 'message' => '请选择城市'];
		}
		$birthday = strtotime($year.'-'.$month.'-'.$day);
		$data = ['nickname' => $nickname, 'sex' => $sex ,'birthday' => $birthday ,'province' => $province ,'city' => $city];
		M("customer")
		->where(['customer_id' => $customer_id ])
		->save($data);
		return ['errcode' => 0 , 'message' =>'保存成功'];
	}
	/**
	 * 个人资料
	 */
	public function getSimUserInfo(){
		$customer_id = get_customer_id();
		if(!$customer_id){
			return ['errcode' => 99 , 'message' =>'请重新登录'];
		}
	
		$host = \app\library\SettingHelper::get("bear_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		
		$customer = M("customer")
		->where(['customer_id' => $customer_id])
		->field("nickname,sex,phone,concat('$host',avater,'$suffix') as avater")
		->find();
		
		if(empty($customer)){
			return ['errcode' => -102, 'message' => '用户不存在'];
		}
		return ['errcode' => 0, 'message' => '请求成功', 'content' => $customer];
	}
    /**
     * 我的钱包
     */
    public function getWalletInfo(){
        $customer_id = get_customer_id();
        if(!$customer_id){
            return ['errcode' => 99 , 'message' =>'请重新登录'];
        }

        $customer = M("customer")
            ->where(['customer_id' => $customer_id])
            ->field("account as remain_money,bonus_pool")
            ->find();

        if(empty($customer)){
            return ['errcode' => -102, 'message' => '用户不存在'];
        }

        $sum = M("customer_extend")
            ->alias("ce")
            ->join("customer_extend_record cer","ce.customer_extend_id = cer.customer_extend_id","LEFT")
            ->where(['cer.state' => 1, 'ce.pid' => $customer_id,'cer.date_add' => ['gt', strtotime(date("Y-m-d"))]])
            ->sum("cer.commission");

        if($customer){
            $customer['total_commission'] = $sum ? $sum : "0";
        }

        //我的钱包页面只显示3条
//        $finance = M("finance")
//            ->order("date_add desc")
//            ->where(['customer_id' => $customer_id])
//            ->limit(3)
//            ->select();

        $finance = M("finance")
            ->alias("f")
            ->join("customer_extend ce","f.customer_id = ce.pid","LEFT")
            ->join("customer_extend_record cer","ce.customer_extend_id = cer.customer_extend_id","LEFT")
            ->join("customer c","c.customer_id = ce.customer_id","LEFT")
            ->join("order o","o.order_sn = f.order_sn","LEFT")
            ->group("f.record_id")
            ->order("f.date_add desc")
            ->where(['f.customer_id' => $customer_id])
            ->field("f.finance_type_id,f.amount,f.order_sn,f.date_add,c.nickname,o.order_amount as consume")
            ->limit(3)
            ->select();
        $customer['finance'] = $finance;

        //分红规则
        $customer['rule'] = $this->rule()["content"];

        return ['errcode' => 0, 'message' => '请求成功', 'content' => $customer];

    }
    /**
     * 钱包明细
     */
    public function getWalletDetail(){
        $customer_id = get_customer_id();
        if(!$customer_id){
            return ['errcode' => 99 , 'message' =>'请重新登录'];
        }

        $customer = M("customer")
            ->where(['customer_id' => $customer_id])
            ->field("account as remain_money,bonus_pool")
            ->find();

        if(empty($customer)){
            return ['errcode' => -102, 'message' => '用户不存在'];
        }

        $page = (int)I("page");
        if($page <= 0){
            $page = 1;
        }

//        $finance = M("finance")
//            ->order("date_add desc")
//            ->where(['customer_id' => $customer_id])
//            ->limit(10)
//            ->page($page)
//            ->select();

        $finance = M("finance")
            ->alias("f")
            ->join("customer_extend ce","f.customer_id = ce.pid","LEFT")
            ->join("customer_extend_record cer","ce.customer_extend_id = cer.customer_extend_id","LEFT")
            ->join("customer c","c.customer_id = ce.customer_id","LEFT")
            ->join("order o","o.order_sn = f.order_sn","LEFT")
            ->group("f.record_id")
            ->order("f.date_add desc")
            ->where(['f.customer_id' => $customer_id])
            ->field("f.finance_type_id,f.amount,f.order_sn,f.date_add,c.nickname,o.order_amount as consume")
            ->limit(10)
            ->page($page)
            ->select();


//        $finance = M("finance")
//            ->where(['customer_id' => $customer_id])
//            ->field("finance_type_id,amount,order_sn,date_add")
//            ->limit(10)
//            ->page($page)
//            ->select();
        return ['errcode' => 0, 'message' => '请求成功', 'content' => $finance];
    }
    /**
     * 获取提现页面信息
     */
    public function getWithDrawInfo(){
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }

        //服务费介绍
        $withdraw_introduce = "提现将收取10%的服务费，工作日2小时内到账";
        //服务费比例
        $serviceFee_ratio = "0.1";
        //支付宝账号
        $customer_account = M("customer_account")
            ->where(['customer_id' => $customer_id])
            ->order("date_add desc")
            ->field("account_id,pay_account,pay_account_name")
            ->find();
        $content["customer_account"] = $customer_account;
        $content["serviceFee_ratio"] = $serviceFee_ratio;
        $content["withdraw_introduce"] = $withdraw_introduce;
        return ['errcode' => 0, 'message' => '请求成功', 'content' => $content];

    }


    /**
     * 获取付款账号列表
     */
    public function getPayAccount(){
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }

        $page = (int)I("page");
        if($page <= 0){
            $page = 1;
        }

        $account = M("customer_account")
            ->where(['customer_id' => $customer_id])
            ->limit(10)
            ->page($page)
            ->order("date_add DESC")
            ->select();
        return ['errcode' => 0, 'message' => '请求成功', 'content' => $account];
    }
    /**
     * 添加/修改付款账号
     */
    public function savePayAccount(){
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }

        !($pay_account = I("pay_account"));
        if(empty($pay_account)){
            return ['errcode' => -102 , 'message' => '请输入支付宝账号'];
        }
        !($pay_account_name = I("pay_account_name"));
        if(empty($pay_account_name)){
            return ['errcode' => -103 , 'message' => '请输入支付宝姓名'];
        }

        $account_id = I('account_id');

        $account_info = M("customer_account")
            ->where(['account_id' => $account_id])
            ->find();

        $account = ['customer_id' => $customer_id, 'pay_account' => $pay_account, 'pay_account_name' => $pay_account_name];

        // 添加或更新支付账户信息
        if(empty($account_info)){
            $account['date_add'] = time();
            $new_account_id = M("customer_account")
                ->add($account);
            $account["account_id"] = $new_account_id;
            return ['errcode' => 0 , 'message' =>'保存成功', 'content' => $account];
        }else {
            M("customer_account")
                ->where(array('account_id' => $account_id))
                ->save($account);
            $account["account_id"] = $account_id;
            return ['errcode' => 0 , 'message' =>'更新成功', 'content' => $account];
        }
    }
    /**
     * 删除付款账号
     */
    public function delPayAccount(){
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }

        $account_id = I("account_id");

        !($account_id = I("account_id"));
        if(empty($account_id)){
            return ['errcode' => -102 , 'message' => '未传account_id'];
        }

        $account_info = M("customer_account")
            ->where(['account_id' => $account_id])
            ->delete();

        if (empty($account_info)){
            return ['errcode' => -103 , 'message' =>'删除失败'];
        }
        return ['errcode' => 0 , 'message' =>'删除成功'];

    }

    /**
    *是否实名认证
    */
    public function check_real_customer(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $goods_id = I("goods_id");
        if (empty($goods_id)){
            return ['errcode' => -102,'message' => '请传入商品id'];
        }

        $real_customer = M("real_customer")->where(["customer_id" => $customer_id,'goods_id' => $goods_id])->field("*")->find();
        if (empty($real_customer)) {
            return ['errcode' => -103,'message' => '未进行实名认证'];
        }
        return ['errcode' => 0,'message' => '实名认证通过'];
    }

    /**
    *实名认证
    */
    public function authentication(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }

        $name = I("name");
        $phone = I("phone");
        $trade_num = I("trade_num");
        $address = I("address");
        $goods_id = I("goods_id");
        $arr = array('name' => $name,"phone" => $phone,"trade_num" => $trade_num,"address" => $address,'goods_id' => $goods_id );
        foreach ($arr as $key => $value) {
            if (empty($value)) {
                return ['errcode' => -101,'message' => '参数:'.$key."为空"];
            }
        }
        if ($trade_num != "无") {
            if (!preg_match("/079/", substr($trade_num,0,3)) || strlen($trade_num) != 12) {
            return ['errcode' => -102,'message' => '交易商账号格式错误'];
            }
        }
        
        $arr['customer_id'] = $customer_id;
        $res = M("real_customer")->add($arr);
        if ($res === false) {
            return ['errcode' => -201,'message' => '认证失败'];
        }

        return ['errcode'=> 0,'message' => '认证成功'];
    }

    /**
    *分享
    */
    public function share(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99,'message' => '请重新登录'];
        }
        $customer = M("customer")->where(['customer_id' => $customer_id])->find();
        $data = array('title' => '帅柏商城','content'=>'来买吧','url' => 'http://shuaiboweixin.zertone1.com/register.html','user' => $customer_id,'cover' => $customer['avater']);
        return ['errcode' => 0,'message' => '请求成功','content' => $data];
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
            return ['errcode' => 99,'message' => '请重新登录'];
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
            return ['errcode' => 99,'message' => '请重新登录'];
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

    /**
    *余额宝
    */
    public function rewardAmount(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99,'message' => '请重新登录'];
        }

        $reward = D("customer_model")->myFind(['customer_id' => $customer_id],"reward_amount,transfer_amount,is_frozen");
        if ($reward['is_frozen'] == 1){
            $reward['frozen_amount'] = $reward['reward_amount'] + $reward['transfer_amount'];
        }else{
            $reward['frozen_amount'] = 0;
        }
        unset($reward['is_frozen']);

        $page = I("page");
        if (empty($page)){
            $page = 1;
        }
        $detail = D("user_model")->yuebaoDetail($customer_id,$page);
        $reward['detail'] = $detail['record'];

        return ['errcode' => 0,'message' => '请求成功','content' => $reward];
    }

    /**
    *余额宝充值
    */
    public function yuebaoCharge(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99,'message' => '请重新登录'];
        }

        $pay_type = I("pay_type");
        $amount = I("amount");

        if (in_array($pay_type,[1,6,7])) {
            return ['errcode' => -201,'message' => '不能使用该支付方式'];
        }
        $order = \app\library\order\OrderHelper::getInstance($pay_type);
        $order_sn = createNo("order_info","order_sn","PO");
        $order->setOrderType(6)
        ->setOrderNumber($order_sn)
        ->setSubject("余额宝充值"+$amount)
        ->setBody("余额宝充值"+$amount);

        $return = $order->get_init_order($customer_id,$amount);

        M("order_info")->add($order->getOrder());

        return ['errcode' => 0,'message' => '请求成功','content' => $order->getReturn()];
    }

    /**
    *余额宝转账
    **/
    public function yuebaoTransfer(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99,'message' => '请重新登录'];
        }

        $amount = I("amount");
        $user = I("user");
        if ($amount <= 0) {
            return ['errcode' => -201,'message' => '金额不正确'];
        }

        //查看转账人
        $field = verifyParam($user);

        $transfer_id = M("customer")->where([$field => $user])->getField("customer_id");
        if (empty($transfer_id)) {
            return ['errcode' => -202,'message' => '转账人不存在'];
        }

        return D("user_model")->transfer($customer_id,$amount,$transfer_id,$user);
    }

    /**
    *验证转账密码
    **/
    public function verifyTransfer(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99,'message' => '请重新登录'];
        }

        $transfer_pass = I("transfer_pass");

        $verify_pass = M("customer")->where(['customer_id' => $customer_id])->getField("transfer_passwd");
        if ($transfer_pass != $verify_pass) {
            return ['errcode' => -201,'message' => '密码错误'];
        }
        return ['errcode' => 0,'message' => '验证成功'];
    }

    /**
    *验证支付密码
    **/
    public function verifyPay(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99,'message' => '请重新登录'];
        }

        $pay_pass = I("pay_pass");

        $verify_pass = M("customer")->where(['customer_id' => $customer_id])->getField("pay_passwd");
        if ($pay_pass != $verify_pass) {
            return ['errcode' => -201,'message' => '密码错误'];
        }

        return ['errcode' => 0,'message' => '验证成功'];
    }

    /**
    *余额宝提现
    **/
    public function yuebaoWithdraw(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99,'message' => '请重新登录'];
        }
        
        $account = I("account");
        $amount = I("amount");
        $realname = I("realname");
        $type = I("type");
        $subbranch = I("subbranch");
        $invoice = I("invoice");
        return D("user_model")->yuebaoWithdraw($customer_id,$account,$amount,$realname,$type,$subbranch,$invoice);
    }

    /**
    *转账明细
    **/
    public function transferRecord(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99,'message' => '请重新登录'];
        }
        $transfer_amount = M("customer")->where(['customer_id' => $customer_id])->getField("transfer_amount");
        $page = I("page");
        if (empty($page)){
            $page = 1;
        }

        $record = D("user_model")->transferRecord($customer_id,$page);

        return ['errcode' => 0,'message' => '请求成功','content' => ['transfer_amount' => $transfer_amount,'detail' => $record['record']]];
    }

    /**
    *鸿府积分明细
    **/
    public function hongfuDetail(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99,'message' => '请重新登录'];
        }

        $hongfu = M("customer")->where(['customer_id' => $customer_id])->getField("hongfu");
        $page = I("page");
        if (empty($page)){
            $page = 1;
        }

        $detail = D("user_model")->hongfuDetail($customer_id,$page);

        return ['errcode' => 0,'message' => '请求成功','content' => ['hongfu' => $hongfu,'detail' => $detail['record']]];
    }

    /**
     * 手续费比例
     */
    public function withdrawServiceFee(){
        $service_fee = \app\library\SettingHelper::get("shuaibo_withdraw_fee",0.1);

        return ['errcode' => 0,'message' => '请求成功','content' => $service_fee];
    }

    /**
     * 余额宝明细
     */
    public function yuebaoDetail(){
        $customer_id = get_customer_id();
        if(empty($customer_id)){
            return ['errcode' => 99,'message' => '请重新登录'];
        }

        $page = I("page");
        if (empty($page)){
            $page = 1;
        }
        $detail = D("user_model")->singelYueDetail($customer_id,$page);
        return ['errcode' => 0,'message' => '请求成功','content' => ['detail' => $detail['record']]];
    }
}