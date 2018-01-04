<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
use app\shopadmin\library\SettingHelper;
class Setting extends Admin
{
	public function web()
	{
		$config = get_admin_config();
		$db = get_admin_config('db');
		$web['site_name'] = isset($config['site_name']) ? $config['site_name'] : '';	
		$web['site_host'] = isset($config['site_host']) ? $config['site_host'] : '';
		$web['site_icp'] = isset($config['site_icp']) ? $config['site_icp'] : '';
		$web['site_copyright'] = isset($config['site_copyright']) ? $config['site_copyright'] : '';

		$this->assign('web',$web);
		$this->assign('db',$db['database']);
		$this->display();
	}

	public function config()
	{
		$web = I('post.web/a');
		$db = I('post.db/a');
		$data['database'] = $db;

		$web && save_admin_config($web);
		$db && save_admin_config($data,'db');	
	}

	public function editpwd(){
		$settings = M("settings")->where(['setting_key' => "pay_pwd"])->find();
		if(IS_POST){
			!I("post.old_pwd") && !empty($settings) && $this->error("请输入旧密码") ;
			!I("post.new_pwd") && $this->error("请输入新密码");
			!empty($settings) && $settings['setting_value'] != md5(I("post.old_pwd")) && $this->error("旧密码错误");
			if(empty($settings)){
				M("settings")->add(['setting_key' => 'pay_pwd', 'setting_value' => md5(I("post.new_pwd"))]);
			}else{
				M("settings")->where(['setting_key' => 'pay_pwd'])->save(['setting_value' => md5(I("post.new_pwd"))]);
			}
			$this->success("设置成功");
		}else{
			$this->assign("is_setting", !empty($settings));
			$this->display();
		}
	}
	
	
	public function savegoodsshare(){
		if(IS_POST){
			!($share_title = I("post.share_title")) && $this->error("请输入分享标题");
			!($share_desc = I("post.share_desc")) && $this->error("请输入分享文本");
			$share = ['share_title'=>$share_title, 'share_desc' => $share_desc];
			\app\library\SettingHelper::set("goods_share",$share);
			$this->success("设置成功");
		}else{
			$settings = \app\library\SettingHelper::get("goods_share", ['share_title' => '', 'share_desc'=>'']) ;
			$this->assign("settings", $settings);
			$this->display();
		}
	}
	
	public function savewxshare(){
		if(IS_POST){
			!($share_title = I("post.share_title")) && $this->error("请输入分享标题");
			!($share_desc = I("post.share_desc")) && $this->error("请输入分享文本");
			!($share_img = I("post.share_image")) && $this->error("请传分享图片");
			$share = ['share_title'=>$share_title, 'share_desc' => $share_desc,'share_image' => $share_img];
			
			\app\library\SettingHelper::set("wx_share", $share);
			$this->success("设置成功");
		}else{
			$settings = \app\library\SettingHelper::get("wx_share", ['share_title' => '', 'share_desc'=>'' , 'share_image' => '']);
			$this->assign("settings", $settings);
			$this->display();
		}
	}
	public function message_template(){
		if(IS_POST){
			$send_message = I("send_message");
			$send_virtual_message = I("send_virtual_message");
			$win_message = I("win_message");
			$bind_message = I("bind_message");
			$find_message = I("find_message");
			$reg_message = I("reg_message");
			$sign_position = I("sign_position");
			$sms_message = I("sms_message");
			$withdraw_message = I("withdraw_message");
			$validate = (int)I("validate");
			if($validate <= 0){
				$this->error("过期时间必须大于0");
			}
			$sign = I("sign");
			\app\library\SettingHelper::set("bear_message_sign_position", $sign_position);
			\app\library\SettingHelper::set("bear_message_sign", $sign);
			\app\library\SettingHelper::set("bear_bind_message", $bind_message);
			\app\library\SettingHelper::set("bear_find_message", $find_message);
			\app\library\SettingHelper::set("bear_reg_message", $reg_message);
			\app\library\SettingHelper::set("bear_send_message", $send_message);
			\app\library\SettingHelper::set("bear_send_virtual_message", $send_virtual_message);
			\app\library\SettingHelper::set("bear_win_message", $win_message);
			\app\library\SettingHelper::set("bear_sms_message", $sms_message);
			\app\library\SettingHelper::set("bear_sms_validate", $validate);
			\app\library\SettingHelper::set("bear_withdraw_tips", $withdraw_message);
			$this->success("修改成功");
		}
		$sign_position = \app\library\SettingHelper::get("bear_message_sign_position","0");
		$sign = \app\library\SettingHelper::get("bear_message_sign","天天购");
		$validate = \app\library\SettingHelper::get("bear_sms_validate", "10");
		$bind_message = \app\library\SettingHelper::get("bear_bind_message", "您正在绑定手机，验证码为%code，请在10分钟内完成操作");
		$find_message = \app\library\SettingHelper::get("bear_find_message", "您正在找回密码，验证码为%code，请在10分钟内完成操作");
		$sms_message = \app\library\SettingHelper::get("bear_sms_message", "您正在使用短信登录，验证码为%code，请在10分钟内完成操作");
		$reg_message = \app\library\SettingHelper::get("bear_reg_message" , "您正在注册账号，验证码为%code，请在10分钟内完成操作");
		$send_message = \app\library\SettingHelper::get("bear_send_message", "您的奖品已发送，请注意查收");
		$send_virtual_message = \app\library\SettingHelper::get("bear_send_virtual_message", "您的奖品已发送，请注意查收");
		$win_message = \app\library\SettingHelper::get("bear_win_message", "恭喜您中奖啦，赶紧去领奖吧");
		$withdraw_message = \app\library\SettingHelper::get("bear_withdraw_tips");
		$this->assign("send_message", $send_message);
		$this->assign("win_message", $win_message);
		$this->assign("bind_message", $bind_message);
		$this->assign("reg_message", $reg_message);
		$this->assign("find_message", $find_message);
		$this->assign("sms_message" , $sms_message);
		$this->assign("withdraw_message", $withdraw_message);
		$this->assign("send_virtual_message", $send_virtual_message);
		$this->assign("validate", $validate);
		$this->assign("sign", $sign);
		$this->assign("sign_position", $sign_position);
		$this->display();
	}
	
