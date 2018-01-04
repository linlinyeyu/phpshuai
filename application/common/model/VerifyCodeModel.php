<?php
namespace app\common\model;
use think\Model;
class VerifyCodeModel extends Model{
/**
 * 进行验证
 * @param unknown $phone
 * @param unknown $code
 * @param number $type
 */	
	public function verify($param,$code, $type = 0,$field = 'phone'){
        $res = array(
			"message" => '验证成功',
			"errcode" => 0);
		//如果为邮箱验证
		if ($field == 'email'){
			if(empty($param)){
				return ['errcode' => -101, 'message' => '请填入邮箱'];
			}
			if(!preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $param)){
				return ['errcode' => -101, 'message' => '邮箱格式错误'];
			}
		}
		
		//如果为手机验证
		if ($field == 'phone'){
			if(empty($param)){
				$res['message'] = "请输入手机号";
				$res['errcode'] = -105;
				return $res;
			}
			if(empty($code)){
				$res['message'] = "请输入验证码";
				$res['errcode'] = -101;
				return $res;
			}
			if(!preg_match("/^1\d{10}$/", $param)){
				$res['message'] = "手机号格式错误";
				$res['errcode'] = -402;
				return $res;
			}
		}
		
		$condition = array($field => $param, 'type' => $type, 'state' => 0);
		$verify =$this->where($condition)->find();
		if(empty($verify) || $verify['code'] != $code){
			$res['message'] = "验证码错误";
			$res['errcode'] = -408;
			return $res;
		}
		$validate = (int)\app\library\SettingHelper::get("shuaibo_sms_validate", "10");
		if($validate <= 0){
			$validate = 10;
		}

		if($verify['date_add'] + 60* $validate < microtime_float() ){
			$res['message'] = "验证码已过期";
			$res['errcode'] = -409;
			return $res;
		}
		//$this->where($condition)->save(['state' => 1]);
		return $res;
	}
/**
 * 发送验证码
 * @param unknown $phone
 * @param number $type
 */
	public function send($param, $type = 0,$field ="phone",$access_token = ''){
		$verify = $this->where(array("$field"=> $param,'type' => $type))->find();
		$code = "".rand(100000,999999);
// 判断是否验证
		if(!$verify){
			$verify = [
				'code' => $code,
				$field => $param,
				'type' => $type,
				'date_add' => microtime_float()];
			$this->add($verify);
		}else{
		    // 		设置验证时间为10分钟
			$validate = (int)\app\library\SettingHelper::get("shuaibo_sms_validate", "10");
			if($verify['date_add'] + 60* $validate > microtime_float()){
				$code = $verify['code'];
			}else{
				$verify['code'] = $code;
				$verify['date_add'] = microtime_float();
				$this->where(array("verify_id"=> $verify['verify_id']))->save($verify);
			}
		}
// 		发送短信至客户手机
		$content = "";
		if($type == 0){
			$content = \app\library\SettingHelper::get("shuaibo_reg_message" , "您正在注册账号，验证码为%code，请在10分钟内完成操作");
		}else if($type == 1){
			$content = \app\library\SettingHelper::get("shuaibo_find_message", "您正在找回密码，验证码为%code，请在10分钟内完成操作");
		}else if($type == 2){
			$content = \app\library\SettingHelper::get("shuaibo_sms_message", "您正在使用短信登录，验证码为%code，请在10分钟内完成操作");
		}else if($type == 3){
			$content = \app\library\SettingHelper::get("shuaibo_bind_message", "您正在完善信息，验证码为%code，请在10分钟内完成操作");
		}elseif ($type == 5){
			$content = \app\library\SettingHelper::get("shuaibo_bind_email_message","验证码:%code <br/><b>您正在绑定邮箱，点击以下链接或者复制验证码到页面即可验证</b><br/>".\app\library\SettingHelper::get("shuaibo_bind_url")."?verify_code=%code&access_token=$access_token");
		}else if($type == 6){
            $content = \app\library\SettingHelper::get("shuaibo_modify_password", "您正在修改密码，验证码为%code，请在10分钟内完成操作");
        }else if ($type == 7){
        	$content = \app\library\SettingHelper::get("shuaibo_unbind_email_message","验证码:%code <br/><b>您正在解绑邮箱，点击以下链接或者复制验证码到页面即可验证</b><br/>".\app\library\SettingHelper::get("shuaibo_unbind_url")."?verify_code=%code&access_token=$access_token");
        }else if ($type == 8){
            $content = \app\library\SettingHelper::get("shuaibo_bind_message", "您正在绑定手机，验证码为%code，请在10分钟内完成操作");
        }else if ($type == 9){
            $content = \app\library\SettingHelper::get("shuaibo_bind_message", "您正在更改绑定手机，验证码为%code，请在10分钟内完成操作");
        }else if ($type == 10){
            $content = \app\library\SettingHelper::get("shuaibo_modify_pay_password","您正在修改支付密码，验证码为%code，请在10分钟内完成操作");
        }else if ($type == 11){
            $content = \app\library\SettingHelper::get("shuaibo_modify_transfer_password","您正在修改转账密码，验证码为%code,请在10分钟内完成操作");
        }else{
			return 0;
		}
		$content = str_replace("%code", $code, $content);
		
		if($field == 'email'){
			think_send_mail($param, "帅柏商城",'帅柏商城验证邮件',$content);
			return $code;
		}
		//发送短信
		$helper = \app\library\SmsHelper::getInstance();
		$result = $helper->send($param, $content);
		
		return $code;
	}
	
}