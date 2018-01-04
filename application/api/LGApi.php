<?php
namespace app\api;

class LGApi{
	public function __construct(){
	}

    /**
     * 充值
     * @param $data
     * @return array
     */
//	public function recharge($data) {
//        $check = self::checkBeforeRecharge($data);
//        if ($check['errcode'] < 0) {
//            return $check;
//        }
//        $value = ['integration' => ['exp','integration + ' . $data['integration']], 'shopping_coin' => ['exp','shopping_coin + ' . $data['shopping_coin']]];
//        M('customer')->where(['uuid' => $data['userid']])->save($value);
//        return ['errcode' => 0, 'message' => '充值成功'];
//    }
	
	//推荐与安置
	public function CheckTuiJianAndAnZhi($data){
		$check = self::checkBeforeTuiJian($data);
		if($check['errcode'] < 0){
			return $check;
		}
		
		$sign = self::sign($data['userid']);
		$request['method_name'] = "Check_TuiJianAndAnZhi";
        $request['userid'] = $data['userid'];
        $request['tuijian'] = $data['tuijian'];
		$request['sign'] = strtoupper($sign);
		$request['anzhi'] = isset($data['anzhi'])?$data['anzhi']:"";

		$json = self::toJson($request);		
		$url = \app\library\SettingHelper::get("shuaibo_lg_url","http://www.power139.com/LGService.aspx");
		return httpPostData($url,$json);
	}

	// 修改用户密码
    public function EditUpdatePass($data) {
        $check = self::checkBeforeUpdatePass($data);
        if($check['errcode'] < 0){
            return $check;
        }
        $sign = self::sign($data['userid']);
        $data['method_name'] = "Edit_UpdatePass";
        $data['sign'] = strtoupper($sign);
        $json = self::toJson($data);
        $url = \app\library\SettingHelper::get("shuaibo_lg_url","http://www.power139.com/LGService.aspx");
        return httpPostData($url, $json);
    }

    // 用户登录
    public function SearchLogin($data) {
        $check = self::checkBeforeLogin($data);
        if($check['errcode'] < 0){
            return $check;
        }
        $sign = self::sign($data['userid']);
        $data['method_name'] = "Search_Login";
        $data['sign'] = strtoupper($sign);
        $json = self::toJson($data);
        $url = \app\library\SettingHelper::get("shuaibo_lg_url","http://www.power139.com/LGService.aspx");
        return httpPostData($url, $json);
    }
	
	//用户注册
	public function AddMemberInfo($data){
		$check = self::checkBeforeTuiJian($data);
		if ($check['errcode'] < 0){
			return $check;
		}
		$sign = self::sign($data['userid']);
		$data['method_name'] = "Add_MemberInfo";
		$data['registerdate'] = date("Y-m-d H:i:s");
        $data['anzhi'] = isset($data['anzhi'])?$data['anzhi']:$data['tuijian'];
        $data['sign'] = strtoupper($sign);
        $json = self::toJson($data);
		$url = \app\library\SettingHelper::get("shuaibo_lg_url","http://www.power139.com/LGService.aspx");
		return httpPostData($url, $json);
	}

	// 商城购物
	public function AddOrderInfo($data) {
        $check = self::checkBeforeOrder($data);
        if ($check['errcode'] < 0){
            return $check;
        }
        $sign = self::sign($data['userid']);
        $data['method_name'] = "Add_OrderInfo";
        $data['sign'] = strtoupper($sign);
        $json = self::toJson($data);
        $url = \app\library\SettingHelper::get("shuaibo_lg_url","http://www.power139.com/LGService.aspx");
        return httpPostData($url, $json);
    }

    public function getUsername($username) {
        if( substr($username,-2) !="lg" || strlen($username) != 10) {
            return false;
        }
        return substr($username,0,8);
    }
	
	private function sign($username){
		return md5($username."LGService_2016");
	}
	
	private function toJson($data){
		$i=1;
		$count = count($data);
		$json_str='';
		foreach($data as $key=>$value){
	
			if($i<$count){
				$json_str.='"'.$key.'":"'.$value.'",';
			}else{
				$json_str.='"'.$key.'":"'.$value.'"';
			}
			$i++;
		}
		$json_str = '[{'.$json_str.'}]';
	
		return $json_str;
	}

//	private function checkBeforeRecharge($data) {
//        if (!isset($data['method_name'])){
//            return ['errcode' => -101, 'message' => '请传入方法名'];
//        }
//        if ($data['method_name'] != 'recharge'){
//            return ['errcode' => -102, 'message' => '方法不正确'];
//        }
//        if (!isset($data['userid'])){
//            return ['errcode' => -103, 'message' => '请传入用户名'];
//        }
//        if ($data['sign'] != self::sign($data['userid'])) {
//            return ['errcode' => -104, 'message' => '验证错误'];
//        }
//        if ($data['integration'] < 0) {
//            return ['errcode' => -105, 'message' => '积分不能小于0'];
//        }
//        if ($data['shopping_coin'] < 0) {
//            return ['errcode' => -106, 'message' => '会员积分不能小于0'];
//        }
//        return ['errcode' => 0, 'message' => '验证通过'];
//    }
	