	public function agreement_setting(){
		if(IS_POST){
			$agreement = htmlspecialchars(I('post.editorValue'), ENT_QUOTES);
			\app\library\SettingHelper::set("bear_agreement", $agreement);
			$this->success("修改成功");
		}
		$agreement = htmlspecialchars_decode(\app\library\SettingHelper::get("bear_agreement"), ENT_QUOTES) ;
		$this->assign("agreement", $agreement);
		$this->display();
	}
	
	public function about_us(){
		if(IS_POST){
			$about_us = htmlspecialchars(I('post.editorValue'), ENT_QUOTES);
			\app\library\SettingHelper::set("bear_about_us", $about_us);
			$this->success("修改成功");
		}
		$about_us = htmlspecialchars_decode(\app\library\SettingHelper::get("bear_about_us"), ENT_QUOTES) ;
		$this->assign("about_us", $about_us);
		$this->display();
	}
	
	public function rule(){
		if(IS_POST){
			$rule = htmlspecialchars(I('post.editorValue'),ENT_QUOTES);
			\app\library\SettingHelper::set("bear_rule",$rule);
			$this->success("修改成功");
		}
		$rule = htmlspecialchars_decode(\app\library\SettingHelper::get("bear_rule"), ENT_QUOTES);
		$this->assign("rule",$rule);
		$this->display();
	}
	
	public function promo_code_rule(){
		if(IS_POST){
			$promo_code_rule = htmlspecialchars(I('post.editorValue'),ENT_QUOTES);
			\app\library\SettingHelper::set("bear_promo_code_rule",$promo_code_rule);
			$this->success("修改成功");
		}
		$promo_code_rule = htmlspecialchars_decode(\app\library\SettingHelper::get("bear_promo_code_rule"), ENT_QUOTES);
		$this->assign("promo_code_rule",$promo_code_rule);
		$this->display();
	}
	
	public function qiniu(){
		if(IS_POST){
			$is_open = I("is_open");
			$secretKey = I("secretKey");
			$accessKey = I("accessKey");
			$domain = I("domain");
			$bucket = I("bucket");
			$site = I("site");
			if(!empty($is_open)){
				!$secretKey && $this->error("请传入secretKey");
				!$accessKey && $this->error("请传入accessKey");
				!$domain && $this->error("请传入domain");
				!$bucket && $this->error("请传入bucket");
			}
			
			$data = ['is_open' => $is_open, 
			'maxSize'=>5*1024*1024,//文件大小
			'rootPath'=>'./',
			'saveName'=>array('uniqid',''),
			'driver'=>'Qiniu',
			'driverConfig'=>array(
					'secretKey'=>$secretKey,
					'accessKey'=>$accessKey,
					'domain'=>$domain,
					'bucket'=>$bucket,
			)];
			\app\library\SettingHelper::set("bear_qiniu", $data);
			\app\library\SettingHelper::set("bear_image_url", "http://" . $domain . "/");
			$this->success("操作成功");
		}
		
		$settings =  \app\library\SettingHelper::get("bear_qiniu", 
				['is_open' => 0, 
			'maxSize'=>5*1024*1024,//文件大小
			'rootPath'=>'./',
			'saveName'=>array('uniqid',''),
			'driver'=>'Qiniu',
			'driverConfig'=>array(
					'secretKey'=>'',
					'accessKey'=>'',
					'domain'=>'',
					'bucket'=>'',
			)]);
		$this->assign("settings", $settings);
		$this->display();
	}
	
