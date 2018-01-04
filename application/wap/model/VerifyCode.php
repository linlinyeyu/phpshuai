<?php
namespace app\app\model;
use think\Model;
class VerifyCode extends Model{
/**
 * 进行验证
 * @param unknown $phone
 * @param unknown $code
 * @param number $type
 */	
	public function verify($phone,$code, $type = 0){
		$res = array(
			"message" => '请求成功',
			"errcode" => 0);
		if(empty($code)){
			$res['message'] = "请传入验证码";
			$res['errcode'] = -101;
			return $res;
		}
		$condition = array('phone' => $phone, 'type' => $type);
		$verify =$this->where($condition)->find();
		if(empty($verify) || $verify['code'] != $code){
			$res['message'] = "验证码错误";
			$res['errcode'] = -408;
			return $res;
		}
		$validate = (int)\app\library\SettingHelper::get("bear_sms_validate", "10");
		if($validate <= 0){
			$validate = 10;
		}
		
		if($verify['date_add'] + 60* $validate < microtime_float() ){
			$res['message'] = "验证码已过期";
			$res['errcode'] = -409;
			return $res;
		}


		return $res;


	}
/**
 * 发送验证码
 * @param unknown $phone
 * @param number $type
 */
	public function send($phone, $type = 0){
		$verify = $this->where(array("phone"=> $phone,'type' => $type))->find();
		$code = "".rand(100000,999999);
// 判断是否验证
		if(!$verify){
			$verify = [
				'code' => $code,
				'phone' => $phone,
				'type' => $type,
				'date_add' => microtime_float()];
			$this->add($verify);
		}else{
		    // 		设置验证时间为10分钟
			$validate = (int)\app\library\SettingHelper::get("bear_sms_validate", "10");
			if($verify['date_add'] + 60* $validate > microtime_float()){
				$code = $verify['code'];
			}else{
				$verify['code'] = $code;
				$verify['date_add'] = microtime_float();
				$this->where(array("verify_id"=> $verify['verify_id']))->save($verify);
			}
		}
// 		发送短信至客户手机
		$customer = M("customer")->where(['phone' => $phone])->field("customer_id")->find();
		$comment = "发送短信至".$phone;
		if(!empty($customer)){
			D("customer_log")->Log($customer['customer_id'], 5 , $comment);
		}else{
			D("customer_log")->Log(0, 5 , $comment);
		}
		$content = "";
		if($type == 0){
			$content = \app\library\SettingHelper::get("bear_reg_message" , "您正在注册账号，验证码为%code，请在10分钟内完成操作");
		}else if($type == 1){
			$content = \app\library\SettingHelper::get("bear_find_message", "您正在找回密码，验证码为%code，请在10分钟内完成操作");
		}else if($type == 2){
			$content = \app\library\SettingHelper::get("bear_sms_message", "您正在使用短信登录，验证码为%code，请在10分钟内完成操作");
		}else if($type == 3 || $type == 4){
			$content = $bind_message = \app\library\SettingHelper::get("bear_bind_message", "您正在绑定手机，验证码为%code，请在10分钟内完成操作");
		}else{
			return 0;
		}
		$content = str_replace("%code", $code, $content);
		$content = \app\library\SmsHelper\sign($content);
// 		发送内容
		$result = \app\library\SmsHelper::send($phone,$content);
		return $code;
	}
	
}