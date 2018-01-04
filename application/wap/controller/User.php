<?php
namespace app\wap\controller;
use think\Controller;
class User extends \app\wap\UserController
{	
	
	public function index()
	{
		setcookie("s_goods_id", null, time() - 1, "/");
		setcookie("s_option_id", null, time() - 1, "/");
		setcookie("s_quantity", null, time() - 1, "/");
		$this->display(); 
	}
	
	public function cart(){
		$customer_id = $this->customer_id;
		$orders = D("User")->getCart($customer_id);
		$cart_number = D("customer")->CartNumber($this->customer_id);
		$this->assign("goods_number", $cart_number);
		$this->assign("orders", $orders);
		$this->display();
	}
	public function mine(){
		$customer_id = $this->customer_id;
		$customer = D("user")->getUserInfo($customer_id);
		$cart_number = D("customer")->CartNumber($this->customer_id);
		$this->assign("goods_number", $cart_number);
		$this->assign("customer", $customer);
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
			$this->redirect(U("user/mine"));
		}
		$host = $this->host;
		$this->assign("app_url", "http://" . get_domain() . "/wap/home/qrcode?uuid=" . $content['content']['uuid']);
		$this->assign("qrcode" , $host . $content['content']['name']);
		$this->display();
	}
	
	public function improve_data(){
		$customer_id =  $this->customer_id ;
		
		$host = $this->host;
		
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		$customer = M("customer")->where(['customer_id' => $customer_id])
		->field("customer_id,phone,avater as image, nickname, agent_id,date_add")
		->find();
		if($customer['phone']){
			$this->redirect(U("home/index"));
		}
		if($customer['image'] && strpos($customer['image'], "http") !== 0){
			$customer['image'] = $host . $customer['image'] . $suffix;
		}
		
		if(empty($customer['agent_id']) && $customer['date_add'] < strtotime("2016-10-26")){
			$customer['agent_id'] = 37;
		}
		
		$this->assign("customer", $customer);
		$this->display();
	}
	
	
	public function my_customer(){
		$customer_id = $this->customer_id;
		$level_counts=D("user")->getmycustomer($customer_id);
		$this->assign("levels", $level_counts);
		$this->display();
	}
	public function goal_customer(){
		$customer_id = $this->customer_id;
		
		$count = D("user")->getgoalcustomer($customer_id);
		
		$this->assign("count", $count);
		$this->display();
	}
	
	public function changeaddress(){
		
		
		$customer_id = $this->customer_id;
		
		$content=D("user")->changeaddress($customer_id);
		$this->assign("customer", $content['customer']);
		$this->assign("hotcity", $content['hotcity']);
		$this->display();
	}
	public function personal_inform(){
		$customer_id = $this->customer_id;
		$customer = D("customer")->GetUserInfo($customer_id);
		$this->assign('customer',$customer);
		$this->display();
	}
	public function exchange(){
		$this->display();
	}
	
	public function more(){
		$phone=\app\library\SettingHelper::get("bear_service_phone");
		$this->assign("phone",$phone);
		$this->display();
	}

	public function changename(){
		$this->display();
	}
	
	public function changephone(){
		$customer_id = $this->customer_id;
		$content=D("user")->changephone($customer_id);
		$real_phone = $content['customer']['phone'];
		$phone = substr_replace($real_phone,'******',3,6);
		$this->assign('customer',$content['customer']);
		$this->assign("real_phone",$real_phone);
		$this->assign('phone',$phone);
		$this->display();
	}
	public function member_rank(){
		$customer_id = $this->customer_id;
		$levels = \app\library\SettingHelper::get_levels();
		$group_id = M("customer")->where(['customer_id' => $customer_id])->getField("group_id");
		$levels = array_reverse($levels);
		$this->assign("levels", $levels);
		$this->assign("group_id", $group_id);
		$this->display();
	}
	public function changephone_2(){
		$customer_id = $this->customer_id;
		$customer = M("customer")->where(['customer_id'=>$customer_id])->field("phone,customer_id")->find();
		$phone = S("changephone".$customer_id);
		if(!$customer['phone']||S("changephone_".$customer_id)==md5($customer['phone'])){
			$this->assign('customer',$customer);
			$this->display();
		}else{
			$this->redirect(U('user/changephone'));
		}
		
	}
	
	public function changepassword(){
		$this->display();
	}
	

	public function about_us(){
		$about_us = htmlspecialchars_decode(\app\library\SettingHelper::get("bear_about_us")) ;
		$this->assign("about_us",$about_us);
		$this->display();
	}
	
	public function about_us_view(){
		$this->assign("title", "关于我们");
		$about_us = htmlspecialchars_decode(\app\library\SettingHelper::get("bear_about_us")) ;
		$this->assign("about_us", $about_us);
		$this->display();
	}
	
	public function ranklist(){
		$customer_id = $this->customer_id;
		$banners = D("banner")->GetBanners(1, 4);
		$records = D("customer")->getRankList($customer_id);
		
		if(is_string($records)){
			$this->error($records);
		}
		
		$this->assign("banners", $banners);
		$this->assign("records" , $records);
		$this->display();
	}
	
	public function settings(){
		$customer_id = $this->customer_id;
		$switch = M("customer")->where(['customer_id' => $customer_id])->getField("need_push");
		$this->assign("switch", $switch);
		$this->display();
		
	}
	
	

}