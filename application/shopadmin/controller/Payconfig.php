<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
use app\library\CertHandler;
class Payconfig extends Admin
{
	public function index(){
		$this->display();
	}
	
	public function alipay_app_config(){
		if(IS_POST){
			
			!($seller_email = I("post.seller_email")) && $this->error("请传入收款账号");
			!($partner = I("post.partner")) && $this->error("请传入合作伙伴ID");
			$keys = [];
			
			foreach ($_FILES as $key => $file){
				$result = CertHandler::saveCertToPath($file['tmp_name'], $key . ".pem", "alipayApp");
				$keys[$key] = $result;
			}
			
			$data = [
					'is_open' => (int)I("post.is_open"), 
					'partner' => $partner,
					'seller_email' => $seller_email,
			];
			foreach ($keys as $k => $v){
				if(!empty($v)){
					$data[$k . "_path"] = $v[0];
					$data[$k] = $v[1];
				}
			}
			\app\library\SettingHelper::set_pay_params(2, $data);
			\app\library\SettingHelper::set_pay_params(5, $data);
			$this->success("上传成功");
		}
		
		$settings = \app\library\SettingHelper::get_pay_params(2,
				['partner' => '','seller_email' => '', 'is_open' => 0]);
		$settings['is_set_private'] = isset($settings['private_key_path']);
		$settings['is_set_public'] = isset($settings['public_key_path']);
		$settings['is_set_ali_public'] = isset($settings['ali_public_key_path']);
		$this->assign("settings", $settings);
		$this->display();
	}
	
	public function alipay_web_config(){
		if(IS_POST){
			
			!($seller_email = I("post.seller_email")) && $this->error("请传入收款账号");
			!($partner = I("post.partner")) && $this->error("请传入合作伙伴ID");
			$keys = [];
			
			foreach ($_FILES as $key => $file){
				$result = CertHandler::saveCertToPath($file['tmp_name'], $key . ".pem", "alipayWeb");
				$keys[$key] = $result;
			}
			
			
			$data = ['is_open' => (int)I("post.is_open"), 
					'partner' => $partner,
					'seller_email' => $seller_email,
			];
			
			foreach ($keys as $k => $v){
				if(!empty($v)){
					$data[$k . "_path"] =  $v[0];
					$data[$k] = $v[1];
				}
			}
			\app\library\SettingHelper::set("bear_alipay_web", $data);
			
			$this->success("上传成功");
		}
		
		$settings = \app\library\SettingHelper::get("bear_alipay_web",
				['partner' => '','seller_email' => '', 'is_open' => 0]);
		$settings['is_set_private'] = isset($settings['private_key_path']);
		$settings['is_set_public'] = isset($settings['public_key_path']);
		$settings['is_set_ali_public'] = isset($settings['ali_public_key_path']);
		
		$this->assign("settings", $settings);
		$this->display();
	}
	
	public function alipay_wap_config(){
		if(IS_POST){
			
			!($seller_email = I("post.seller_email")) && $this->error("请传入收款账号");
			!($partner = I("post.partner")) && $this->error("请传入合作伙伴ID");
			$keys = [];
			
			foreach ($_FILES as $key => $file){
				$result = CertHandler::saveCertToPath($file['tmp_name'], $key . ".pem", "alipayWap");
				$keys[$key] = $result;
			}
			
			
			$data = ['is_open' => (int)I("post.is_open"), 
					'partner' => $partner,
					'seller_email' => $seller_email,
			];
			
			foreach ($keys as $k => $v){
				if(!empty($v)){
					$data[$k . "_path"] = $v[0];
					$data[$k] = $v[1];
				}
			}
			\app\library\SettingHelper::set("bear_alipay_wap", $data);
			
			$this->success("上传成功");
		}
		
		$settings = \app\library\SettingHelper::get("bear_alipay_wap",
				['partner' => '','seller_email' => '', 'is_open' => 0]);
		$settings['is_set_private'] = isset($settings['private_key_path']);
		$settings['is_set_public'] = isset($settings['public_key_path']);
		$settings['is_set_alipay_public'] = isset($settings['alipay_public_key_path']);
		
		$this->assign("settings", $settings);
		$this->display();
	}
	
	public function weixin_app_config(){
		if(IS_POST){
			
			!($appid = I("post.appid")) && $this->error("请传入Appid");
			!($app_secret = I("post.app_secret")) && $this->error("请传入appsecret");
			$mch_id = I("post.mch_id");
			$api_key = I("post.api_key");
			$keys = [];
			
			foreach ($_FILES as $key => $file){
				$suffix = ".pem";
				if($key == "client_p12_path"){
					$suffix = ".p12";
				}
				$result = CertHandler::saveCertToPath($file['tmp_name'], $key . $suffix, "weixinApp", false);
				$keys[$key] = $result;
			}
			
			
			$data = [
					'is_open' => (int)I("post.is_open"), 
					'appid' => $appid,
					'app_secret' => $app_secret,
					'api_key' => $api_key,
					'mch_id' => $mch_id,
			];
			foreach ($keys as $k => $v){
				if(!empty($v)){
					$data[$k . "_path"] =  $v[0];
					$data[$k] = $v[1];
				}
			}
			\app\library\SettingHelper::set_pay_params(3, $data);
			$this->success("上传成功");
		}
		
		$settings = \app\library\SettingHelper::get_pay_params(3,
				['appid' => '','app_secret' => '','api_key' => '','mch_id' => '', 'is_open' => 0]);
		$settings['is_set_client_p12'] = isset($settings['client_p12_path']);
		$settings['is_set_client_pem'] = isset($settings['client_pem_path']);
		$settings['is_set_client_key'] = isset($settings['client_key_path']);
		$settings['is_set_root_ca'] = isset($settings['root_ca_path']);
		$this->assign("settings", $settings);
		$this->display();
	}
	
	public function weixin_gz_config(){
		if(IS_POST){
			!($appid = I("post.appid")) && $this->error("请传入Appid");
			!($app_secret = I("post.app_secret")) && $this->error("请传入appsecret");
			$mch_id = I("post.mch_id");
			$api_key = I("post.api_key");
			$keys = [];
				
			foreach ($_FILES as $key => $file){
				$suffix = ".pem";
				if($key == "client_p12_path"){
					$suffix = ".p12";
				}
				$result = CertHandler::saveCertToPath($file['tmp_name'], $key . $suffix, "weixinGz", false);
				$keys[$key] = $result;
			}
				
				
			$data = [
					'is_open' => (int)I("post.is_open"),
					'appid' => $appid,
					'app_secret' => $app_secret,
					'api_key' => $api_key,
					'mch_id' => $mch_id,
			];
			M("pay_type")-> where(['pay_id']);
			foreach ($keys as $k => $v){
				if(!empty($v)){
					$data[$k . "_path"] = $v[0];
					$data[$k] = $v[1];
				}
			}
			\app\library\SettingHelper::set_pay_params(4, $data);
			$this->success("上传成功");
		}
	
		$settings = \app\library\SettingHelper::get_pay_params(4,
				['appid' => '','app_secret' => '','api_key' => '','mch_id' => '', 'is_open' => 0]);
		$settings['is_set_client_p12'] = isset($settings['client_p12_path']);
		$settings['is_set_client_pem'] = isset($settings['client_pem_path']);
		$settings['is_set_client_key'] = isset($settings['client_key_path']);
		$settings['is_set_root_ca'] = isset($settings['root_ca_path']);
		$this->assign("settings", $settings);
		$this->display();
	}
}
