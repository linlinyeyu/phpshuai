<?php
namespace app\admin\controller;
use app\admin\Admin;
class Customer extends Admin
{
	
	public function index()
	{
		$map = [];
		$name = I("get.name");
		$phone = I("get.phone");
		$min_date = I("get.min_date");
		$max_date = I("get.max_date");
		$sex = I("sex");
		$min_integration = I("min_integration");
		$max_integration = I("max_integration");
        $min_done_money = I("min_done_money");
        $max_done_money = I("max_done_money");
		$min_age = I("min_age");
		$max_age = I("max_age");

		if(!empty($phone)){
			$map['c.phone'] = ['like' , $phone . '%'];
		}
		
		if(!empty($name)){
			$map['_string'] = " (c.nickname like '%$name%' or c.realname like '%$name%')";
		}
		if(!empty($min_date)){
			$map['c.date_add'] = ['egt', strtotime($min_date)];
		}
		if(!empty($max_date)){
			if (!empty($map['c.date_add']) ) {
				$map['c.date_add'][] =  ['elt', strtotime($max_date) ];
			}else{
				$map['c.date_add'] = ['elt', strtotime($max_date)];
			}
		}
		if (!empty($sex)){
			$map['c.sex'] = $sex;
		}
		
		if (!empty($min_integration)){
			$map['c.integration'] = ['egt',$min_integration];
		}
		
		if (!empty($max_integration)){
			if (!empty($min_integration)){
				$map['c.integration'][] = ['elt',$max_integration];
			}else {
				$map['c.integration'] = ['elt',$max_integration];
			}
		}
		
		if (!empty($min_age)){
			$map['c.birthday'] = ['elt',strtotime("-".$min_age." year")];
		}
		
		if (!empty($max_age)){
			if (!empty($min_age)){
				$map['c.birthday'][] = ['egt',strtotime("-".$max_age." year")];
			}else {
				$map['c.birthday'] = ['egt',strtotime("-".$max_age." year")];
			}
		}
		
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

        $count = M('customer')
            ->alias("c")
            ->where($map)
//            ->field("ifnull($done_money,0) as done_money, ifnull($done_count,0) as done_count")
            ->count();

        $page = new \com\Page($count, 15);
		
		$tmp_sql = M("customer")->alias("c")
		->where($map)->order("customer_id desc")
		->limit($page->firstRow . ',' . $page->listRows)
		->field("c.*")->buildSql();
		
		$prefix = C("database.prefix");
//		$users = M("customer")
//            ->alias("c")
//            ->where($map)
//            ->order("customer_id desc")
//            ->limit($page->firstRow . ',' . $page->listRows)
//            ->field("c.*,ifnull($done_money,0) as done_money, ifnull($done_count,0) as done_count")
//            ->select();
		$users = M()->query("select ifnull($done_money,0) as done_money, ifnull($done_count,0) as done_count , t.*" .
				" from {$tmp_sql} t");
		foreach ($users as &$u){
			if($u['avater'] && strpos($u['avater'], "http") !== 0){
				$u['avater'] = $this->image_url . $u['avater'];
			}
			$age = floor((time()-$u['birthday'])/(3600*24*365));
			$u['age'] = $age;
		}
		
		$this->assign('users',$users);
		$this->assign('count',$count);
		$this->assign("page", $page->show());
		$this->display();
	}
	
	public function index_excel(){
		$map = ['a.status' => 1];
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

		$users = M('customer')
		->alias("c")
		->join("address a",'a.customer_id = c.customer_id','left')
		->where($map)
		->group("c.customer_id")
		->order("c.customer_id DESC")
		->field("c.customer_id,c.nickname,c.phone,c.share_code,".
            "ifnull($done_money,0) as done_money,".
            "from_unixtime(c.date_add,'%Y-%m-%d %h:%i:%s'),group_concat(concat(a.province,a.city,a.district,a.address))")
		->buildSql();

		$filename="会员列表";
		$headArr=array("会员号","姓名","电话","推荐号","消费金额","注册日期","收货地址");

        $this->excelData($filename,$users,$headArr);
    }

