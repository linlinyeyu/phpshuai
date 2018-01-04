<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Statistics extends Admin
{
	
	// 订单统计
	public function order_statistics() {
		$map = "order_state in (2,3,4,5,7) and seller_id = ".session("sellerid");
		$min_date = I("min_date");
		$max_date = I("max_date");
		
		if (!empty($min_date)){
			$map .= " and date_add >= ".strtotime($min_date);
		}
		if(!empty($max_date)){
			$map .=" and date_add <= ".strtotime($max_date);
		}
		
		if (empty($min_date) && empty($max_date)){
			$map .= " and DATE_SUB(CURDATE(),INTERVAL 7 DAY) <= DATE_FORMAT(FROM_UNIXTIME(date_add),'%Y-%m-%d')";
		}
		
		$orders = M("order")->where($map)->field("ifnull(count(id),0) as amount,DATE_FORMAT(FROM_UNIXTIME(date_add),'%Y-%m-%d') as date")->order("date_add")->group("DATE_FORMAT(FROM_UNIXTIME(date_add),'%Y-%m-%d')")->select();		
		if (!empty($orders)){
			$min_order_date = '';
			$max_order_date = '';
			if (!empty($min_date) && !empty($max_date)){
				$min_order_date = date("Y-m-d",strtotime($min_date));
				$max_order_date = date("Y-m-d",strtotime($max_date));
			}
			
			if (!empty($min_date) && empty($max_date)){
				$min_order_date = date("Y-m-d",strtotime($min_date));
				$max_order_date = end($orders)['date'];
			}
			
			if (empty($min_date) && !empty($max_date)){
				$min_order_date = $orders[0]['date'];
				$max_order_date = date("Y-m-d",strtotime($max_date));
			}
			
			if (empty($min_date) && empty($max_date)){
				$min_order_date = date("Y-m-d",strtotime("-7 days"));
				$max_order_date = date("Y-m-d",time());
			}
			
			$date = self::getDateFromRange($min_order_date, $max_order_date);
			$exist_date = array();
			foreach ($orders as $order){
				$exist_date[] = $order['date'];	
			}
			$diff_date = array_diff($date, $exist_date);
			foreach ($diff_date as $lose_date){
				array_push($orders, ['amount' => '0','date' => $lose_date]);
			}
			$sort_date = array();
			foreach ($orders as $order){
				$sort_date[] = $order['date'];
			}
			array_multisort($sort_date,SORT_ASC,$orders);
		}
		$result = array();
		if (!empty($orders)){
			foreach ($orders as $order){
				$result['xAxis'][] = $order['date'];
				$result['yAxis'][] = $order['amount'];
			}
		}else {
			$result['xAxis'] = [];
			$result['yAxis'] = [];
		}
		
		$this->assign("orders",$result);
		$this->display();
	}
	
	private function getDateFromRange($startdate, $enddate){
	
		$stimestamp = strtotime($startdate);
		$etimestamp = strtotime($enddate);
	
		// 计算日期段内有多少天
		$days = ($etimestamp-$stimestamp)/86400+1;
	
		// 保存每天日期
		$date = array();
	
		for($i=0; $i<$days; $i++){
			$date[] = date('Y-m-d', $stimestamp+(86400*$i));
		}
	
		return $date;
	}
	
	public function revenue_statistics(){
		$customer_id = M("seller_shopinfo")->where(["seller_id" => session("sellerid")])->getField("customer_id");
		$map = "customer_id = $customer_id and is_minus = 2";
		$min_date = I("min_date");
		$max_date = I("max_date");
		
		if (!empty($min_date)){
			$map .= " and date_add >= ".strtotime($min_date);
		}
		if(!empty($max_date)){
			$map .=" and date_add <= ".strtotime($max_date);
		}
		
		if (empty($min_date) && empty($max_date)){
			$map .= " and DATE_SUB(CURDATE(),INTERVAL 7 DAY) <= DATE_FORMAT(FROM_UNIXTIME(date_add),'%Y-%m-%d')";
		}
		
		$revenues = M("finance_op")->where($map)->field("sum(amount) as amount,DATE_FORMAT(FROM_UNIXTIME(date_add),'%Y-%m-%d') as date")->order("date_add")->group("DATE_FORMAT(FROM_UNIXTIME(date_add),'%Y-%m-%d')")->select();
		if (!empty($revenues)){
			$min_revenue_date = '';
			$max_revenue_date = '';
			if (!empty($min_date) && !empty($max_date)){
				$min_revenue_date = date("Y-m-d",strtotime($min_date));
				$max_revenue_date = date("Y-m-d",strtotime($max_date));
			}
				
			if (!empty($min_date) && empty($max_date)){
				$min_revenue_date = date("Y-m-d",strtotime($min_date));
				$max_revenue_date = end($revenues)['date'];
			}
				
			if (empty($min_date) && !empty($max_date)){
				$min_revenue_date = $revenues[0]['date'];
				$max_revenue_date = date("Y-m-d",strtotime($max_date));
			}
				
			if (empty($min_date) && empty($max_date)){
				$min_revenue_date = date("Y-m-d",strtotime("-7 days"));
				$max_revenue_date = date("Y-m-d",time());
			}
			
			$date = self::getDateFromRange($min_revenue_date, $max_revenue_date);
			$exist_date = array();
			foreach ($revenues as $revenue){
				$exist_date[] = $revenue['date'];
			}
			$diff_date = array_diff($date, $exist_date);
			foreach ($diff_date as $lose_date){
				array_push($revenues, ['amount' => '0','date' => $lose_date]);
			}
			$sort_date = array();
			foreach ($revenues as $revenue){
				$sort_date[] = $revenue['date'];
			}
			array_multisort($sort_date,SORT_ASC,$revenues);
		}
		$result = array();
		if (!empty($revenues)){
			foreach ($revenues as $revenue){
				$result['xAxis'][] = $revenue['date'];
				$result['yAxis'][] = $revenue['amount'];
			}
		}else {
			$result['xAxis'] = [];
			$result['yAxis'] = [];
		}
		
		$this->assign("revenue",$result);
		$this->display();
	}
	
	public function order_excel() {
		$map = " 1=1";
		$map .=" AND (o.order_state in (2,3,4,5,7))";
		I('get.min_date') && $map .= " AND o.date_add >= ".strtotime(I('get.min_date')) ;
		I("get.max_date") && $map .=" AND o.date_add <= ".(strtotime(I("get.max_date"))) ;
		I('get.order_number') && $map .=" AND o.order_sn = '".trim(I('get.order_number')) . "'";
		I('get.customer_name') && $map .=" AND c.nickname like '%".trim(I('get.customer_name'))."%'";
		I("get.phone") && $map .= " AND c.phone like '".trim(I("get.phone")). "%'";
		I("province") && $map .=" AND oa.province_id = " . I("province");
		I("city") && $map .=" AND oa.city_id = " . I("city");
		$year = I("get.year");
		$time_type = I("time_type");
		$quarter_name = I("quarter_name");
		$month_name = I("month_name");
		$min_time = 0;
		$max_time = 0;
		if(!empty($year) && $time_type != 0){
			if($time_type==1){
				$min_month = $quarter_name * 3 ;
				$max_month = ($quarter_name + 1) * 3;
				$min_time = mktime(0,0,0,$min_month,1,$year);
				$max_time = mktime(0,0,0,$max_month,1,$year);
			}else if($time_type==2){
				$min_time = mktime(0,0,0,$month_name , 1, $year);
				$max_time = mktime(0,0,0,$month_name + 1, 1, $year);
			}
			if($time_type == 0 
					|| ($time_type == 2 && $month_name == 0) 
					|| ($time_type == 1 && $quarter_name == 0)){
				$min_time = mktime(0,0,0,1,1, $year);
				$max_time = mktime(0,0,0,1,1, $year + 1);
			}
		}
		if($time_type == 3){
			$min_time = 0;
			$max_time = 0;
			if(I("get.min_date")){
				$min_time = strtotime(I('get.min_date'));
			}
			if(I("get.max_date")){
				$max_time = strtotime(I("get.max_date"));
			}
		}
		if($min_time > 0){
			$map .= " AND o.date_add >= ".$min_time ;
		}
		
		if($max_time > 0){
			$map .=" AND o.date_add <= ".$max_time ;
		}
		$goods_map  = [];
		
		if(I("goods_id")){
			$goods_map['og.goods_id'] = I("goods_id");
		}
		
		if(I("type")){
			$goods_map['g.goods_type'] = I("type");
		}
		
		if(!empty($goods_map)){
			$goods_map[] = 'og.order_id = o.id';
			$sql = M("order_goods")->alias("og")
			->join("goods g","g.goods_id = og.goods_id","LEFT")
			->field("1 as t")
			->where($goods_map)->buildSql();
			$map .= " AND exists $sql";
		}
		
		$lists = M('order')
		->alias('o')
		->join("order_goods og", "og.order_id = o.id")
		->join("goods g", "g.goods_id = og.goods_id")
		->join("order_state os","os.order_state_id = o.order_state")
		->join("order_address oa","oa.id = o.address_id")
		->join("pay_type pt","pt.pay_id = o.pay_id","LEFT")
		->join('customer c','o.customer_id=c.customer_id')
		->join("express e","e.express_id = o.express_id","LEFT")
		->field("o.order_sn,os.name as state,c.uuid, c.nickname,c.realname, ifnull(pt.name,'未支付') as pay_name,o.out_order_sn, oa.name as address_name, oa.phone as phone,".
				" concat(oa.province,oa.city,oa.district,oa.address) as address, o.order_amount, o.goods_amount, o.express_amount, ".
				"e.express_name, o.express_sn, " .
				"FROM_UNIXTIME(o.date_add) as date_add , FROM_UNIXTIME(o.date_pay) as date_pay,FROM_UNIXTIME(o.date_send) as date_send, ".
				"FROM_UNIXTIME(o.date_received) as date_received, FROM_UNIXTIME(o.date_finish) as date_finish,FROM_UNIXTIME(o.date_refund) as date_refund,GROUP_CONCAT(g.name, '×', og.quantity ) as goods")
		->group("o.id")
		->where($map)
		->buildSql();
		
		/*$ids = [];
		foreach ($lists as &$l){
			$ids[] = $l['id'];
			$l['nickname'] = $this->replace_emoji($l['nickname']);
		}
		unset($l);
		if(empty($lists)){
			$this->error("数据为空");
		}
		$order_goods = M("order_goods")
		->alias("og")
		->join("goods g","g.goods_id = og.goods_id","LEFT")
		->field("og.order_id,GROUP_CONCAT(g.name, '×', og.quantity ) as goods")
		->group("og.order_id")
		->where(['og.order_id' => ['in', $ids]])
		->select();
		$order_goods = array_key_arr($order_goods, "order_id");
		foreach ($lists as &$l){
			if(isset($order_goods[$l['id']])){
				$l['goods'] = $order_goods[$l['id']][0]['goods'];
			}
			unset($l['id']);
		}*/
		
		$headArr = ["订单号","订单状态","用户编号", "昵称",'真实姓名', '支付方式',"支付订单号",  "收货人","收货人手机","收货地址", "订单金额",'商品金额',"运费","快递公司","快递号","下单日期","支付日期","发货日期","收货日期",'完成日期','退款日期','购买商品'];
		$this->export("订单列表", $lists, $headArr);
	}
	
	// 商品销售排行
	public function goods_statistics() {
		$map = " 1=1";
		$map .=" AND (o.order_state in (4, 5,7)) and o.seller_id = ".session("sellerid");
		
		$min_date = I("min_date");
		$max_date = I("max_date");
		
		if (!empty($min_date)){
			$map .= " and o.date_add >= ".strtotime($min_date);
		}
		
		if (!empty($max_date)){
			$map .= " and o.date_add <= ".strtotime($max_date); 
		}
		
		$goods = M("order")->alias("o")->join("order_goods og",'og.order_id = o.id')->join("goods g",'g.goods_id = og.goods_id')->where($map)->field("sum(og.quantity) as quantity,g.name")->group("og.goods_id")->limit(10)->order("quantity desc")->select();
		$result = array();
		if (!empty($goods)){
			foreach ($goods as $item){
				$result['xAxis'][] = $item['name'];
				$result['yAxis'][] = $item['quantity'];
			}
		}else {
			$result['xAxis'] = [];
			$result['yAxis'] = [];
		}
		
		$this->assign("goods",$result);
		$this->display();
	}
	
	public function goods_excel() {
		$map = " 1=1";
		$map .=" AND (o.order_state in (4,5))";
		I("get.goods_id") && $map .=" AND og.goods_id = ". I("get.goods_id") . " ";
		$year = I("get.year");
		$time_type = I("time_type");
		$quarter_name = I("quarter_name");
		$month_name = I("month_name");
		if(!empty($year) && $time_type != 0){
			if($time_type==1){
				$min_month = $quarter_name * 3 ;
				$max_month = ($quarter_name + 1) * 3;
				$min_time = mktime(0,0,0,$min_month,1,$year);
				$max_time = mktime(0,0,0,$max_month,1,$year);
			}else if($time_type==2){
				$min_time = mktime(0,0,0,$month_name , 1, $year);
				$max_time = mktime(0,0,0,$month_name + 1, 1, $year);
			}
			if($time_type == 0 
					|| ($time_type == 2 && $month_name == 0) 
					|| ($time_type == 1 && $quarter_name == 0)){
				$min_time = mktime(0,0,0,1,1, $year);
				$max_time = mktime(0,0,0,1,1, $year + 1);
			}
			
			$map .= " AND o.date_add >= ".$min_time ;
			$map .=" AND o.date_add <= ".$max_time ;
		}
		
		I('get.min_date') && $map .= " AND o.date_add >= ".strtotime(I('get.min_date')) ;
		I("get.max_date") && $map .=" AND o.date_add <= ".(strtotime(I("get.max_date"))) ;
		
		$province = I("get.province");
		$city = I("get.city");
		if(!empty($province)){
			I("get.province") && $map .=" AND oa.province_id = ". $province . " ";
			if(!empty($city)){
				$city_name=M("zone")
				->where(['zone_id'=>$province])
				->getField("name");
				I("get.city") && $map .=" AND oa.city_id = ". $city . " ";
			}
		}
		I("get.type") && $map .=" AND g.goods_type = ". I("get.type") . " ";
		$goods_type = M("goods_type")
		->field("id,name")
		->select();
		
		$sale_count = I("get.sale_count") ;
		if(!I("get.sale_count")){
			$sale_count = 0;
		}
		$lists = M('order_goods')
		->alias('og')
		->join('order o','o.id=og.order_id')
		->join("order_address oa","oa.id=o.address_id")
		->join('goods g','g.goods_id=og.goods_id')
		->field("g.sku,g.name,g.price,count(1) as counts,'' as remark")
		->where($map)
		->order('count(1) DESC')
		->group("og.goods_id")
		->having("count(1) >= $sale_count")
		->select();
		
		$filename="商品销售排行";
		$headArr=array("商品编号","商品","商品价格","销售量","备注");
		$this->getExcel($filename,$headArr,$lists);
	}
	
	// 会员消费排行
	public function customer_statistics() {
		$map =" c.active = 1 and c.total_account > 0";
		I('get.customer_name') && $map .=" AND c.nickname like '%".trim(I('get.customer_name'))."%'";
		I("get.phone") && $map .= " AND c.phone like '".trim(I("get.phone")). "%'";
		
		$withdraw = \app\library\SettingHelper::get("bear_commission_withdraw",['is_open' => 1, 'weixin' => 1, 'min_withdraw' => 100 , 'min_audit' => 500 , 'min_date' => 7 ]);
		
		$min_date = time() - $withdraw['min_date'] * 24 * 60 * 60;
		
		$count = M('customer')->alias("c")->where($map)->count();
		$page = new \com\Page($count, 20);
		
		
		$total = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->where(['ce.pid = c.customer_id',"cer.state" => 1])
		->field("sum(cer.commission)")
		->buildSql();
		
		$balance =  M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->where(['ce.pid = c.customer_id',"cer.state" => 1,'cer.date_add' => ['gt',  $min_date]])
		->field("sum(cer.commission)")
		->buildSql();
		
		$withdraw = M("customer_withdraw")
		->alias("cw")
		->where(['cw.customer_id = c.customer_id','cw.state' => 1])
		->field("sum(money)")
		->buildSql();
		
		$lists = M('customer')
		->alias('c')
		->where($map)
		->field("c.uuid,c.customer_id,c.nickname,c.phone,total_account, c.avater, ifnull($total, 0) as total, ifnull($balance, 0) as balance, ifnull($withdraw, 0) as withdraw")
		->limit($page->firstRow . ',' . $page->listRows)
		->order("total_account desc")
		->select();
		
		foreach ($lists as &$u){
			$u['available'] = $u['total'] - $u['balance'] - $u['withdraw'];
			if($u['avater'] && strpos($u['avater'], "http") !== 0){
				$u['avater'] = $this->image_url . $u['avater'];
			}
		}
		unset($u);
		$total = M("customer_extend_record")
		->alias("cer")
		->where(['cer.state' => 1])
		->field("sum(cer.commission)");
		
		if(!empty($map)){
			$total
			->join("customer_extend ce", "ce.customer_extend_id = cer.customer_extend_id")
			->join("customer c","c.customer_id = ce.pid");
		}
		$total = $total->buildSql();
		
		$balance = M("customer_extend_record")
		->alias("cer")
		->where(['cer.state' => 1, 'cer.date_add' => ['gt',  $min_date]] )
		->field("sum(cer.commission)");
		
		
		if(!empty($map)){
			$balance
			->join("customer_extend ce", "ce.customer_extend_id = cer.customer_extend_id")
			->join("customer cc","cc.customer_id = ce.pid");
		}
		$balance = $balance->buildSql();
		
		
		$withdraw = M("customer_withdraw")
		->alias("cw")
		->where ( ['cw.state' => 1 ] )
		->join("customer cc","cc.customer_id =cw.customer_id")
		->field("sum(cw.money)")
		->buildSql();
		
		$count = M("customer")->where(['group_id' => ['gt', 2]])->count();
		
		$commission = M()->query(" select ifnull($total, 0) as total, ifnull($withdraw,0) as withdraw, ifnull($balance,0) as balance from dual ");
		$commission = $commission[0];
		
		$commission['available'] = $commission['total'] - $commission['withdraw'] - $commission['balance'];
		
		$commission['count'] = $count;
		
		$this->assign("count", $commission);
		$this->assign('page',$page->show());
		$this->assign('lists',$lists);
		
		$this->display();
	}
	
	public function customer_excel() {
		$map =" c.total_account > 0 AND c.active = 1";
		I('get.customer_name') && $map .=" AND c.nickname like '%".trim(I('get.customer_name'))."%'";
		I("get.phone") && $map .= " AND c.phone like '".trim(I("get.phone")). "%'";
		
		$withdraw = \app\library\SettingHelper::get("bear_commission_withdraw",['is_open' => 1, 'weixin' => 1, 'min_withdraw' => 100 , 'min_audit' => 500 , 'min_date' => 7 ]);
		
		$min_date = time() - $withdraw['min_date'] * 24 * 60 * 60;
		
		$total = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->where(['ce.pid = c.customer_id',"cer.state" => 1])
		->field("sum(cer.commission)")
		->buildSql();
		
		$balance =  M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->where(['ce.pid = c.customer_id',"cer.state" => 1,'cer.date_add' => ['gt', $min_date]])
		->field("sum(cer.commission)")
		->buildSql();
		
		$withdraw = M("customer_withdraw")
		->alias("cw")
		->where(['cw.customer_id = c.customer_id','cw.state' => 1])
		->field("sum(money)")
		->buildSql();
		
		$lists = M('customer')
		->alias("c")
		->where($map)
		->field("uuid,nickname,phone,total_account, ifnull($total, 0) as total, ifnull($balance, 0) as balance, ifnull($withdraw, 0) ")
		->order('total_account DESC')
		->select();
		foreach ($lists as $k => &$v) {
			$v['nickname'] = $this->replace_emoji($v['nickname']);
		}
		
		$filename="用户消费排行";
		$headArr=array("用户编号","用户","手机","消费金额","总佣金", "结算期佣金", '已提现佣金',"可提现佣金");
		$this->getExcel($filename,$headArr,$lists);
	}
	
	
	public function customer_trend_stat() {
		$year = (int)I('year');
		$month = (int)I('month');
		$x = I("x");
		$customer = null;
		if(empty($x) || $x == 1){
			$customer = D("customer")->day_statistics(0,7,'y','count');
		}else if($x == 2){
			$customer =D('customer')->day_statistics();
		}else{
			$year =  $year == 0 ? date('Y') : $year;
			if($month == 0){
				$customer = D("customer")->year_statistics($year);
			}else{
				$customer = D("customer")->month_statistics($year, $month);
			}
		}
		$this->assign("customer",$customer);
		$this->display();
		
	}
	
	
}