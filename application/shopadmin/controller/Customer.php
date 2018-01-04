<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Customer extends Admin
{
	
	public function index()
	{
		$map = [];
		$uuid = I("get.uuid");
		$name = I("get.name");
		$phone = I("get.phone");
		$min_date = I("get.min_date");
		$max_date = I("get.max_date");
		$group_id = (int)I("get.group_id");
		$min_date_sale = I("get.min_date_sale");
		$max_date_sale = I("get.max_date_sale");
		if(!empty($uuid)){
			$map['c.uuid'] = $uuid;
		}
		if(!empty($group_id)){
			$map['c.group_id'] = $group_id;
		}
		if(!empty($phone)){
			$map['c.phone'] = ['like' , $phone . '%'];
		}
		
		if(!empty($name)){
			$map['_string'] = " (c.nickname like '%$name%' or c.realname like '%$name%')";
		}
		if(!empty($min_date)){
			$map['c.date_add'] = [['egt', strtotime($min_date)]];
		}
		if(!empty($max_date)){
			if (!empty($map['c.date_add']) ) {
				$map['c.date_add'][] =  ['elt', strtotime($max_date) ];
			}else{
				$map['c.date_add'] = ['elt', strtotime($max_date)];
			}
		}
		if(!empty($min_date_sale)){
			$map['c.date_sale'] = [['egt', strtotime($min_date_sale)]];
		}
		if(!empty($max_date_sale)){
			if (!empty($map['c.date_sale']) ) {
				$map['c.date_sale'][] =  ['elt', strtotime($max_date_sale) ];
			}else{
				$map['c.date_sale'] = ['elt', strtotime($max_date_sale)];
			}
		}
		
		
		
		$count = M('customer')->alias("c")
		->where($map)
		->count();
		
		
		$page = new \com\Page($count, 15);
		
		$done_money = M("order")
		->alias("o")
		->where(['o.order_state' => ['in', "2,3,4,5,7"] , 'o.customer_id = t.customer_id'])
		->field("sum(o.order_amount)")
		->buildSql();
		
		$done_count = M("order")
		->alias("o")
		->where(['o.order_state' => ['in', "2,3,4,5,7"] , 'o.customer_id = t.customer_id'])
		->field("count(o.id)")
		->buildSql();
		
		$withdraw = M("customer_withdraw")
		->alias("cw")
		->where(['cw.state' => 1 , 'cw.customer_id = t.customer_id'])
		->field("sum(cw.money)")
		->buildSql();
		
		$tmp_sql = M("customer")->alias("c")
		->where($map)->order("customer_id desc")
		->limit($page->firstRow . ',' . $page->listRows)
		->field("c.*")->buildSql();
		
		$prefix = C("database.prefix");
		$users = M()->query("select ifnull($withdraw,0) as money, ifnull($done_money,0) as done_money, ifnull($done_count,0) as done_count , t.*,cg.name as group_name, ifnull(pc.nickname, '总店') as pname,pc.uuid as pid,pc.avater as pavater,ifnull(t.date_sale,null) as datesale " . 
				" from {$tmp_sql} t left join {$prefix}customer_group cg on cg.group_id = t.group_id " .
			" left join {$prefix}customer pc on pc.customer_id = t.agent_id");
		
		/*$users = M('customer')
		->alias("c")
		->join("customer_group cg","cg.group_id =c.group_id","LEFT")
		->join("customer pc","pc.customer_id = c.agent_id","LEFT")
		->where($map)
		->order("customer_id DESC")
		->limit($page->firstRow . ',' . $page->listRows)
		->field("ifnull($withdraw,0) as money, ifnull($done_money,0) as done_money, ifnull($done_count,0) as done_count , c.*,cg.name as group_name, ifnull(pc.nickname, '总店') as pname,pc.uuid as pid,pc.avater as pavater,c.realname,ifnull(c.date_sale,null) as datesale")
		->select();
		*/
		foreach ($users as &$u){
			if($u['pavater'] && strpos($u['pavater'], "http") !== 0){
				$u['pavater'] = $this->image_url . $u['pavater'];
			}
			if($u['avater'] && strpos($u['avater'], "http") !== 0){
				$u['avater'] = $this->image_url . $u['avater'];
			}
		}
		
		
		
		$group=M("customer_group")
		->alias('cg')
		->field("group_id,name")
		->select();
		$this->assign('users',$users);
		$this->assign('group',$group);
		$this->assign('group_id',$group_id);
		$this->assign('count',$count);
		$this->assign("page", $page->show());
		$this->display();
	}
	
	public function index_excel(){
		$map = [];
		$uuid = I("get.uuid");
		$name = I("get.name");
		$phone = I("get.phone");
		$min_date = I("get.min_date");
		$max_date = I("get.max_date");
		$group_id = (int)I("get.group_id");
		$min_date_sale = I("get.min_date_sale");
		$max_date_sale = I("get.max_date_sale");
		if(!empty($uuid)){
			$map['c.uuid'] = $uuid;
		}
		if(!empty($group_id)){
			$map['c.group_id'] = $group_id;
		}
		if(!empty($phone)){
			$map['c.phone'] = ['like' , $phone . '%'];
		}
		
		if(!empty($name)){
			$map['_string'] = " (c.nickname like '%$name%' or c.realname like '%$name%')";
		}
		if(!empty($min_date)){
			$map['c.date_add'] = [['egt', strtotime($min_date)]];
		}
		if(!empty($max_date)){
			if (!empty($map['c.date_add']) ) {
				$map['c.date_add'][] =  ['elt', strtotime($max_date) ];
			}else{
				$map['c.date_add'] = ['elt', strtotime($max_date)];
			}
		}
		if(!empty($min_date_sale)){
			$map['c.date_sale'] = [['egt', strtotime($min_date_sale)]];
		}
		if(!empty($max_date_sale)){
			if (!empty($map['c.date_sale']) ) {
				$map['c.date_sale'][] =  ['elt', strtotime($max_date_sale) ];
			}else{
				$map['c.date_sale'] = ['elt', strtotime($max_date_sale)];
			}
		}
		
		
		$done_money = M("order")
		->alias("o")
		->where(['o.order_state' => ['in', "2,3,4,5,7"] , 'o.customer_id = c.customer_id'])
		->field("sum(o.order_amount)")
		->buildSql();
		
		$done_count = M("order")
		->alias("o")
		->where(['o.order_state' => ['in', "2,3,4,5,7"] , 'o.customer_id = c.customer_id'])
		->field("count(o.id)")
		->buildSql();
		
		$withdraw = M("customer_withdraw")
		->alias("cw")
		->where(['cw.state' => 1 , 'cw.customer_id = c.customer_id'])
		->field("sum(cw.money)")
		->buildSql();
		
		$users = M('customer')
		->alias("c")
		->join("customer_group cg","cg.group_id =c.group_id","LEFT")
		->join("customer pc","pc.customer_id = c.agent_id","LEFT")
		->where($map)
		->group("c.customer_id")
		->order("c.customer_id DESC")
		->field("c.uuid,c.nickname,c.realname,c.phone,cg.name as group_name,ifnull(pc.nickname, '总店') as pname,pc.uuid as pid,case when c.is_subscribe = 0 then '未关注' else '已关注' end,c.commission,ifnull($withdraw,0) as money, ifnull($done_money,0) as done_money, ifnull($done_count,0) as done_count , ifnull(FROM_UNIXTIME(c.date_sale , '%Y-%m-%d %h:%i:%s'),null) as datesale , FROM_UNIXTIME(c.date_add , '%Y-%m-%d %h:%i:%s')")
		->buildSql();
		
		$filename="用户列表";
		$headArr=array("编号","昵称","真实姓名","手机号", "等级","上级","上级编号","关注状态","佣金","已打款佣金","成交金额","订单数","成为销售员时间","创建日期");
		
		$this->export($filename, $users, $headArr);
	}
	
	public function disable()
	{
		!($id = (int)I('get.id')) && $this->error('参数错误');
		$name = D("customer")->getCustomerName($id);
		$this->log("禁止用户：" .$name);
		M('customer')->where(['customer_id'=>$id])->save(['active'=>0]) ? $this->success('成功') : $this->error('失败');;
	}
	
	public function delete_customer(){
		(!$id = (int)I("id")) && $this->error("请传入id");
		$customer = M("customer")->where(['customer_id' => $id])->field("access_token,group_id")->find();
		if(empty($customer)){
			$this->error("找不到相关人员");
		}
		/*$count = M("order")->where(['customer_id'=>$id, 'order_state' => ['in', '2,3,4,5,7']])->count();
		if($count > 0){
			$this->error("无法已购买过产品的人员");
		}
		
		$count = M("customer")->where(['agent_id' => $id])->count();
		if($count > 0 ){
			$this->error("无法删除拥有下级的人员");
		}*/
		$access_token = $customer['access_token'];
		M()->startTrans();
		try{
			M("address")->where(['customer_id' => $id])->delete();
			
			M("customer")->where(['customer_id' => $id])->delete();
			M("customer")->where(['agent_id' => $id])->save(['agent_id' => 0]);
			$customer_extend_ids = M("customer_extend")->where("customer_id = $id or pid = $id")->getField("customer_extend_id",true);
			if(!empty($customer_extend_ids)){
				M("customer_extend_record")->where(['customer_extend_id' => ['in', $customer_extend_ids]])->delete();
				M("customer_extend")->where(['customer_extend_id' => ['in', $customer_extend_ids]])->delete();
			}
			
			M("customer_withdraw")->where(['customer_id' => $id])->delete();
			M("finance_op")->where(['customer_id' => $id])->delete();
			$orders = M("order")->where(['customer_id' => $id])->select();
			$order_ids = [];
			$address_ids = [];
			foreach ($orders as $o){
				$order_ids[] = $o['id'];
				$address_ids[] = $o['address_id'];
			}
			
			if(!empty($address_ids)){
				M("order_address")->where(['id' => ['in', $address_ids]])->delete();
				M("order")->where(['id' => ['in', $order_ids]])->delete();
			}
			
			
			
			M("order_comment")->where(['customer_id' => $id])->delete();
			
			M("order_return")->where(['customer_id' => $id])->delete();
			
			M("customer_log")->where(['customer_id' => $id])->delete();
			
			M("user_statistics")->where(['customer_id' => $id])->delete();
		}catch(\Exception $e){
			M()->rollback();
			var_dump($e);
		}
		M()->commit();
		S($access_token , null);
		$this->success("操作成功");
		
	}

	public function able()
	{
		!($id = (int)I('get.id')) && $this->error('参数错误');
		$name = D("customer")->getCustomerName($id);
		$this->log("开启用户：" .$name);
		M('customer')->where(['customer_id'=>$id])->save(['active'=>1]) ? $this->success('成功') : $this->error('失败');;
	}

	public function change_agent(){
		(!$customer_id = I("customer_id")) && $this->error("用户信息有误");
		(!$agent_id = I("agent_id")) && $this->error("上级id有误");
	}
	
	public function edit(){
		if (IS_POST){
			$pid = (int)I("pid");
			$phone = I("phone");
			$id = I("id");
			$group_id = I("group_id");
			
			M("customer")->where(['customer_id' => $id])->save(['agent_id' => $pid, 'phone' => $phone, 'group_id' => $group_id]);
			
			D("customer_extend")->bind($id);
			
			$this->log("修改会员信息: " .M("customer")->where(['customer_id' => $id])->getField("nickname")  . "上级id为：$pid ; 手机号为：$phone ; 会员等级为" . M("customer_group")->where(['group_id' => $group_id])->getField("name"));
			$this->success("操作成功");
			
		}
		$this->error("请使用POST提交");
		
	}
	
	public function detail()
	{
		$map = [];
		!($id = (int)I('get.id')) && $this->error('参数错误');
		$map['c.customer_id'] = $id;

		$pwd = cookie("pay_pwd");
		$need_pwd = empty($pwd);
		$customer = M('customer')
		->alias("c")
		->join("customer pc","pc.customer_id = c.agent_id","LEFT")
		->join("customer_group cg","cg.group_id = c.group_id")
		->join("order o","o.customer_id = c.customer_id and o.order_state in (2,3,4,5)","left")
		->where($map)
		->group("c.customer_id")
		->field("c.* ,cg.name as group_name ,pc.nickname as pname,pc.customer_id as pid,pc.avater as pavater,count(o.id) as order_count, ifnull(sum(o.order_amount),0) as order_amount")
		->find();
		
		if($customer['pavater'] && strpos($customer['pavater'], "http") !== 0){
			$customer['pavater'] = $this->image_url . $customer['pavater'];
		}
		
		if($customer['avater'] && strpos($customer['avater'], "http") !== 0){
			$customer['avater'] = $this->image_url . $customer['avater'];
		}
		
		$levels = M("customer_group")
		->order("upgrade, group_id")
		->select();
		
		$pcustomers = M("customer_extend")
		->alias("ce")
		->where(['ce.customer_id' => $id , 'level' => 1])
		->join("customer c","c.customer_id = ce.pid")
		->field("c.customer_id , c.avater, c.uuid, c.nickname")
		->order("ce.date_add desc")
		->select();
		
		foreach ($pcustomers as &$u){
			if($u['avater'] && strpos($u['avater'], "http") !== 0){
				$u['avater'] = $this->image_url . $u['avater'];
			}
		}
		
		$this->assign("levels", $levels);
		$this->assign('customer',$customer);
		$this->assign('need_pwd', $need_pwd);
		$this->assign("pcustomers", $pcustomers);
		$this->display();
	}

	public function pay()
	{
		$settings = M("settings")->where(['setting_key' => 'pay_pwd'])->find();
		$pwd = cookie("pay_pwd");
		if(!empty($settings) && $settings['setting_value'] != $pwd ){
			setcookie("pay_pwd", "", 0);
			$this->error("请输入验证密码");
		}

		!($pay = (float)I('post.pay')) && $this->error('充值金额错误');
		!($id = (int)I('post.cid')) && $this->error('参数错误');
		$name = D("customer")->getCustomerName($id);
		D("user_action")->saveAction(session("userid"),0,$id,"用户".$name."充值". $pay."元");
		M('customer')->where(['customer_id'=>$id])->setInc('account',$pay) ? $this->success('成功') : $this->error('失败');
	}

	public function fine()
	{
		$settings = M("settings")->where(['setting_key' => 'pay_pwd'])->find();
		$pwd = cookie("pay_pwd");
		if(!empty($settings) && $settings['setting_value'] != $pwd ){
			setcookie("pay_pwd", "", 0);
			$this->error("请输入验证密码");
		}

		!($pay = (float)I('post.pay')) && $this->error('充值金额错误');
		!($id = (int)I('post.cid')) && $this->error('参数错误');
		$name = D("customer")->getCustomerName($id);
		D("user_action")->saveAction(session("userid"),0,$id,"用户".$name."被罚款". $pay."元");

		M('customer')->where(['customer_id'=>$id])->setDec('account',$pay) ? $this->success('成功') : $this->error('失败');
	}

	public function changepasswd()
	{
		!($id = (int)I('post.cid')) && $this->error('参数错误');
		!($newpwd = I('post.newpwd')) && $this->error('新密码不能为空');
		!($repwd = I('post.repwd')) && $this->error('重复密码不能为空');
		($newpwd != $repwd) &&  $this->error('两次密码不一样');

		$name = D("customer")->getCustomerName($id);
		D("user_action")->saveAction(session("userid"),0,$id,"用户".$name."修改密码");
		$reuslt = M('customer')->where(['customer_id'=>$id])->save(['passwd'=>md5($newpwd), 'customer_id' => $id]); 
		$reuslt ? $this->success('成功') : $this->error('失败');
	}

	public function verifypassword(){
		!($pwd = I("post.pwd")) && $this->error("请输入密码");
		$settings = M("settings")->where(['setting_key' => 'pay_pwd'])->find();
		$pwd = md5($pwd);
		if(!empty($settings) && $settings['setting_value'] != $pwd){
			$this->error("密码错误");
		}
		setcookie("pay_pwd", $pwd, time() + 60 * 15, "/");
		D("user_action")->saveAction(session("userid"),4,0,"验证密码成功");
		$this->success("密码正确，请继续操作");
	}

	public function add(){
		if(IS_POST){
			!($pwd = I("post.pwd")) && $this->error("请输入密码");
			!($name = I("post.name") && $this->error("请输入昵称"));
			!($phone = I("post.phone") && $this->error("请输入电话号码"));
			M("customer")->where(['phone' => $phone])->count() > 0 && $this->error("手机号已被注册");
			M("customer")->where(['nickname' => $name])->count() > 0 && $this->error("昵称已被使用");
			$data = ['phone'=>$phone,'nickname' => $name, 'passwd' => md5($pwd)];
			$data['date_add'] = time();
			$data['date_upd'] = time();
			$data['reg_ip'] = get_client_ip();
			$data['last_ip'] = $data['reg_ip'];
			$data['last_area'] = get_location($data['reg_ip']);
			$data['uuid'] = build_uuid();
			$data['promotion_code'] = make_promotion();
			$id = M('customer')->add($data) ;
			D("user_action")->saveAction(session("userid"),0,$id,"创建用户". $name);
			$id = $id ? $this->success("添加成功", U("customer/index")) : $this->error("添加失败");
		}
		$this->display();
	}

	public function money(){
		!($id = (int)I('get.id')) && $this->error('参数错误');
		$total = M("finance")->where(['customer_id' => $id])->sum('amount');
		$total = $total ? $total : 0;
		$ops = M("finance_op")->alias('fp')
		->join("order_goods og","og.order_id = fp.foreign_id")
		->join("items i","i.item_id = og.item_id","LEFT")
		->join("goods g","g.goods_id = i.goods_id","LEFT")
		->where(['fp.customer_id' => $id, "fp.type" => ['egt', 0],"fp.amount" => ['neq', 0]])
		->group("g.goods_id")
		->field("g.name as goods_name, sum(ABS(fp.amount)) as count,g.goods_id")
		->select();
		$resume = M("finance_op")->where(['customer_id' => $id, "type" => ['egt', 0]])->sum('abs(amount)');
		$resume = $resume ? $resume : 0;
		$this->assign("id", $id);
		$this->assign("total", $total);
		$this->assign("ops", $ops);
		$this->assign("resume", $resume);
		$this->display();
	}
	
	public function extend(){
		!($id = (int)I('get.id')) && $this->error('参数错误');
		$map = ['pid' => $id];
		$count=M('customer_extend')->alias("c")->where($map)->count();
		$page = new \com\Page($count, 15);
		$extends = M("customer_extend")->alias("ce")
		->join("customer c", "c.customer_id = ce.customer_id")
		->join("finance_op f", "f.customer_id = c.customer_id and f.type >= 0","left")
		->field("c.nickname, ce.*, ifnull(sum(abs(f.amount)) , 0 ) as account")
		->limit($page->firstRow . ',' . $page->listRows)
		->order("ce.date_add desc")
		->group("c.customer_id")
		->where($map)
		->select();
		
		
		$nickname = M("customer")->where(['customer_id'=>$id])->getField("nickname");
		$this->assign("count" , $count);
		$this->assign("nickname", $nickname);
		$this->assign("extends" , $extends);
		$this->assign("page", $page->show());
		$this->display();
	}
	
	public function search(){
		$key = I("term");
		$customers = M("customer")
		->where([ "_logic" => 'OR', 'nickname' => ['like', "%". $key ."%"], 'phone' => ['like', $key .'%'] , 'uuid' => ['like' , $key .'%']])
		->field("customer_id , avater , nickname, phone, uuid")
		->select();
		foreach ($customers as &$u){
			if($u['avater'] && strpos($u['avater'], "http") !== 0){
				$u['avater'] = $this->image_url . $u['avater'];
			}
		}
		
		die(json_encode($customers));
	}
	
	public function extend_detail(){
		!($id = (int)I('get.id')) && $this->error('参数错误');
		$map = ["cer.customer_extend_id" => $id];
		$count=M('customer_extend_record')->alias("cer")->where($map)->count();
		$page = new \com\Page($count, 15);
		
		$records = M("customer_extend_record")->alias("cer")
		->join("customer_extend ce", "ce.customer_extend_id = cer.customer_extend_id")
		->join("customer c", "c.customer_id = ce.customer_id")
		->join("order_goods og", "og.order_number = cer.order_number", "LEFT")
		->join("items i", "i.item_id = og.item_id", "LEFT")
		->join("goods g" , "g.goods_id = i.goods_id", "LEFT")
		->field("cer.commission,cer.date_add,cer.order_number, " .
				" (case when og.item_id = 0  then concat('充值',og.quantity, '元') else GROUP_CONCAT(concat(g.name, ' * ', og.quantity) SEPARATOR ',')  end) as goods_name")
		->where($map)
		->limit($page->firstRow . ',' . $page->listRows)
		->group("cer.order_number")
		->select();
		$customer = M("customer_extend")
		->alias("ce")
		->join("customer c", "c.customer_id = ce.customer_id")
		->field("c.nickname, ce.level, ce.commission, ce.pid,ce.customer_extend_id")
		->where(['ce.customer_extend_id' => $id])
		->find();
		$this->assign("customer", $customer);
		$this->assign("records", $records);
		$this->assign("page", $page->show());
		$this->display();
	}
	
	public function groups(){
		$groups = M("customer_group")->select();
		$this->assign("groups", $groups);
		$this->display();
	}
	
	public function add_group(){
		$id = I("id");
		if(IS_POST){
			$upgrade = (int)I("upgrade");
			(!$name = I("name")) && $this->error("请传入名字");
			$privilege = I("privilege");
			$requirement = I("requirement");
			$data = ['upgrade' => $upgrade,'name' => $name, 'privilege' => $privilege, 'requirement' => $requirement];
			if($id){
				M("customer_group")->where(['group_id' => $id])->save($data);
			}else{
				M("customer_group")->add($data);
			}
			\app\library\SettingHelper::set_levels();
			$this->success("操作成功");
		}
		
		$group = M("customer_group")->getEmptyFields();
		if(!empty($id)){
			$group = M("customer_group")->where(['group_id' => $id])->find();
		}
		$this->assign("group", $group);
		$this->display();
	}
	
	public function del_group(){
		(!$id = I("id")) && $this->error("请选择等级");
		if($id <= 3){
			$this->error("此3项为内置等级，不可删除");
		}
		M("customer_group")->delete($id);
	}
	
	
	public function send_my_message(){


		$uuid = I("uuid");
		$title = I("title");
		$type = I("type");

		if(empty($title)||$title=="undefined") {
			$title="";
		}
		$content = trim(I("content"));
		if(empty($content)||$content=="undefined") {
			$this->error("请传入发送内容");
		}
		$client = \app\library\message\MessageClient::getInstance();
		$message = new \app\library\message\Message();
		if($uuid==0)
		{
			$message
				->setTargetType(\app\library\message\Message::TARGET_ALL)
				->setPushExtra(['title' => $title,'content' => $content]);

		}elseif (!($uuid>0))
		{
			$this->error("请正确传入id");
		}else{
			$customer = M("customer")->where(['uuid'=>$uuid])->find();
			$customer_id = $customer['customer_id'];
			$message
				->setTargetType(\app\library\message\Message::TARGET_CUSTOMERS)
				->setTargetIds($customer_id)
				->setPushExtra(['title' => $title,'content' => $content]);
		}



		$message->setPlatform(\app\library\message\Message::PLATFORM_MESSAGE);
		//设置发送形式为站内信

		$client->pushCache($message);
		$this->success("发送成功");

	}


	public function send_message_to_tags(){
		
		$received_tags = I('tags_id');
		if (empty($received_tags)){
			$this->error("请传入tags_id");
		}
		$received_tags = explode(',',$received_tags);
		$tags = [];
		foreach ($received_tags as $l){
			$l = intval($l);
			if($l>0){
				$tags[] = $l;
			}
		}
		if(empty($tags)){
			$this->error("请正确传入tags_id");
		}

		
		$received_tags_is_and = I("tags_is_and");
		$tags_is_and = 0;
		if(!empty($received_tags_is_and) && $received_tags_is_and == 1){
			$tags_is_and = 1;
		}
		

		



	}


}