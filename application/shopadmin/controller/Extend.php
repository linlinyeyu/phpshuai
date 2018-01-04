<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Extend extends Admin
{
	public function withdraw()
	{
		$map = ['cw.state' => 0];
		
		$type = I("type");
		
		if($type > 0){
			$map['cw.type'] = $type;
		}
		I("name") && $map['c.nickname'] = ['like', '%' . I("name") .'%'];
		
		I("phone") && $map['c.phone'] = ['like' , I('phone') . '%'];
		
		I("uuid") && $map['c.uuid'] = ['like' , I("uuid") . "%"];
		
		$min_date = I("min_date");
		
		$max_date = I("max_date");
		
		if(!empty($min_date)){
			$map['cw.date_add'] = [['gt' , strtotime($min_date)]];
		}
		
		if(!empty($max_date)){
			if(!isset($map['cw.date_add']) ){
				$map['cw.date_add'] = [];
			}
			$map['cw.date_add'][] = ['lt' , strtotime($max_date)];
		}
		
		$count = M("customer_withdraw")->alias("cw")
		->join("customer c","c.customer_id = cw.customer_id")
		->where($map)->count();
		$page = new \com\Page($count , 20);
		
		$withdraw = \app\library\SettingHelper::get("bear_commission_withdraw",['is_open' => 1, 'weixin' => 1, 'min_withdraw' => 100 , 'min_audit' => 500 , 'min_date' => 7 ]);
		
		$min_date = time() - $withdraw['min_date'] * 24 * 60 * 60;
		
		$total_sql = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->where(['cer.state' => 1, 'ce.pid = c.customer_id' , 'cer.goods_id = g.goods_id'])
		->field("sum(cer.commission) as commission")
		->buildSql();
		
		$used_sql = M("customer_withdraw")
		->where(['customer_id = c.customer_id' , 'state' => 1, 'goods_id = g.goods_id'])
		->field("sum(money)")
		->buildSql();
		
		$balance_sql = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->join("order o", "o.id = cer.order_id")
		->where(['goods_id = g.goods_id' , 'cer.state' => 1,  'ce.pid = c.customer_id'
				 ,'_string' => " (cer.date_add > $min_date or (o.order_state = 3 or o.order_state = 2 or o.order_state = 7 ) )" ])->field("sum(cer.commission)")
		->buildSql();
		
		
		$records = M("customer_withdraw")->alias("cw")
		->join("customer c", "c.customer_id = cw.customer_id")
		->field("cw.*,c.avater, c.nickname,c.commission, g.name as goods_name, ifnull({$total_sql},0) total,".
		" ifnull({$used_sql},0) as used, ifnull({$balance_sql},0) as balance")
		->join("goods g", "g.goods_id = cw.goods_id")
		->where($map)
		->limit($page->firstRow .",". $page->listRows)
		->order("cw.date_add desc")
		->select();
		
		foreach ($records as &$r){
			$r['available'] = $r['total'] - $r['used'] - $r['balance'];
			
			if($r['avater'] && strpos($r['avater'], "http") !== 0){
				$r['avater'] = $this->image_url . $r['avater'];
			}
			
		}
		$this->assign("records", $records);
		$this->assign("page", $page->show());
		$this->display();
	}
	
	public function withdraw_record(){
		$map = [];
		
		$type = I("type");
		
		if($type > 0){
			$map['cw.type'] = $type;
		}
		I("name") && $map['c.nickname'] = ['like', '%' . I("name") .'%'];
		
		I("phone") && $map['c.phone'] = ['like' , I('phone') . '%'];
		
		
		I("uuid") && $map['c.uuid'] = ['like' , I("uuid") . "%"];
		
		$min_date = I("min_date");
		
		$max_date = I("max_date");
		
		if(!empty($min_date)){
			$map['cw.date_add'] = [['gt' , strtotime($min_date)]];
		}
		
		if(!empty($max_date)){
			if(!isset($map['cw.date_add']) ){
				$map['cw.date_add'] = [];
			}
			$map['cw.date_add'][] = ['lt' , strtotime($max_date)];
		}
		$count = M("customer_withdraw")->alias("cw")
		->join("customer c","c.customer_id = cw.customer_id")
		->where($map)->count();
		$page = new \com\Page($count , 20);
		
		$withdraw = \app\library\SettingHelper::get("bear_commission_withdraw",['is_open' => 1, 'weixin' => 1, 'min_withdraw' => 100 , 'min_audit' => 500 , 'min_date' => 7 ]);
		
		$min_date = time() - $withdraw['min_date'] * 24 * 60 * 60;
		
		$total_sql = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->where(['cer.state' => 1, 'ce.pid = c.customer_id' , 'cer.goods_id = g.goods_id'])
		->field("sum(cer.commission) as commission")
		->buildSql();
		
		$used_sql = M("customer_withdraw")
		->where(['customer_id = c.customer_id' , 'state' => 1, 'goods_id = g.goods_id'])
		->field("sum(money)")
		->buildSql();
		
		$balance_sql = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->join("order o", "o.id = cer.order_id")
		->where(['goods_id = g.goods_id' , 'cer.state' => 1,  'ce.pid = c.customer_id'
				 ,'_string' => " (cer.date_add > $min_date or (o.order_state = 3 or o.order_state = 2 or o.order_state = 7 ) )" ])
				 ->field("sum(cer.commission)")
		->buildSql();
		
		
		
		$records = M("customer_withdraw")->alias("cw")
		->join("customer c", "c.customer_id = cw.customer_id")
		->join("user u","u.id = cw.user_id","LEFT")
		->field("u.username ,c.avater, cw.*, c.nickname,c.commission, g.name as goods_name, ifnull({$total_sql},0) total,".
		" ifnull({$used_sql},0) as used, ifnull({$balance_sql},0) as balance")
		->join("goods g", "g.goods_id = cw.goods_id")
		->where($map)
		->limit($page->firstRow .",". $page->listRows)
		->order("cw.date_add desc")
		->select();
		
		foreach ($records as &$r){
			$r['available'] = $r['total'] - $r['used'] - $r['balance'];
			if($r['avater'] && strpos($r['avater'], "http") !== 0){
				$r['avater'] = $this->image_url . $r['avater'];
			}
		}
		$total = M("customer_withdraw")->alias("cw")
		->join("customer c","c.customer_id = cw.customer_id")
		->where(array_merge($map, ['cw.state' =>1 ]))->sum("cw.money");
		$total = $total ? $total : 0;
		$this->assign("records", $records);
		$this->assign("page", $page->show());
		$this->assign("total", $total);
		$this->display();
	}
	
	public function agree(){
		$id = I("id");
		empty($id) && $this->error("请传入id");
		
		
		$withdraw = \app\library\SettingHelper::get("bear_commission_withdraw",['is_open' => 1, 'weixin' => 1, 'min_withdraw' => 100 , 'min_audit' => 500 , 'min_date' => 7 ]);
		
		$min_date = time() - $withdraw['min_date'] * 24 * 60 * 60;
		
		$total_sql = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->where(['cer.state' => 1, 'ce.pid = cw.customer_id' , 'cer.goods_id = cw.goods_id'])
		->field("sum(cer.commission) as commission")
		->buildSql();
		
		$used_sql = M("customer_withdraw")
		->where(['customer_id = cw.customer_id', 'state' => 1, 'goods_id = cw.goods_id'])
		->field("sum(money)")
		->buildSql();
		
		$balance_sql = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->join("order o", "o.id = cer.order_id")
		->where(['goods_id = cw.goods_id' , 'cer.state' => 1,  'ce.pid = cw.customer_id'
				 ,'_string' => " (cer.date_add > $min_date or (o.order_state = 3 or o.order_state = 2 or o.order_state = 7 ) )" ])->field("sum(cer.commission)")
		->buildSql();
		
		$withdraw = M("customer_withdraw")
		->alias("cw")
		->where(['id' => $id])
		->field("cw.customer_id,cw.state,cw.money,IFNULL({$total_sql},0) as total,IFNULL({$balance_sql},0) as balance, IFNULL({$used_sql},0) as used")
		->find();
		
		empty($withdraw) && $this->error("找不到提现相关记录");
		$withdraw['state'] != 0 && $this->error("此记录已被操作");
		$money = round($withdraw['money'], 2);
		$withdraw = round(($withdraw['total'] - $withdraw['balance'] - $withdraw['used']), 2);
		if($money > $withdraw){
			$this->error("此用户可提现金额不足，无法提现");
		}
		
		$result = D("customer_withdraw")->withdraw($id, session("userid"));
		
		$nickname = M("customer")->where(['customer_id' => $withdraw['customer_id']])->getField('nickname');
		
		if($result['errcode'] == 0){
			$this->log( $nickname ."申请提现成功");
			$this->success("提现成功");
		}
		
		$this->error($result['message']);
		
	}
	
	public function disagree(){
		!($id = I("id")) && $this->error("参数错误");
		!($reason = I("reason")) && $this->error("请输入原因");
		$record = M("customer_withdraw")->where(['id' => $id])->find();
		empty($record) && $this->error("找不到对应记录");
		$record['state'] != 0 && $this->error("状态错误");
		
		if(!empty($record['orders'])){
			$sql = M("customer_extend")->alias("ce")
			->where(['ce.pid' => $record['customer_id'],'ce.customer_extend_id = cer.customer_extend_id'])
			->field("1 as t")
			->buildSql();
			M("customer_extend_record")
			->alias("cer")
			->where(['cer.order_id' => ['in', $record['orders']], ['_string' => "exists $sql"]])
			->save(['withdraw_state' => 0]);
		}
		M("customer_withdraw")->where(['id' => $id])->save(['state' => 2,'date_audit' => time(),'reply' => $reason, 'user_id' => session("userid")]);
		$client = \app\library\message\MessageClient::getInstance();
		$message = new \app\library\message\Message();
		$message->setTargetIds($record['customer_id'])
		->setPlatform([\app\library\message\Message::PLATFORM_ALL])
		->setTemplate("withdraw_fail")
		->setExtras(['title' => '申请提现失败', 'content' => '您的提现申请未通过，原因为：' . $reason])
		->setWeixinExtra(['money' => $record['money'], 'date_add' => $record['date_add']])
		->setAction(\app\library\message\Message::ACTION_WITHDRAW_RECORD);
		$client->pushCache($message);
		
		$nickname = M("customer")->where(['customer_id' => $record['customer_id']])->getField('nickname');
		$this->log( $nickname ."申请提现失败");
		$this->success("操作成功");
	}
	
	public function settings(){
		
		if(IS_POST){
			$settings = I("settings");
			$settings = json_decode($settings, true);
			
			\app\library\SettingHelper::set("bear_commission", $settings);
			
			$this->success("修改成功");
		}
		
		$settings = \app\library\SettingHelper::get("bear_commission" , ['is_open' => 1,
				'commission1_rate' => 0.04 , 'commission2_rate' =>0.02 ,'commission3_rate' => 0,
				'commission1_pay' => 0,'commission2_pay' => 0, 'commission3_pay' => 0
		]);


        $host = \app\library\SettingHelper::get("bear_image_url");

        $goods_list = M('goods')
            ->alias('g')
            ->join("goods pg","pg.goods_id = g.pid","LEFT")
            ->field("g.*, concat('$host', g.cover) as image_url,pg.name as pname")
            ->where(['g.on_sale'=>1])
            ->group("g.goods_id")
            ->order("g.date_add desc");
        $goods_list = $goods_list->select();
////
////
//        echo "<pre>";
//        print_r($goods_list);
//        echo "</pre>";
//
//exit;

		$this->assign("settings", $settings);
        $this->assign("goods_list", $goods_list);
		$this->display();
	}
	
	public function withdraw_settings(){
		
		if(IS_POST){
			$settings = I("settings");
			$settings = json_decode($settings, true);
			\app\library\SettingHelper::set("bear_commission_withdraw", $settings);
			$this->success("修改成功");
		}
		
		$settings = $withdraw = \app\library\SettingHelper::get("bear_commission_withdraw",
				['is_open' => 1, 'weixin' => 1,
						'min_withdraw' => 100 , 
						'min_audit' => 500 , 
						'min_date' => 7 ]);
		
		$this->assign("settings", $settings);
		
		$this->display();
	}
	
	public function extend_people(){
		$customer_id = (int)I("customer_id");
		empty($customer_id) && $this->error("请传入用户id");
		$map = [];		
		$name = I("name");
		I("name") && $map['_string'] = " (c.nickname like '%$name%' or c.realname like '%$name%')";
		
		I("phone") && $map['c.phone'] = ['like' , I('phone') . '%'];
		
		$level = (int)I("level");
		
		if($level > 0){
			$map['ce.level'] = $level;
			$map['ce.commission'] = ['gt', 0];
		}
		
		$type = (int)I("type");
		
		$having = "";
		if($type == 1){
			$having = "count(o.id) > 0";
		}elseif($type == 2){
			$having = "count(o.id) = 0";
		}
		
		
		I("commission") && $map['ce.commission'] = ['gt',(int)I("commission")];
		
		I("uuid") && $map['c.uuid'] = ['like' , I("uuid") . "%"];
		
		$min_date = I("min_date");
		
		$max_date = I("max_date");
		
		if(!empty($min_date)){
			$map['c.date_sale'] = [['gt' , strtotime($min_date)]];
		}
		
		if(!empty($max_date)){
			if(!isset($map['c.date_sale']) ){
				$map['c.date_sale'] = [];
			}
			$map['c.date_sale'][] = ['lt' , strtotime($max_date)];
		}
		
		$extend_map = ['cer.customer_extend_id = ce.customer_extend_id and cer.state = 1'];
		
		$goods_id = I("goods_id");
		
		if(!empty($goods_id)){
			$extend_map['cer.goods_id'] = $goods_id;
			$goods_sql = M("customer_extend_record")->where(['goods_id' => $goods_id])->field("customer_extend_id")->buildSql();
			$map[] = " ce.customer_extend_id in $goods_sql";
		}
		
		$extend_sql = M("customer_extend_record")->alias("cer")->where($extend_map)->field("sum(cer.commission)")->buildSql();
		
		
		$extends = [];
		
		$page = null;
		if($level == -1){
			$mylevel = 1;
			$down_id = [$customer_id];
			do{
				$t = array_merge($map , ['c.agent_id' => ['in', $down_id]]);
				$c = M("customer")
				->alias("c")
				->join("customer_extend ce","ce.customer_id = c.customer_id and ce.pid = c.agent_id and ce.level = 1","LEFT")
				->join("order o","o.customer_id = c.customer_id and o.order_state in (2,3,4,5,7)","LEFT")
				->where($t)
				->group("c.customer_id")
				->order("date_sale desc, date_add desc")
				->having($having)
				->field("c.customer_id , c.date_sale, c.uuid , c.avater , c.phone , c.date_add , c.nickname , c.agent_id , $mylevel as level, ifnull($extend_sql, 0) as commission")
				->select();
				
				foreach ($c as &$u){
					if($u['avater'] && strpos($u['avater'], "http") !== 0){
						$u['avater'] = $this->image_url . $u['avater'];
					}
				}
				unset($u);
				$extends = array_merge($extends, $c);
				$down_id = [];
				foreach ($c as $a){
					if($a['agent_id'] > 0){
						$down_id[] = $a['customer_id'];
					}
				}
				$down_id = array_unique($down_id);
				$mylevel ++;
			}while(!empty($down_id));
			$count = count($extends);
			$page = new \com\Page($count, $count);
		}else{
			$map['ce.pid'] = $customer_id;
			$count = M("customer")
			->alias("c")
			->join("customer_extend ce" ,"ce.customer_id = c.customer_id ")
			->join("order o","o.customer_id = c.customer_id and o.order_state in (2,3,4,5,7)","LEFT")
			->group("c.customer_id")
			->field("1 as t")
			->where($map)
			->having($having)
			->select();
			$count = count($count);
			$page = new \com\Page($count, 20);
			
			//下级列表
			$extends = M("customer")
			->alias("c")
			->join("customer_extend ce" ,"ce.customer_id = c.customer_id ")
			->join("order o","o.customer_id = c.customer_id and o.order_state in (2,3,4,5,7)","LEFT")
			->where($map)
			->having($having)
			->field("ce.customer_id, ce.pid, ifnull($extend_sql, 0) as commission,ce.level,ce.date_add ,c.date_sale, c.nickname,c.uuid,c.phone,c.avater")
			->group("c.customer_id")
			->order("date_sale desc, date_add desc")
			->limit($page->firstRow ."," . $page->listRows)
			->select();
			
			foreach ($extends as &$u){
				if($u['avater'] && strpos($u['avater'], "http") !== 0){
					$u['avater'] = $this->image_url . $u['avater'];
				}
			}
			unset($u);
			
		}
		
		
		//当前用户的分销详情
		$total = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->where(['cer.state' => ['in', '1'], 'ce.pid' => $customer_id])
		->sum("cer.commission");
		
		$total = empty($total) ? 0 : $total;
		
		
		$total_used = M("customer_withdraw")
		->where(['customer_id' => $customer_id, 'state' => 1])
		->sum("money");
		
		$total_used = empty($total_used) ? 0 : $total_used;
		
		$withdraw = \app\library\SettingHelper::get("bear_commission_withdraw",['is_open' => 1, 'weixin' => 1, 'min_withdraw' => 100 , 'min_audit' => 500 , 'min_date' => 7 ]);
		
		$min_date = time() - $withdraw['min_date'] * 24 * 60 * 60;
		
		$total_balance = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->join("order o", "o.id = cer.order_id")
		->join("goods g","g.goods_id = cer.goods_id")
		->where(['g.is_deleted' => 0 , 'cer.state' => ['in', '1,3'],  'ce.pid' => $customer_id
				 ,'_string' => " (cer.date_add > $min_date or (o.order_state = 3 or o.order_state = 2 or o.order_state = 7 ) )" ])->sum("cer.commission");
		
		$total_balance = empty($total_balance) ? 0 : $total_balance;
		
		
		$customer_1 = ['total' => $total, 'used' => $total_used, 'balance' => $total_balance, 'available' => $total - $total_used - $total_balance];
		
		$level_0 = M("customer_extend")
		->where(['pid = c.customer_id', "level" => 1, 'commission' => 0])
		->field("count(1)")
		->buildSql();
		
		$level_1 = M("customer_extend")
		->where(['pid = c.customer_id', "level" => 1, 'commission' => ['gt', 0]])
		->field("count(1)")
		->buildSql();
		
		$level_2 = M("customer_extend")
		->where(['pid = c.customer_id', "level" => 2, 'commission' => ['gt', 0]])
		->field("count(1)")
		->buildSql();
		
		$level_3 = M("customer_extend")
		->where(['pid = c.customer_id', "level" => 3, 'commission' => ['gt', 0]])
		->field("count(1)")
		->buildSql();
		
		$customer = M("customer")
		->alias("c")
		->where(['c.customer_id' => $customer_id])
		->field("ifnull($level_0 , 0) as level_0, ifnull($level_1,0) as level_1,ifnull($level_2,0) as level_2,ifnull($level_3 , 0) as level_3,c.nickname ")
		->find();
		
		$customer = array_merge($customer_1, $customer);
		
		$goods = M("goods")->where(['has_commission' => 1 , 'is_deleted' => 0])->field("name , goods_id")->select();
		
		
		$this->assign("goods", $goods);
		$this->assign("page", $page->show());
		$this->assign("extends", $extends);
		$this->assign("customer", $customer);
		$this->display();
	}
	
	public function extend_excel(){
	$customer_id = (int)I("customer_id");
		empty($customer_id) && $this->error("请传入用户id");
		$map = [];		
		$name = I("name");
		I("name") && $map['_string'] = " (c.nickname like '%$name%' or c.realname like '%$name%')";
		
		I("phone") && $map['c.phone'] = ['like' , I('phone') . '%'];
		
		$level = (int)I("level");
		
		if($level > 0){
			$map['ce.level'] = $level;
			$map['ce.commission'] = ['gt', 0];
		}
		
		$type = (int)I("type");
		
		$having = "";
		if($type == 1){
			$having = "count(o.id) > 0";
		}elseif($type == 2){
			$having = "count(o.id) = 0";
		}
		
		
		I("commission") && $map['ce.commission'] = ['gt',(int)I("commission")];
		
		I("uuid") && $map['c.uuid'] = ['like' , I("uuid") . "%"];
		
		$min_date = I("min_date");
		
		$max_date = I("max_date");
		
		if(!empty($min_date)){
			$map['c.date_sale'] = [['gt' , strtotime($min_date)]];
		}
		
		if(!empty($max_date)){
			if(!isset($map['c.date_sale']) ){
				$map['c.date_sale'] = [];
			}
			$map['c.date_sale'][] = ['lt' , strtotime($max_date)];
		}
		
		$extend_map = ['cer.customer_extend_id = ce.customer_extend_id and cer.state = 1'];
		
		$goods_id = I("goods_id");
		
		if(!empty($goods_id)){
			$extend_map['cer.goods_id'] = $goods_id;
			$goods_sql = M("customer_extend_record")->where(['goods_id' => $goods_id])->field("customer_extend_id")->buildSql();
			$map[] = " ce.customer_extend_id in $goods_sql";
		}
		
		$extend_sql = M("customer_extend_record")->alias("cer")->where($extend_map)->field("sum(cer.commission)")->buildSql();
		
		$extends = [];
		
		
		$withdraw = M("customer_withdraw")
		->alias("cw")
		->where(['state' => 1, 'customer_id = c.customer_id'])
		->field("sum(cw.money)")
		->buildSql();
		
		$order_sum = M("order")
		->where(['customer_id = c.customer_id', 'order_state' => ['in', '4,5']])
		->field("sum(order_amount)")
		->buildSql();
		
		$order_num = M("order")
		->where(['customer_id = c.customer_id', 'order_state' => ['in', '4,5']])
		->field("count(1)")
		->buildSql();
		
		$field = "c.uuid, ifnull(c.realname,c.nickname) as name, c.phone, ifnull(pc.realname,pc.nickname) as pname , pc.uuid as pid,".
					" case when c.is_subscribe = 0 then '未关注' else '已关注' end as sub,c.commission, ifnull($withdraw, 0) as withdraw , ".
		"ifnull($order_sum , 0) as sum , ifnull($order_num ,0) as num,FROM_UNIXTIME(c.date_sale) as date_sale , FROM_UNIXTIME(c.date_add) as date_add ";
		$extends = [];
		
		$page = null;
		if($level == -1){
			$mylevel = 1;
			$down_id = [$customer_id];
			do{
				$t = array_merge($map , ['c.agent_id' => ['in', $down_id]]);
				$c = M("customer")
				->alias("c")
				->join("customer_extend ce","ce.customer_id = c.customer_id and ce.pid = c.agent_id and ce.level = 1","LEFT")
				->join("order o","o.customer_id = c.customer_id and o.order_state in (2,3,4,5,7)","LEFT")
				->join("customer pc", "pc.customer_id = c.agent_id","LEFT")
				->where($t)
				->group("c.customer_id")
				->order("date_sale desc, date_add desc")
				->having($having)
				->field("c.customer_id , c.date_sale, c.uuid , c.avater , c.phone , c.date_add , c.nickname , c.agent_id , $mylevel as level, ifnull($extend_sql, 0) as commission")
				->select();
				
				$extends = array_merge($extends, $c);
				$down_id = [];
				foreach ($c as $a){
					if($a['agent_id'] > 0){
						$down_id[] = $a['customer_id'];
					}
				}
				$down_id = array_unique($down_id);
				$mylevel ++;
			}while(!empty($down_id));
			$count = count($extends);
			$page = new \com\Page($count, $count);
		}else{
			$map['ce.pid'] = $customer_id;
			//下级列表
			$extends = M("customer")
			->alias("c")
			->join("customer_extend ce" ,"ce.customer_id = c.customer_id ")
			->join("order o","o.customer_id = c.customer_id and o.order_state in (2,3,4,5,7)","LEFT")
			->join("customer pc", "pc.customer_id = c.agent_id","LEFT")
			->where($map)
			->having($having)
			->field($field)
			->group("c.customer_id")
			->order("date_sale desc, date_add desc")
			->select();
			
		}
		
		if(empty($extends)){
			$this->error("没有对应的人员");
		}
		foreach ($extends as &$e){
			$e['name'] = $this->replace_emoji($e['name']);
			$e['pname'] = $this->replace_emoji($e['pname']);
		}
		
		
		$filename="下级列表";
		$headArr=array("编号","昵称","电话","上级","上级编号","关注状态","佣金","已打款佣金","成交金额","订单数","成为销售员时间","创建日期");
		$this->getExcel($filename,$headArr,$extends);
	}
	
	public function extend_record(){
		$customer_id = (int)I("customer_id");
		
		$goods_id = (int)I("goods_id");
		
		$order_sn = I("order_sn");
		
		$withdraw_id = I("withdraw_id");
		
		$map = ['cer.state' => 1];
		
		$customer_id > 0 && $map['ce.pid'] = $customer_id;
		
		$goods_id > 0 && $map['cer.goods_id'] = $goods_id;
		
		!empty($order_sn) && $map['o.order_sn'] = ['like' , $order_sn . '%'];
		
		$min_date = I("min_date");
		
		$max_date = I("max_date");
		
		
		
		if(!empty($min_date)){
			$map['cer.date_add'] = [['gt' , strtotime($min_date)]];
		}
		
		if(!empty($max_date)){
			if(!isset($map['cer.date_add']) ){
				$map['cer.date_add'] = [];
			}
			$map['cer.date_add'][] = ['lt' , strtotime($max_date)];
		}
		
		$ta_customer_id = I("ta_customer_id");
		
		I("name") && $map['c.nickname'] = ['like' , '%' . I("name") . "%"];
		
		I("uuid") && $map['c.uuid'] = ['like' , I("uuid") . "%"];
		
		I("ta_customer_id") && $map['c.customer_id'] = I("ta_customer_id");
		
		I("phone") && $map['c.phone'] = ['like' , I('phone') . '%'];
		
		$level = I("level");
		
		if($level > 0){
			$map['ce.level'] = $level;
		}
		
		$count = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->join("customer c","c.customer_id = ce.customer_id")
		->join("order o","o.id = cer.order_id")
		->where($map)
		->count();
		
		$page = new \com\Page($count , 20);
		
		$orders = [];
		
		if(!empty($customer_id) && !empty($withdraw_id)){
			$order_ids = M("customer_withdraw")->where(['id' => $withdraw_id])->getField("orders");
			if(!empty($order_ids)){
				$orders = M("customer_extend_record")
				->alias("cer")
				->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
				->join("goods g","g.goods_id = cer.goods_id")
				->join("order o", "o.id = cer.order_id")
				->join("customer c","c.customer_id = ce.customer_id")
				->field("g.name as goods_name,c.nickname, c.customer_id , c.avater,cer.commission, cer.date_add, o.order_sn ,ce.level")
				->where(['ce.pid' => $customer_id, 'cer.state' => 1, 'cer.order_id' => ['in', $order_ids]])
				->select();
					
				foreach ($orders as &$u){
					if($u['avater'] && strpos($u['avater'], "http") !== 0){
						$u['avater'] = $this->image_url . $u['avater'];
					}
					unset($u);
				}
			}
			
		}
		
		$records = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->join("customer c","c.customer_id = ce.customer_id")
		->join("order o","o.id = cer.order_id")
		->join("goods g","g.goods_id = cer.goods_id")
		->order("cer.date_add desc")
		->field("c.nickname,c.avater,c.customer_id, c.uuid, o.order_sn, g.name as goods_name,g.goods_id,ce.level,cer.date_add,cer.commission,cer.order_id")
		->where($map)
		->limit($page->firstRow . "," . $page->listRows)
		->select();
		
		foreach ($records as &$u){
			if($u['avater'] && strpos($u['avater'], "http") !== 0){
				$u['avater'] = $this->image_url . $u['avater'];
			}
		}
		unset($u);
		
		if($goods_id > 0 && $customer_id > 0){
			$withdraw = \app\library\SettingHelper::get("bear_commission_withdraw",['is_open' => 1, 'weixin' => 1, 'min_withdraw' => 100 , 'min_audit' => 500 , 'min_date' => 7 ]);
			
			$min_date = time() - $withdraw['min_date'] * 24 * 60 * 60;
			
			$total_sql = M("customer_extend_record")
			->alias("cer")
			->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
			->where(['cer.state' => 1, 'ce.pid' => $customer_id , 'cer.goods_id = g.goods_id'])
			->field("sum(cer.commission) as commission")
			->buildSql();
			
			$used_sql = M("customer_withdraw")
			->where(['customer_id' => $customer_id, 'state' => 1, 'goods_id = g.goods_id'])
			->field("sum(money)")
			->buildSql();
			
			$balance_sql = M("customer_extend_record")
			->alias("cer")
			->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
			->join("order o", "o.id = cer.order_id")
			->where(['cer.goods_id = g.goods_id' , 'cer.state' => 1,  'ce.pid' => $customer_id
				 ,'_string' => " (cer.date_add > $min_date or (o.order_state = 3 or o.order_state = 2 or o.order_state = 7 ) )" ])
				 ->field("sum(cer.commission)")
			->buildSql();
			
			$goods = M("goods")
			->alias("g")
			->where(['has_commission' => 1,'is_deleted' => 0, 'goods_id' => $goods_id])
			->field("IFNULL({$total_sql},0) as total,IFNULL({$balance_sql},0) as balance, IFNULL({$used_sql},0) as used, ".
			" g.goods_id,g.name")
			->find();
			if(!empty($goods)){
				$goods['available'] = $goods['total'] - $goods['used'] - $goods['balance'];
			}
			$this->assign("goods", $goods);
		}
		$desc = "";
		if($goods_id > 0 && $customer_id > 0){
			$desc = "此页面展示为：上级为".M("customer")->where(['customer_id' => $customer_id])->getField("nickname") .",且购买商品为：" 
					. M("goods")->where(['goods_id' => $goods_id])->getField("name") . "的分销记录";
		}else if($goods_id > 0){
			$desc = "此页面展示为商品："
					. M("goods")->where(['goods_id' => $goods_id])->getField("name") . "的分销记录";
		}else if($customer_id > 0 && $ta_customer_id > 0){
			$desc = "此页面展示为：下级".M("customer")->where(['customer_id' => $ta_customer_id])->getField("nickname") ."为上级" .M("customer")->where(['customer_id' => $customer_id])->getField("nickname") ."贡献的佣金记录" ;
		}else if($customer_id){
			$desc = "此页面展示为：上级为".M("customer")->where(['customer_id' => $customer_id])->getField("nickname") ."的总分销记录";
		}
		
		$this->assign("desc", $desc);
		$this->assign("records" , $records);
		$this->assign("page", $page->show());
		$this->assign("orders", $orders);
		
		$this->display();
	}
	
	
}