	private function checkBeforeTuiJian($data){
		if (!isset($data['userid'])){
			return ['errcode' => -101, 'message' => '请传入用户名'];
		}
		if(!isset($data['tuijian'])){
			return ['errcode' => -101, 'message' => '请传入推荐编号'];
		}
		return ['errcode' => 0, 'message' => '验证通过'];
	}
	
	private function checkBeforeRegister($data){
		if (!isset($data['userid'])){
			return ['errcode' => -101, 'message' => '请传入用户名'];
		}
		if(!isset($data['tuijian'])){
			return ['errcode' => -101, 'message' => '请传入推荐编号'];
		}
		if (!isset($data['name'])){
			return ['errcode' => -101, 'message' => '请传入姓名'];
		}
		if(!isset($data['sex'])){
			return ['errcode' => -101, 'message' => '请传入性别'];
		}
		if(!isset($data['birthday'])){
			return ['errcode' => -101, 'message' => '请传入生日'];
		}
		if(!isset($data['petname'])){
			return ['errcode' => -101, 'message' => '请传入昵称'];
		}
		if(!isset($data['papernumber'])){
			return ['errcode' => -101, 'message' => '请传入身份证号码'];
		}
		if(!isset($data['country'])){
			return ['errcode' => -101, 'message' => '请传入国家'];
		}
		if(!isset($data['province'])){
			return ['errcode' => -101, 'message' => '请传入省份'];
		}
		if(!isset($data['city'])){
			return ['errcode' => -101, 'message' => '请传入城市'];
		}
		if(!isset($data['xian'])){
			return ['errcode' => -101, 'message' => '请传入县'];
		}
		if(!isset($data['address'])){
			return ['errcode' => -101, 'message' => '清传入详细地址'];
		}
		return ['errcode' => 0, 'message' => '验证成功'];
	}

    private function checkBeforeOrder($data){
        if (!isset($data['userid'])){
            return ['errcode' => -101, 'message' => '请传入用户名'];
        }
        if(!isset($data['orderid'])){
            return ['errcode' => -101, 'message' => '请传入订单号'];
        }
        if(!isset($data['ordermoney'])){
            return ['errcode' => -101, 'message' => '请传入订单金额'];
        }
        if(!isset($data['orderpv'])){
            return ['errcode' => -101, 'message' => '请传入订单积分'];
        }
        if(!isset($data['orderdate'])){
            return ['errcode' => -101, 'message' => '请传入支付时间'];
        }
        if (!isset($data['name'])){
            return ['errcode' => -101, 'message' => '请传入收货人'];
        }
        if(!isset($data['mobiletele'])){
            return ['errcode' => -101, 'message' => '请传入收货手机号码'];
        }
        if(!isset($data['country'])){
            return ['errcode' => -101, 'message' => '请传入国家'];
        }
        if(!isset($data['province'])){
            return ['errcode' => -101, 'message' => '请传入省份'];
        }
        if(!isset($data['city'])){
            return ['errcode' => -101, 'message' => '请传入城市'];
        }
        if(!isset($data['xian'])){
            return ['errcode' => -101, 'message' => '请传入县'];
        }
        if(!isset($data['address'])){
            return ['errcode' => -101, 'message' => '清传入详细地址'];
        }
        return ['errcode' => 0, 'message' => '验证成功'];
    }

    private function checkBeforeUpdatePass($data){
        if (!isset($data['userid'])){
            return ['errcode' => -101, 'message' => '请传入会员编号'];
        }
        if(!isset($data['userpass'])){
            return ['errcode' => -101, 'message' => '请传入会员密码'];
        }
        if(!isset($data['passtype'])){
            return ['errcode' => -101, 'message' => '请传入密码类型'];
        }
        return ['errcode' => 0, 'message' => '验证通过'];
    }

    private function checkBeforeLogin($data){
        if (!isset($data['userid'])){
            return ['errcode' => -101, 'message' => '请传入会员编号'];
        }
        if(!isset($data['userpass'])){
            return ['errcode' => -101, 'message' => '请传入会员密码'];
        }
        return ['errcode' => 0, 'message' => '验证通过'];
    }
}