	public function website_setting(){
		if(IS_POST){
			!($title = I('post.title')) && $this->error("请输入网站标题");
			!($image_url = I('post.image_url')) && $this->error("请输入图片域名");
			!($mobile_url = I('post.mobile_url')) && $this->error("请输入手机网站地址");
			if(strpos($image_url, "http://") !== 0 && strpos($image_url, "https://") !== 0){
				$image_url = "http://".$image_url;
			}
			if(strpos($mobile_url, "http://") !== 0 && strpos($mobile_url, "https://") !== 0){
				$mobile_url = "http://".$mobile_url;
			}
			\app\library\SettingHelper::set("bear_image_url", $image_url);
			\app\library\SettingHelper::set("bear_mobile_url", $mobile_url);
			\app\library\SettingHelper::set("bear_title", $title);
			$this->success("修改成功");
		}
		$title = \app\library\SettingHelper::get("bear_title", "一元夺宝");
		$mobile_url = SettingHelper::get("bear_mobile_url");
		$image_url = SettingHelper::get("bear_image_url");
		$this->assign("title", $title);
		$this->assign("mobile_url", $mobile_url);
		$this->assign("image_url", $image_url);
		$this->display();
	}
	
	public function lottery(){
		if(IS_POST){
			!($name = I("post.name")) && $this->error("请输入彩票名");
			$is_open = I("post.is_open");
			!($uid = I("post.uid")) && $this->error("请输入uid");
			!($token = I("post.token") ) && $this->error("请输入token");
			
			$data = ['is_open' => $is_open,'name' => $name ,'uid' => $uid, 'token' => $token];
			\app\library\SettingHelper::set("bear_lottery", $data);
			$this->success("操作成功");
		}
		
		$settings = \app\library\SettingHelper::get("bear_lottery", ['is_open' => 0, 'name' => 'cqssc', 'uid' => '464665', 'token' => 'a4cff42c918b4be5a7ec3c92b993be3d19db562b']);
		$this->assign("lottery", $settings);
		$this->display();
	}
	
	public function test_setting(){
		if(IS_POST){
			$audit_test = (int)I("audit_test");
			$money_test = (int)I("money_test");
			\app\library\SettingHelper::set("bear_money_test", $money_test);
			\app\library\SettingHelper::set("bear_audit_test", $audit_test);
			$this->success("请求成功");
		}
		$money_test = \app\library\SettingHelper::get("bear_money_test", 0);
		$audit_test = \app\library\SettingHelper::get("bear_audit_test", 0);
		$this->assign("money_test", $money_test);
		$this->assign("audit_test", $audit_test);
		$this->display();
	}
	public function phone_setting(){
        if(IS_POST){
           !( $phone = I("phone")) && $this->error("请输入电话");
           !( $address = I("address")) && $this->error("请输入地址");
           $info = ['address' => $address,'qq' => $phone];
          	\app\library\SettingHelper::set("shuaibo_seller_info", $info);
           $this->success("操作成功");
        }
        $seller_info = \app\library\SettingHelper::get("shuaibo_seller_info",['address' => '杭州市' ,'qq' => '123456789']);
		$this->assign("seller_info",$seller_info);
		$this->display();
	}
	
	public function register_money(){
		if(IS_POST){
			$money = (int)I("money");
			$is_open = (int)I("is_open");
			\app\library\SettingHelper::set("bear_register_money", ['is_open' => $is_open, 'money' =>$money]);
			$this->success("操作成功");
		}
		$money = \app\library\SettingHelper::get("bear_register_money", ['is_open' => 0, 'money' => 0]);
		$this->assign("money", $money);
		$this->display();
	}
	
