<?php
namespace app\admin\controller;
use app\admin\Admin;
class Statistics extends Admin
{
	// 订单统计
	public function order_statistics() {
		$map = "order_state in (2,3,4,5,7)";
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

    public function order_excel() {
        $map = "order_state in (2,3,4,5,7)";
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

        $orders = M("order")->where($map)->field("DATE_FORMAT(FROM_UNIXTIME(date_add),'%Y-%m-%d') as date,ifnull(count(id),0) as amount")->order("date_add")->group("DATE_FORMAT(FROM_UNIXTIME(date_add),'%Y-%m-%d')")->buildSql();

        $headArr = ["下单日期","订单数量"];
        $this->export("订单统计", $orders, $headArr);
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
		$map = "is_minus = 2";
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

	public function revenue_excel() {
        $map = "is_minus = 2";
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

        $revenues = M("finance_op")->where($map)->field("DATE_FORMAT(FROM_UNIXTIME(date_add),'%Y-%m-%d') as date,sum(amount) as amount")->order("date_add")->group("DATE_FORMAT(FROM_UNIXTIME(date_add),'%Y-%m-%d')")->buildSql();

        $headArr = ["日期","金额"];
        $this->export("营收统计", $revenues, $headArr);
	}
	
	// 商品销售排行
	public function goods_statistics() {
		$map = " 1=1";
		$map .=" AND (o.order_state in (4, 5,7))";
		
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
        $map .=" AND (o.order_state in (4, 5,7))";

        $min_date = I("min_date");
        $max_date = I("max_date");

        if (!empty($min_date)){
            $map .= " and o.date_add >= ".strtotime($min_date);
        }

        if (!empty($max_date)){
            $map .= " and o.date_add <= ".strtotime($max_date);
        }

        $goods = M("order")->alias("o")->join("order_goods og",'og.order_id = o.id')->join("goods g",'g.goods_id = og.goods_id')->where($map)->field("g.name,sum(og.quantity) as quantity")->group("og.goods_id")->limit(10)->order("quantity desc")->select();
		
		$filename="商品销售排行";
		$headArr=array("商品","销售量","备注");
		$this->getExcel($filename,$headArr,$goods);
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

	public function customer_trend_excel() {
        $year = (int)I('year');
        $month = (int)I('month');
        $x = I("x");
        $customer = null;
        if(empty($x) || $x == 1){
            $date = date("Y-m-d");
            //将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
            $todayTime= strtotime($date);
            $condition = ['date_add' => ['egt', $todayTime - 60 * 60 * 24 * 7]];
        }else if($x == 2){
            $date = date("Y-m-d");
            //将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
            $todayTime= strtotime($date);
            $condition = ['date_add' => ['egt', $todayTime - 60 * 60 * 24 * 30]];
        }else{
            $year =  $year == 0 ? date('Y') : $year;
            if($month == 0){
                $start_time = mktime(0,0,0,1 , 1, $year);
                $end_time = mktime(0,0,0,1,1, $year + 1);
                //将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
                $condition = ['date_add' => [['egt', $start_time],['elt', $end_time]]];
            }else{
                $start_time = mktime(0,0,0,$month , 1, $year);
                $end_time = mktime(0,0,0,$month + 1,1, $year);
                //将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
                $condition = ['date_add' => [['egt', $start_time],['elt', $end_time]]];
            }
        }
        $finances = M('customer')->where($condition)->group("FROM_UNIXTIME(date_add,'%Y-%m')")
            ->field("FROM_UNIXTIME(date_add,'%Y-%m-%d') as date , count(1) as count")->order("date_add desc")->select();

        $filename="用户增长趋势";
        $headArr=array("日期","用户量","备注");
        $this->getExcel($filename,$headArr,$finances);
    }
	
	
}