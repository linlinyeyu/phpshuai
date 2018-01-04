<?php
namespace app\admin\controller;
use app\admin\Admin;
class Money extends Admin
{
	public function index()
	{
		$date = date('Y-m-d');
		$date = strtotime($date);
		
		$today_income = M('finance')->where(['date_add' => ['egt', $date]])->sum('amount');
		$yes_income = M('finance')->where(['date_add' => [['egt', $date - 60 * 60 *24 ],['elt', $date]]])->sum('amount');

		$today_weixin_income = M("finance")->where(['type' => ['in',"2,3"],'date_add' => ['egt', $date]])->sum('amount');
		$yes_weixin_income = M("finance")->where(['type' => ['in',"2,3"],'date_add' => [['egt', $date - 60 * 60 *24 ],['elt', $date]]])->sum('amount');

		$today_alipay_income = M("finance")->where(['type' => 1,'date_add' => ['egt', $date]])->sum('amount');
		$yes_alipay_income = M("finance")->where(['type' => 1,'date_add' => [['egt', $date - 60 * 60 *24 ],['elt', $date]]])->sum('amount') ;
		
		$days30_income = D("finance")->day_statistics();
		$days30_weixin_income = D("finance")->day_statistics(['in',"2,3"]);
		$days30_alipay_income = D("finance")->day_statistics(1);

		$month12_income = D("finance")->month_statistics();
		$month12_weixin_income = D("finance")->month_statistics(['in',"2,3"]);
		$month12_alipay_income = D("finance")->month_statistics(1);
		
		$customer_count = M("finance")->field('customer_id')->distinct(true)->count();
		
		$twice_count = M("finance")->field("customer_id")->distinct(true)->group("customer_id")->having(" count(customer_id) >= 2")->count();
		
		$max_money = M("finance")->max("amount");
		
		$total_money = M("finance")->sum("amount");
		
		
		$active_count = M("customer")->where(['is_robot' => 0])->count();
		
		if($active_count == 0){
			$active_count = 1;
		}
		if($customer_count == 0){
			$customer_count = 1;
		}
		$start_time = C("start_time");
		$start_time = strtotime($start_time);
		
		$month = (time() - $start_time) / (60 * 60 * 24 *30);
		$reg_apru = round($total_money /  $active_count / $month, 2);
		
		$pay_apru = round($total_money /  $customer_count / $month , 2); 
		
		
		$this->assign("reg_apru" , $reg_apru ? $reg_apru : 0) ;
		$this->assign("pay_apru", $pay_apru ? $pay_apru : 0);
		$this->assign("total_money", $total_money ? $total_money : 0);
		$this->assign("max_money", $max_money ? $max_money: 0);
		$this->assign("twice_count", $twice_count ? $twice_count : 0);
		$this->assign("customer_count" , $customer_count ? $customer_count : 0);
		$this->assign('today_income', $today_income ? $today_income: 0);
		$this->assign('yes_income', $yes_income ? $yes_income: 0);
		$this->assign('today_weixin_income', $today_weixin_income ? $today_weixin_income : 0);
		$this->assign('yes_weixin_income', $yes_weixin_income ?  $yes_weixin_income : 0);
		$this->assign('today_alipay_income', $today_alipay_income ? $today_alipay_income: 0);
		$this->assign('yes_alipay_income', $yes_alipay_income ? $yes_alipay_income: 0);
		$this->assign("days30_income", $days30_income);
		$this->assign("days30_alipay_income", $days30_alipay_income);
		$this->assign('days30_weixin_income', $days30_weixin_income);
		$this->assign("month12_income", $month12_income);
		$this->assign("month12_alipay_income", $month12_alipay_income);
		$this->assign("month12_weixin_income", $month12_weixin_income);
		$this->display();
	}

	public function detail()
	{
		$this->display();
	}

	public function paylist(){
		$count=M('finance')->count();
		$page = new \com\Page($count, 15);
		$finances = M('finance')
		->alias('f')
		->join("customer c", "c.customer_id = f.customer_id")
		->order("date_add DESC")
		->field("c.nickname, f.amount, f.source")
		->limit($page->firstRow . ',' . $page->listRows)
		->select();
		$this->assign('finances',$finances);
		$this->assign("page", $page->show());

		$this->display();
	}