	public function qrcode(){
		if(IS_POST){
			$position = I("position");
			
			if(empty($position)){
				$this->error("请传入位置信息");
			}
			
			$position = json_decode($position, true);
			$position['number'] = rand(0, 999). time();
			foreach ($position as &$p){
				if(is_array($p)){
					if(abs($p['w'] / 2 + $p['x'] - 50 ) < 5 ){
						$p['center'] = 1;
					}else{
						$p['center'] = 0;
					}
				}
			}
			\app\library\SettingHelper::set("bear_qrcode_position", $position);
			$this->success("成功");
		}
		$position = \app\library\SettingHelper::get("bear_qrcode_position",
				[
						'name' => ['x'=> 50,'y' => 24 , 'w' => 23 ,'h' => 50, 'center' => 0],
						'image' => ['x'=> 24,'y' => 24 , 'w' => 18 ,'h' => 97, 'center' => 0],
						'qrcode' => ['x'=> 27,'y' => 39 , 'w' => 50 ,'h' => 100, 'center' => 1],
						'background' => ""
				]);
		$this->assign("position", $position);
		$this->display();
	}
	
	public function jpush_setting(){
		if(IS_POST){
			$is_open = (int)I("is_open");
			$app_key = I("app_key");
			$app_secret = I("app_secret");
			if(empty($app_key)){
				$this->error("请输入app_key");
			}
			if(empty($app_secret)){
				$this->error("请输入app_secret");
			}
			\app\library\SettingHelper::set("bear_jpush", ['is_open' => $is_open,'app_key' => $app_key,'app_secret' => $app_secret]);
			$this->success("保存成功");
		}
		
		$jpush = \app\library\SettingHelper::get("bear_jpush",['is_open' => 0,'app_key'=> '','app_secret' => '']);
		$this->assign("jpush", $jpush);
		$this->display();
	}
	
	public function site_config(){
		if(IS_POST){
			$is_open = (int)I("is_open");
			$reason = I("reason");
			if($is_open == 0 && !$reason){
				$this->error("请输入关闭原因");
			}
			\app\library\SettingHelper::set("bear_close", ['is_open' => $is_open , 'reason' => $reason]);
			$this->success("操作成功");
		}
		$settings = \app\library\SettingHelper::get("bear_close", ['is_open' => 1, 'reason' => '']);
		$this->assign("settings", $settings);
		$this->display();
	}
	
	public function start_page(){
		if(IS_POST){
			$start_page = I("start_page");
			$is_open = I("is_open");
			\app\library\SettingHelper::set("bear_start_page", ['start_page' => $start_page, 'is_open'=> $is_open]);
			$this->success("操作成功");
		}
		$start_page = \app\library\SettingHelper::get("bear_start_page", ['is_open' => 0 , 'start_page' => '']);
		$this->assign("settings" , $start_page);
		$this->display();
	}
	public function home_video(){
		if(IS_POST){
			$video = I("video");
			
			if(empty($video)){
				$this->error("请传入视频链接");
			}
			
			\app\library\SettingHelper::set("bear_home_video",$video);
			$this->success("修改成功");
		}
		
		$video = \app\library\SettingHelper::get("bear_home_video");
		
		$this->assign("video", $video);
		$this->display();
	}

	//系统参数
	public function websys(){
		if(IS_POST){
			!($webtitle = I("post.webtitle")) && $this->error("请输入标题");
			!($keywords = I("post.keywords")) && $this->error("请输入关键字");
			!($description = I("post.description")) && $this->error("请输入站点描述");
			!($copyright = I("post.copyright")) && $this->error("请输入版权信息");
			!($record = I("post.record")) && $this->error("请输入备案号");

			\app\library\SettingHelper::set("bear_webtitle", $webtitle);
			\app\library\SettingHelper::set("bear_keywords", $keywords);
			\app\library\SettingHelper::set("bear_description", $description);
			\app\library\SettingHelper::set("bear_copyright", $copyright);
			\app\library\SettingHelper::set("bear_record", $record);
			
			$this->success("设置成功");
		}
		$webtitle = \app\library\SettingHelper::get("bear_webtitle", '聚美云合');
		$keywords = \app\library\SettingHelper::get("bear_keywords");
		$description = \app\library\SettingHelper::get("bear_description");
		$copyright = \app\library\SettingHelper::get("bear_copyright");
		$record = \app\library\SettingHelper::get("bear_record");
		$this->assign("webtitle", $webtitle);
		$this->assign("keywords", $keywords);
		$this->assign("description", $description);
		$this->assign("copyright", $copyright);
		$this->assign("record", $record);
		$this->display();		
	}	
	
}