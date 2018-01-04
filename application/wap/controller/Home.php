<?php
namespace app\wap\controller;
use think\Controller;
class Home extends \app\wap\BaseController
{
	public function index()
	{
		$tags = D("home_tag")->GetTags(3);
		$banners = D("banner")->GetBanners(0,3);
		$activity = D("activity")->GetActivity(3);
		$goods = D("goods")->GetHomeSecKill();	
		$cart_number = D("customer")->CartNumber($this->customer_id);
		$this->assign("goods_number", $cart_number);
		$this->assign("tags", $tags);
		$this->assign("banners",$banners);
		$this->assign("activity", $activity);
		$this->assign("goods", $goods);
		$this->display();
	}	
	
	public function qrcode(){
		$customer_id = $this->customer_id;
	
		$uuid = I("uuid");
		if(!empty($uuid)){
			$c = M("customer")->where(['uuid' => $uuid])->getField("customer_id");
			if(!empty($c)){
				$customer_id = $c;
			}
		}
		$content = D("user")->qrcode($customer_id);
		if($content['errcode'] < 0){
			$this->redirect(U("home/index"));
		}
		$host = $this->host;
		$this->assign("app_url", "http://" . get_domain() . "/wap/home/qrcode?uuid=" . $content['content']['uuid']);
		$this->assign("qrcode" , $host . $content['content']['name']);
		$this->display();
	}
	
	public function customer_detail(){
		$uuid = I("uuid");
		$customer = D("customer")->getCustomerDetail($uuid);
		
		if(is_string($customer)){
			$this->error($customer);
		}
		
		$this->assign("customer" , $customer);
	
		$this->display();
	}
	
	
	public function login(){
		if(!empty($this->customer_id)){
			$this->redirect(U("home/index"));
		}
		
		$this->display();
	}
	
	public function reg(){
		$this->display();
	}

	public function webview(){
		$title = I("title");
		$keywords = I("keywords");
		$description = I("description");
		$bear_copyright = I("bear_copyright");
		$bear_record = I("bear_record");		
		
		$url = I("url");
		if(!isset($url)){
			$url = "http://www.baidu.com";
		}
		$url = urldecode($url);

		if(empty($title) ){
			$title = "零美云合";
		}			
		
		if(empty($keywords) ){
			$keywords = "零美云合";
		}		
		if(empty($description) ){
			$description = "零美云合";
		}	
		if(empty($bear_copyright) ){
			$bear_copyright = "©2016-2021 广州零美生物医药科技有限公司";
		}		
		if(empty($bear_record) ){
			$bear_record = "粤ICP备16051300号";
		}				
		
		$this->assign("title", urldecode($title));
		$this->assign("keywords", urldecode($keywords));
		$this->assign("description", urldecode($description));
		$this->assign("bear_copyright", urldecode($bear_copyright));
		$this->assign("bear_record", urldecode($bear_record));
		if(strpos($url, "http") == -1){
			$url = "http://" .$url;
		}
		$this->assign("url" , $url);
		$url = get_current_url();
		cookie("back_url", $url, time() + 60 * 60 * 24 , "/");
		$this->display();
	}


	public function expand(){
		$this->display();
	}	
	public function login_password(){
		$this->display();
	}
	public function back_password(){
		$this->display();
	}

	public function agreement(){
		$this->assign("title", "用户协议");
		$agreement = htmlspecialchars_decode(\app\library\SettingHelper::get("bear_agreement"), ENT_QUOTES) ;
		$this->assign("agreement", $agreement);
		$this->display();
	}
	
	public function agreement_view(){
		$this->assign("title", "用户协议");
		$agreement = htmlspecialchars_decode(\app\library\SettingHelper::get("bear_agreement"), ENT_QUOTES) ;
		$this->assign("agreement", $agreement);
		$this->display();
	}
	
	public function about_us(){
		$this->assign("title","关于我们");
		$about_us = htmlspecialchars_decode(\app\library\SettingHelper::get("bear_about_us"),ENT_QUOTES);
		$this->assign("about_us",$about_us);
		$this->display();
	}
	
	public function about_us_view(){
		$this->assign("title","关于我们");
		$about_us = htmlspecialchars_decode(\app\library\SettingHelper::get("bear_about_us"),ENT_QUOTES);
		$this->assign("about_us",$about_us);
		$this->display();
	}
	
	public function rule(){
		$this->assign("title","分红规则");
		$rule = htmlspecialchars_decode(\app\library\SettingHelper::get("bear_rule"),ENT_QUOTES);
		$this->assign("rule",$rule);
		$this->display();
	}
	
	public function rule_view(){
		$this->assign("title","分红规则");
		$rule = htmlspecialchars_decode(\app\library\SettingHelper::get("bear_rule"),ENT_QUOTES);
		$this->assign("rule",$rule);
		$this->display();
	}
	
	public function promo_code_rule(){
		$this->assign("title","推广码规则");
		$rule = htmlspecialchars_decode(\app\library\SettingHelper::get("bear_promo_code_rule"),ENT_QUOTES);
		$this->assign("promo_code_rule",$rule);
		$this->display();
	}
	
	public function promo_code_rule_view(){
		$this->assign("title","推广码规则");
		$rule = htmlspecialchars_decode(\app\library\SettingHelper::get("bear_promo_code_rule"),ENT_QUOTES);
		$this->assign("promo_code_rule",$rule);
		$this->display();
	}
	
	public function share_app(){
		$uuid = I("uuid");
		$url = \app\library\SettingHelper::get("bear_download_url", "http://a.app.qq.com/o/simple.jsp?pkgname=com.hongtong.doumi.android");
		$this->assign("url", $url);
		$this->assign("title", "斗米");
		$this->display("normal/share_app");
	}
	
	public function essay_list(){
		$this->display();
	}
	
	public function essay_detail(){
		$id = I("get.essay_id");
		
		if(empty($id)){
			$this->error("找不到相关文章");
		}
		$essay = M("essay")->where(['id' => $id])->find();
		if(empty($essay)){
			$this->error("找不到相关文章");
		}
		M("essay")->where(['id' => $id])->setInc("click_count", 1);
		$this->assign("essay", $essay);
		$this->display();
	}
	
	public function essay_detail_view(){
		$id = I("get.essay_id");
	
		if(empty($id)){
			$this->error("找不到相关文章");
		}
		$essay = M("essay")->where(['id' => $id])->find();
		if(empty($essay)){
			$this->error("找不到相关文章");
		}
		M("essay")->where(['id' => $id])->setInc("click_count", 1);
		$this->assign("essay", $essay);
		$this->display();
	}

    public function protocol(){
        $this->assign("title","入驻协议");
        $protocol = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_seller_protocol"),ENT_QUOTES);
        $this->assign("protocol",$protocol);
        $this->display();
    }
	
	
}