    /**
    *导入excel
    */
    public function insertExcel(){
    	if (!isset($_FILES['files'])) {
    		$this->error("请传入文件");
    	}
    	if (!$_FILES['files']['tmp_name']) {
    		$this->error("请传入文件");
    	}

    	$file = $_FILES['files']['tmp_name'];
    	$real_file_name = $_FILES['files']['name'];
    	vendor("PHPExcel.Classes.PHPExcel");
    	$dir = $_SERVER['DOCUMENT_ROOT']."/upload/excel";
    	if (!is_dir($dir)) {
    		if(!mkdir($dir,0777,true)){
    		$this->error("创建目录失败");
    		}
    	}
    	$extension = strtolower(pathinfo($real_file_name,PATHINFO_EXTENSION));
    	$file_name = $dir."/".date("YmdHis").rand(1000,9999).".".$extension;
    	if (!copy($file,$file_name)) {
    		$this->error("文件操作失败");
    	}
    	$objReader = null;
    	if ($extension == 'xlsx') {
    		$objReader = \PHPExcel_IOFactory::createReader("Excel2007");
    	}elseif ($extension == 'xls') {
    		$objReader = \PHPExcel_IOFactory::createReader("Excel5");
    	}elseif ($extension == 'csv') {
    		$objReader = \PHPExcel_IOFactory::createReader("CSV");
    	}

    	$objPhpExcel = $objReader->load($file_name);
    	$sheet = $objPhpExcel->getSheet(0);
    	$highRow = $sheet->getHighestRow();
    	$highColumn = $sheet->getHighestColumn();

    	$datas = [];
    	$rows = [];
    	$prefix = C("database.prefix");
    	$customer = array();
    	for($i = 2;$i<= $highRow;$i++){
    		$nickname = $sheet->getCell("A$i")->getValue();
    		$realname = $sheet->getCell("B$i")->getValue();
    		$password = $sheet->getCell("C$i")->getValue();
    		$phone = $sheet->getCell("D$i")->getValue();
    		$share_code = $sheet->getCell("E$i")->getValue();
    		$access_token = token();
    		$customer[] = array('nickname' => $nickname."lg",'realname' => $realname,'passwd' => $password,'phone' => $phone,'share_code' => $share_code,'access_token' => $access_token);
    	}

    	$res = M("customer")->addAll($customer);
    	if ($res === false) {
    		$this->error("导入失败");
    	}
    	$this->success("导入成功",null,U("customer/index"));
    }
	
	public function disable()
	{
		!($id = (int)I('get.id')) && $this->error('参数错误');
		$name = D("customer")->getCustomerName($id);
		$this->log("禁止用户：" .$name);
		$res = M('customer')->where(['customer_id'=>$id])->save(['active'=>0]);
		if ($res === false){
			$this->error("失败");
		}
		$seller = M("seller_shopinfo")->where(['customer_id' => $id])->find();
		if (!empty($seller)){
			$res = M("seller_shopinfo")->where(['customer_id' => $id])->save(['status' => 0]);
			if ($res === false){
				$this->error("失败");
			}
			$res = M("seller_user")->where(['customer_id' => $id])->save(['status' => 0]);
			if ($res === false){
				$this->error("失败");
			}
		}
		$this->success("成功");
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
			
			M("customer_withdraw")->where(['customer_id' => $id])->delete();
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
		$res = M('customer')->where(['customer_id'=>$id])->save(['active'=>1]);
		if ($res === false){
			$this->error("失败");
		}
		$seller = M("seller_shopinfo")->where(['customer_id' => $id])->find();
		if(!empty($seller)){
			$res = M("seller_shopinfo")->where(['customer_id' => $id])->save(['status' => 1]);
			if ($res === false){
				$this->error("失败");
			}
			$res = M("seller_user")->where(['customer_id' => $id])->save(['status' => 1]);
			if ($res === false){
				$this->error("失败");
			}
		}
		
		$this->success("成功");
	}
	