	public function chargelist(){
		$map = [];
		$type = I("get.type");
		$name = I("get.name");
		$min_date = I("get.min_date");
		$max_date = I("get.max_date");
		$trade_no=I("get.trade_no");
		$pay_order=I("get.pay_order");
		$phone_number=I("get.phone_number");
		if(!empty($type) && $type > 0){
			$map['f.type'] = $type;
		}else{
			$type = 0;
		}
		if(!empty($trade_no)){
		    $map['f.trade_no']=['eq',$trade_no];
		}
		if(!empty($pay_order)){
		    $map['f.order_number']=['eq',$pay_order];
		}
		if(!empty($phone_number)){
		    $map['c.phone']=['like',$phone_number . "%"];
		}
		if(!empty($name)){
			$map['c.nickname'] = ['like',$name . "%"];
		}
		if(!empty($min_date)){

			$map['f.date_add'] = ['egt', strtotime($min_date)];
		}
		if(!empty($max_date)){
			if (!empty($map['date_add']) ) {
				$map['f.date_add'][] =  ['elt', strtotime($max_date) + 60 * 60 * 24];
			}else{
				$map['f.date_add'] = ['elt', strtotime($max_date) + 60 * 60 * 24];
			}
		}
		$count = M("finance")->alias("f")->join("customer c", "c.customer_id = f.customer_id")->where($map)->count();
		$page = new \com\Page($count,15);
		$finances = M("finance")
		->alias("f")
		->join("customer c","c.customer_id = f.customer_id")
		->join("order_goods og","og.order_number=f.order_number")
		->join("items i","i.item_id=og.item_id","LEFT")
		->join("goods g","g.goods_id=i.goods_id","LEFT")
		->field("c.nickname,c.phone as phone_number,f.amount,c.customer_id,f.trade_no, f.order_number, f.date_add,f.source,".
				" case when og.item_id = 0 then '充值' else GROUP_CONCAT(g.name) end as goods_names")
		->where($map)
		->limit($page->firstRow . "," . $page->listRows)
		->group("f.record_id")
		->select();
		$this->assign("page", $page->show());
		$this->assign("finances", $finances);
		$this->assign("name", $name);
		$this->assign("trade_no",$trade_no);
		$this->assign("pay_order",$pay_order);
		$this->assign("phone_number",$phone_number);
		$this->assign("type", $type);
		$this->assign("min_date", $min_date);
		$this->assign("max_date", $max_date);
		$this->display();
	}
	
	public function excel(){
		$map = [];
		$type = I("get.type");
		$name = I("get.name");
		$min_date = I("get.min_date");
		$max_date = I("get.max_date");
		$trade_no=I("get.trade_no");
		$pay_order=I("get.pay_order");
		$phone_number=I("get.phone_number");
		if(!empty($type) && $type > 0){
			$map['f.type'] = $type;
		}else{
			$type = 0;
		}
		if(!empty($trade_no)){
			$map['f.trade_no']=['eq',$trade_no];
		}
		if(!empty($pay_order)){
			$map['f.order_number']=['eq',$pay_order];
		}
		if(!empty($phone_number)){
			$map['c.phone']=['like',$phone_number . "%"];
		}
		if(!empty($name)){
			$map['c.nickname'] = ['like',$name . "%"];
		}
		if(!empty($min_date)){
		
			$map['f.date_add'] = ['egt', strtotime($min_date)];
		}
		if(!empty($max_date)){
			if (!empty($map['date_add']) ) {
				$map['f.date_add'][] =  ['elt', strtotime($max_date) + 60 * 60 * 24];
			}else{
				$map['f.date_add'] = ['elt', strtotime($max_date) + 60 * 60 * 24];
			}
		}
		$count = M("finance")->alias("f")->join("customer c", "c.customer_id = f.customer_id")->where($map)->count();
		$page = new \com\Page($count,15);
		$data = M("finance")
		->alias("f")
		->join("customer c","c.customer_id = f.customer_id")
		->join("order_goods og","og.order_number=f.order_number")
		->join("items i","i.item_id=og.item_id","LEFT")
		->join("goods g","g.goods_id=i.goods_id","LEFT")
		->field("c.customer_id, c.uuid,c.nickname,c.phone ,f.amount,f.source,concat('\\'', f.trade_no), concat('\\'', f.order_number), from_unixtime(f.date_add, '%Y-%m-%d %H:%i:%s')")
		->where($map)
		->group("f.record_id")
		->select();
		$headArr = ['用户标识','用户编号','用户昵称', '手机号','充值金额','支付类型', '支付流水号', '支付订单号', '支付日期'];
		$this->getExcel("recharge_list",$headArr , $data);
	}
	
}