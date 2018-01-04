<?php
namespace app\wap\controller;
use think\Controller;
class Commission extends \app\wap\UserController
{
	public function score_detail(){
		
		$customer_id = $this->customer_id;
		$goods_id = (int)I("goods_id");
		if(empty($goods_id)){
			$this->error("请输入商品信息");
		}
		
		$this->display();
	}
	
	public function exchange_record(){
		$this->display();
	}
	
	public function exchange(){
		
		$customer_id = $this->customer_id;
		$goods_id = I("goods_id");
		
		$withdraw = \app\library\SettingHelper::get("shuaibo_commission_withdraw",['is_open' => 1, 'weixin' => 1, 'min_withdraw' => 100 , 'min_audit' => 500 , 'min_date' => 7 ]);
		$goods = D("customer_extend_record")->getOneGoodsExtendRecord($customer_id, $goods_id);
		
		if(empty($goods)){
			$this->error("找不到相关商品信息");
		}
		
		$customer = M("customer")->where(['customer_id' => $customer_id])->field("phone,agent_id,date_add")->find();
		if($customer['agent_id'] == 0 && $customer['date_add'] < strtotime("2016-10-26")){
			$customer['agent_id'] = 37;
		}
		$this->assign("customer", $customer);
		$this->assign("available", $goods['available']);
		$this->assign("withdraw", $withdraw);
		$this->display();
	}
	public function my_score(){
		$customer_id = $this->customer_id;
	
		$records = D("customer_extend_record")->getCustomerRecord($customer_id);

		$this->assign("records", $records);
		$this->display();
	}
}