	public function edit(){
		if (IS_POST){
			$phone = I("phone");
			$account = round(I('account'),2);
            $integration = round(I('integration'),2);
            $shopping_coin = round(I('shopping_coin'),2);
            $hongfu = round(I('hongfu'),2);
            $reward_amount = round(I('reward_amount'),2);
            $transfer_amount = round(I('transfer_amount'),2);
            $id = I("id");

            $data = [
                'phone' => $phone,
                'account' => $account,
                'integration' => $integration,
                'shopping_coin' => $shopping_coin,
                'hongfu' => $hongfu,
                'reward_amount' => $reward_amount,
                'transfer_amount' => $transfer_amount,
            ];

            $customer = M("customer")->where(['customer_id' => $id])->find();
            M("customer")->where(['customer_id' => $id])->save($data);

			$data = [];
			if (floatval($customer['account']) != $account){
			    $data[] = array(
			        'customer_id' => $id,
                    'finance_type_id' => 3,
                    'type' => 13,
                    'amount' => $account,
                    'date_add' => time(),
                    'comments' => '后台设置余额为'.$account,
                    'title' => $account,
                );
            }
            if (floatval($customer['integration']) != $integration){
			    $data[] = array(
			        'customer_id' => $id,
                    'finance_type_id' => 1,
                    'type' => 16,
                    'amount' => $integration,
                    'date_add' => time(),
                    'comments' => '后台设置积分为'.$integration,
                    'title' => $integration,
                );
            }
            if (floatval($customer['shopping_coin']) != $shopping_coin){
                $data[] = array(
                    'customer_id' => $id,
                    'finance_type_id' => 2,
                    'type' => 16,
                    'amount' => $shopping_coin,
                    'date_add' => time(),
                    'comments' => '后台设置购物积分为'.$shopping_coin,
                    'title' => $shopping_coin,
                );
            }
            if (floatval($customer['hongfu']) != $hongfu){
                $data[] = array(
                    'customer_id' => $id,
                    'finance_type_id' => 4,
                    'type' => 16,
                    'amount' => $hongfu,
                    'date_add' => time(),
                    'comments' => '后台设置鸿府积分为'.$hongfu,
                    'title' => $hongfu,
                );
            }
            if (floatval($customer['reward_amount']) != $reward_amount){
                $data[] = array(
                    'customer_id' => $id,
                    'finance_type_id' => 3,
                    'type' => 14,
                    'amount' => $reward_amount,
                    'date_add' => time(),
                    'comments' => '后台设置奖励金为'.$reward_amount,
                    'title' => $reward_amount,
                );
            }
            if (floatval($customer['transfer_amount']) != $transfer_amount){
                $data[] = array(
                    'customer_id' => $id,
                    'finance_type_id' => 3,
                    'type' => 15,
                    'amount' => $transfer_amount,
                    'date_add' => time(),
                    'comments' => '后台设置奖励金为'.$transfer_amount,
                    'title' => $transfer_amount,
                );
            }

            if (!empty($data)){
                M("finance")->addAll($data);
            }
			$this->log("修改会员信息: " .M("customer")->where(['customer_id' => $id])->getField("nickname")." phone: ".$phone." account: ".$account." integration: ".$integration." shopping_coin: ".$shopping_coin);
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
		->join("order o","o.customer_id = c.customer_id and o.order_state in (2,3,4,5,7)","left")
		->where($map)
		->group("c.customer_id")
		->field("c.* ,count(o.id) as order_count, ifnull(sum(o.order_amount),0) as order_amount")
		->find();
		
		if($customer['avater'] && strpos($customer['avater'], "http") !== 0){
			$customer['avater'] = $this->image_url . $customer['avater'];
		}
		
		$this->assign('customer',$customer);
		$this->assign('need_pwd', $need_pwd);
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
			!($name = I("post.name")) && $this->error("请输入昵称");
			$phone = I("post.phone");
			if (!empty($phone) || $phone != '') {
                M("customer")->where(['phone' => $phone])->count() > 0 && $this->error("手机号已被注册");
            }
			M("customer")->where(['nickname' => $name])->count() > 0 && $this->error("昵称已被使用");
			$data = ['phone'=>$phone,'nickname' => $name, 'passwd' => md5(sha1($pwd))];
			$data['date_add'] = time();
			$data['date_upd'] = time();
			$data['reg_ip'] = get_client_ip();
			$data['last_ip'] = $data['reg_ip'];
			$data['last_area'] = get_location($data['reg_ip']);
			$data['uuid'] = build_uuid();
			$data['access_token'] = token();
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

    /**
     * 积分明细
     */
	public function inteDetail(){
		$id = I("id");
		$customer = M("customer")->where(['customer_id' => $id])->find();
		$count = M("finance")
            ->where(['finance_type_id' => 1,'customer_id' => $id])
            ->count();

		$page = new \com\Page($count,15);
		$integration = M("finance")
            ->where(['finance_type_id' => 1,'customer_id' => $id])
            ->order("date_add DESC")->limit($page->firstRow.",".$page->listRows)
            ->select();
		
		$this->assign("integration",$integration);
		$this->assign("customer",$customer);
		$this->assign("page",$page->show());
		$this->display();
	}

    /**
     * 会员积分明细
     */
    public function coinDetail(){
        $id = I("id");
        $customer = M("customer")->where(['customer_id' => $id])->find();
        $count = M("finance")
            ->where(['finance_type_id' => 2,'customer_id' => $id])
            ->count();

        $page = new \com\Page($count,15);
        $shopping_coin = M("finance")
            ->where(['finance_type_id' => 2,'customer_id' => $id])
            ->order("date_add DESC")->limit($page->firstRow.",".$page->listRows)
            ->select();

        $this->assign("shopping_coin",$shopping_coin);
        $this->assign("customer",$customer);
        $this->assign("page",$page->show());
        $this->display();
    }

    /**
     * 鸿府积分明细
     */
    public function hongfuDetail(){
        $id = I("id");
        $customer = M("customer")->where(['customer_id' => $id])->field("realname,hongfu")->find();
        $count = M("finance")
            ->where(['finance_type_id' => 4,'customer_id' => $id])
            ->count();
        $page = new \com\Page($count,15);
        $hongfu = M("finance")
            ->where(['finance_type_id' => 4,'customer_id' => $id])
            ->order("date_add desc")
            ->limit($page->firstRow.",".$page->listRows)
            ->select();

        $this->assign("hongfu",$hongfu);
        $this->assign("customer",$customer);
        $this->assign("page",$page->show());
        $this->display();
    }

    /**
     * 余额明细
     */
    public function accountDetail() {
        $id = I("id");
        $customer = M("customer")->where(['customer_id' => $id])->find();
        $count = M("finance")
            ->where(['finance_type_id' => 3,'customer_id' => $id])
            ->count();

        $page = new \com\Page($count,15);
        $account = M("finance")
            ->where(['finance_type_id' => 3,'customer_id' => $id])
            ->order("date_add DESC")->limit($page->firstRow.",".$page->listRows)
            ->select();

        $this->assign("account",$account);
        $this->assign("customer",$customer);
        $this->assign("page",$page->show());
        $this->display();
    }

    /**
     * 重置密码
     */
	public function reset_passwd() {
        $id = I("id");
        $customer = M("customer")->where(['customer_id' => $id])->find();
        if (empty($customer)) {
            $this->error("未找到对应用户");
        }
        M('customer')->where(['customer_id' => $id])->save(['passwd' => md5(sha1('888888'))]);
        // 同步lg修改密码
        $LG = new \app\api\LGApi();
        $lg_user = $LG->getUsername($customer['nickname']);
        if ($lg_user == true) {
            $res = $LG->EditUpdatePass([
                'userid' => $lg_user,
                'userpass' => '888888',
                'passtype' => 1
            ]);
            $res = json_decode($res['return_content'],true);
            if ($res['code'] != 0) {
                $this->error($res['message']);
            }
        }
        $this->success('成功');
    }
    /**
    *冻结金额
    */
    public function frozen(){
    	$customer_id = I("id");
    	$customer = M("customer")->where(['customer_id' => $customer_id])->find();
        if (empty($customer)) {
            $this->error("未找到对应用户");
        }

        M("customer")->where(['customer_id' => $customer_id])->save(['is_frozen' => 1]);

        $this->success("冻结成功");
    }

    /**
    *解除冻结金额
    */
    public function unfrozen(){
    	$customer_id = I("id");
    	$customer = M("customer")->where(['customer_id' => $customer_id])->find();
        if (empty($customer)) {
            $this->error("未找到对应用户");
        }

        M("customer")->where(['customer_id' => $customer_id])->save(['is_frozen' => 0]);
        $this->success("解除成功");
    }

    /**
    *冻结全部金额
    */
    public function frozen_all(){
    	$res = M("customer")->save(['is_frozen' => 1]);
    	if ($res === false) {
    		$this->error("冻结失败");
    	}
    	$this->success("冻结成功");
    }
}
	