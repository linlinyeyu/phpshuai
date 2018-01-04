<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Orders extends Admin
{
	public function index()
	{
		echo 'hello';
	}

	public function lists()
	{
		$state = (int)I("get.state");
		
		$map = " 1 = 1 and io.seller_id = ".session("sellerid");
		$map .=" AND io.order_state = ". $state;
		$state == 0 && $map = " 1 = 1 and io.seller_id = ".session("sellerid");
		I('get.min_date') && $map .= " AND io.date_add >= ".strtotime(I('get.min_date')) . " ";
		I("get.max_date") && $map .=" AND io.date_add <= ".(strtotime(I("get.max_date")))  . " ";
		I('get.order_number') && $map .=" AND io.order_sn like '".trim(I('get.order_number')) . "%' ";
		I("get.pay_id") && $map .= " AND io.pay_id = " . I("get.pay_id") . " ";
		
		if(I("get.goods_id")){
			$goods_sql = M("order_goods")
			->where(['order_id = io.id','goods_id' => I("goods_id")])
			->field("1 as t")
			->buildSql();
			$map .=" AND exists $goods_sql";
		}
		
		if(I("get.customer_name") || I("get.phone") || I("get.uuid") ){
			$customer_map = " ic.customer_id = io.customer_id ";
			I('get.customer_name') && $customer_map .=" AND (ic.nickname like '%".trim(I('get.customer_name'))."%' OR ic.realname like '%" .I("get.customer_name") . "%') ";
			I("get.phone") && $customer_map .= " AND ic.phone like '".trim(I("get.phone")). "%'";
			I("get.uuid") && $customer_map .= " AND ic.uuid like '".I("uuid")."%'";
				
			$customer_sql = M("customer")->alias("ic")->where($customer_map)->field("1 as s")->buildSql();
				
			$map .=" AND exists $customer_sql";
		}
		
		if(I("address_name") || I("address_phone")){
			$address_map = "io.address_id = ioa.id " ;
			I("get.address_name") && $address_map .=" AND ioa.name like '%".I("get.address_name")."%' ";
			I("get.address_phone") && $address_map .=" AND ioa.phone like '".I("get.address_phone")."%' ";
			$address_sql = M("order_address")->alias("ioa")->where($address_map)->field("1 as s")->buildSql();
			$map .= " AND exists $address_sql";
		}
		
		$count=M('order')
		->alias('io')
		->where($map)
		->count();
		
		$page = new \com\Page($count, 20);

		$prefix = C('database.prefix');

		$order_sql = M("order")->alias("io")
		->where($map)
		->order("io.date_add DESC")
		->limit($page->firstRow . ',' . $page->listRows)
		->buildSql();
		$lists = M()->query("select o.*,os.name as state,c.uuid,concat(oa.province,oa.city,oa.district,oa.address) as address, ifnull(pt.name,'未支付') as pay_name, c.nickname,c.account,oa.name as address_name,oa.phone,ore.remind_order_id " .
		" FROM {$order_sql} as o left join {$prefix}order_state os on os.order_state_id = o.order_state ".
		" left join {$prefix}order_address oa on oa.id = o.address_id " .
		" LEFT JOIN {$prefix}pay_type pt on pt.pay_id = o.pay_id " .
		" LEFT JOIN {$prefix}customer c on c.customer_id = o.customer_id" .
        " LEFT JOIN {$prefix}order_remind ore on ore.order_id = o.id" .
        " GROUP BY o.id ORDER BY o.date_add DESC");
		
		$ids = [];
		foreach ($lists as $l){
			$ids[] = $l['id'];
		}
		if(!empty($ids)){
			$order_goods = M("order_goods")
			->alias("og")
			->join("goods g","g.goods_id = og.goods_id","LEFT")
            ->join("goods_option go","go.id = og.option_id","LEFT")
            ->join("order_return ore","ore.order_id = og.order_id AND ore.goods_id = og.goods_id AND ore.option_id = og.option_id","LEFT")
            ->join("order_return_state ors","ors.order_return_state_id = ore.state","LEFT")
			->field("og.order_id,GROUP_CONCAT(g.name) as goods_names,GROUP_CONCAT( g.cover) as images,GROUP_CONCAT(og.price) as prices, GROUP_CONCAT(og.quantity) as quantitys,".
                "GROUP_CONCAT(IFNULL(go.name,'默认')) as option_name,".
                "GROUP_CONCAT(IFNULL(ore.state,0)) as refund_state,GROUP_CONCAT(IFNULL(ors.name,'无')) as refund_state_name,
                group_concat(g.address) as address")
			->group("og.order_id")
			->where(['og.order_id' => ['in', $ids]])
			->select();
			$order_goods = array_key_arr($order_goods, "order_id");
		}
		
		foreach ($lists as  &$l){
			if(!isset($order_goods[$l['id']])){
				$l['goods'] = [];
				continue;
			}
			$goods_name = explode(",", $order_goods[$l['id']][0]['goods_names']);
			$price = explode(",", $order_goods[$l['id']][0]['prices']);
			$quantity = explode(",", $order_goods[$l['id']][0]['quantitys']);
			$images = explode(",", $order_goods[$l['id']][0]['images']);
            $option_name = explode(",", $order_goods[$l['id']][0]['option_name']);
            $refund_state = explode(",", $order_goods[$l['id']][0]['refund_state']);
            $refund_state_name = explode(",", $order_goods[$l['id']][0]['refund_state_name']);
            $address = explode(",",$order_goods[$i['id']][0]['address']);
            $goods = [];
			for ($i = 0; $i< count($goods_name); $i++){
				$goods[] = [
						'goods_name' => $goods_name[$i],
						'price' => $price[$i],
						'quantity' => $quantity[$i],
						'image' => $images[$i],
                        'option_name' => $option_name[$i],
                        'refund_state' => (int)$refund_state[$i],
                        'refund_state_name' => $refund_state_name[$i],
                        'address' => $address[$i]
				];
			}
			$l['goods'] = $goods;
		}
		$states = M("order_state")->alias("os")
		->join("order o","o.order_state = os.order_state_id and o.seller_id = ".session("sellerid"),"LEFT")
		->group("os.order_state_id")
		->field("os.order_state_id as id,count(o.id) as count,os.name")
		->order("os.order_state_id")
		->where(['1 = 1'])
		->select();
		
		$express= \app\library\SettingHelper::get_express();
		
		$count = M("order")->where(['seller_id' => session("sellerid")])->count();
		$goods = M("goods")->where(['is_delete' => 0])->field("name, goods_id")->select();
		$this->assign("goods", $goods);
		$this->assign("express",$express);
		$this->assign("count", $count);
		$this->assign("state", $state);
		$this->assign("states", $states);
		$this->assign('page',$page->show());
		$this->assign('lists',$lists);
		
		$this->display();
	}
	
	public function changeprice(){
		!($id = I("id")) && $this->error("请传入参数");
		$price = (double)I("price");
		$order = M("order")->where(['id' => $id])->field("org_amount,order_sn,order_state")->find();
		empty($order) && $this->error("找不到对应订单");
		$order['order_state'] != 1 && $this->error("订单必须为未支付订单");
		$order['org_amount'] + $price <= 0 && $this->error("订单金额不能小于0");
		M("order")->where(['id' => $id])->save(['change_amount' => $price, 'order_amount' =>$order['org_amount'] +  $price]);
		$this->log("订单号:{$order['order_sn']}修改订单金额：{$price},最终价格为：".($order['org_amount'] + $price));
		$this->success("修改成功");
	}
	
	public function confirm_pay(){
		!($id = I("id")) && $this->error("请传入参数");
		$order = M("order")->where(['id' => $id])->find();
		empty($order) && $this->error("找不到对应订单");
		$order['order_state'] != 1 && $this->error("订单必须为未支付订单");
		$handler = new \app\library\handler\GoodsHandler();
		$res = $handler->orderNotify(['foregin_infos' => $order['order_sn'],'pay_id' => 6, 'customer_id' => $order['customer_id']]);
		$this->log("订单号：{$order['order_sn']}后台支付成功");
		$this->success("操作成功");
	}

    public function send_carrier(){
        !($id = I("id")) && $this->error("请传入参数");
        !($type = I("type")) && $this->error("请传入类型");
        $carrier = I("carrier");
        $shipping = I("shipping");
        $address = I("address");

        $order = M("order")
			->alias("o")
			->join("order_goods og","og.order_id = o.id")
			->join("goods g","g.goods_id = og.goods_id")
			->where(['o.id' => $id])
			->group("o.id")
			->field("order_state, order_sn, GROUP_CONCAT(g.mini_name) as mini_name, customer_id, GROUP_CONCAT(og.goods_id) as goods_ids,GROUP_CONCAT(og.option_id) as option_ids,id as order_id")
			->find();
			$order['order_state'] != 2 && $this->error("订单状态必须为待发货");
			$goods_ids = explode(",",$order['goods_ids']);
			$option_ids = explode(",",$order['option_ids']);
			for ($i = 0; $i < count($goods_ids); $i++) {
			    $refund_state = M('order_return')
	                ->where(['order_id' => $order['order_id'],'goods_id' => $goods_ids[$i],'option_id' => $option_ids[$i]])
	                ->getField("state");
			    if (!empty($refund_state) && !in_array($refund_state,[2,5,6])) {
	                $this->error("该订单还有未处理的退款订单，请先处理退款订单");
	            }
	        }
	    $order_settings = \app\library\SettingHelper::get("shuaibo_order_settings",['received' => 7]);
        if ($type == 1) {
            !$carrier && $this->error("请传入快递类型");
            !$shipping && $this->error("请传入运单号");
            $express = M("express")->where(['express_id' => $carrier])->find();
			empty($express) && $this->error("快递类型有误");
			empty($order) && $this->error("订单号有误");
			
			M("order")->where(['id' => $id])->save([
					'express_id' => $carrier,
					'express_sn' => $shipping,
					'express' => $express['code'],
	                'express_type' => $type,
	                'order_state' => 3,
					'date_send' => time(),
					'date_end' => time() + $order_settings['received'] * 24 * 60 * 60,
					'date_received' => time() + $order_settings['received'] * 24 * 60 * 60,
					'user_id' => session("userid")
			]);
			$client = \app\library\message\MessageClient::getInstance();
			$message = new \app\library\message\Message();
			$message->setAction(\app\library\message\Message::ACTION_EXPRESS)
			->setPlatform([\app\library\message\Message::PLATFORM_PUSH,\app\library\message\Message::PLATFORM_MESSAGE])
			->setTargetIds($order['customer_id'])
			->setTemplate("send")
			->setExtras(['order_sn' => $order['order_sn'],'type' => 2])
			->setPushExtra([
			    'title' => '订单发货通知',
	            'content' => "您购买的{$order['mini_name']}已发货，快递为：{$express['express_name']}{$shipping}",
	            'express_sn' => $shipping,
	            'express' => $express['code'],
	        ]);
			$client->send($message);
			
			$this->log("订单号：{$order['order_sn']}发货，物流为：{$express['express_name']},运单号：{$shipping}");
        }elseif ($type == 2) {
        	if (empty($address)) {
        		$this->error("请传入自提地址");
        	}
        	M("order")->where(['id' => $id])->save([
        		'express_type' => $type,
        		'order_state' => 3,
        		'date_send' => time(),
        		'date_end' => time() + $order_settings['received'] * 24 * 60 * 60,
        		'date_received' => time() + $order_settings['received'] * 24 * 60 * 60,
        		'user_id' => session("userid"),
        		'address' => $address
        	]);

        	$client = \app\library\message\MessageClient::getInstance();
			$message = new \app\library\message\Message();
			$message->setAction(\app\library\message\Message::ACTION_EXPRESS)
			->setPlatform([\app\library\message\Message::PLATFORM_PUSH,\app\library\message\Message::PLATFORM_MESSAGE])
			->setTargetIds($order['customer_id'])
			->setTemplate("send")
			->setExtras(['order_sn' => $order['order_sn'],'type' => 2])
			->setPushExtra([
			    'title' => '订单发货通知',
	            'content' => "您购买的{$order['mini_name']}已发货，自提地址为：{$address}"
	        ]);
			$client->send($message);
			
			$this->log("订单号：{$order['order_sn']}发货，自提地址为{$address}");
        }
		
		$this->success("操作成功");
    }
	
	public function details()
	{
		(!$order_number = I('get.number')) && $this->error('参数错误');
		
		$order = M("order")
		->alias("o")
		->join("customer c","c.customer_id = o.customer_id")
		->join("express e","e.express_id = o.express_id","LEFT")
		->join("order_state os","os.order_state_id = o.order_state")
		->join("order_address oa","oa.id = o.address_id","LEFT")
		->join("order_return orr","orr.order_id = o.id","LEFT")
        ->join("customer_coupon cco","cco.customer_coupon_id = o.coupon_id","LEFT")
        ->join("coupon co","co.coupon_id = cco.coupon_id","LEFT")
		->where(['o.order_sn' => $order_number])
		->field("o.*,c.nickname, c.avater,os.name as state,e.express_name,".
				" oa.name as address_name,oa.phone,concat(oa.province,oa.city,oa.district,oa.address) as address,".
				" orr.state as refund_state,orr.date_add as date_refund, orr.reason,orr.price,orr.remark,".
                "co.amount as coupon_amount")
		->find();

        $pay_amount = $order['order_amount'] + $order['express_amount'] - $order['coupon_amount'] - $order['max_integration'] - $order['max_shopping_coin'];
        $order["pay_amount"] = number_format($pay_amount,2,'.','');
		
		if($order['avater'] && strpos($order['avater'], "http") !== 0){
			$order['avater'] = $this->image_url . $order['avater'];
		}
		
		$order_goods = M("order_goods")
		->alias("og")
		->join("order o","o.id = og.order_id")
		->join("goods g","g.goods_id = og.goods_id")
		->where(['o.order_sn' => $order_number])
		->field("g.name ,og.quantity, og.option_name,og.goods_id,og.price")
		->select();
		
		$express= \app\library\SettingHelper::get_express();
		$this->assign("express",$express);
		$this->assign("order", $order);
		$this->assign("order_goods", $order_goods);
		$this->display(); 
	}
	
	public function batch_carrier(){
		
		$map = " 1=1";
		$map .=" AND o.order_state = 2 and o.seller_id = ".session("sellerid");
		I('get.min_date') && $map .= " AND o.date_add >= ".strtotime(I('get.min_date')) ;
		I("get.max_date") && $map .=" AND o.date_add <= ".(strtotime(I("get.max_date"))) ;
		I('get.order_number') && $map .=" AND o.order_sn = '".trim(I('get.order_number')) . "'";
		I('get.customer_name') && $map .=" AND c.nickname like '%".trim(I('get.customer_name'))."%'";
		I("get.phone") && $map .= " AND c.phone like '".trim(I("get.phone")). "%'";
		$count=M('order')
		->alias('o')
		->join('customer c','o.customer_id=c.customer_id')
		->where($map)->count();
		$page = new \com\Page($count, 20);
		
		
		$lists = M('order')
		->alias('o')
		->join("order_goods og","og.order_id = o.id")
		->join("goods g","g.goods_id = og.goods_id", "LEFT")
		->join("order_state os","os.order_state_id = o.order_state")
		->join("order_address oa","oa.id = o.address_id")
		->join("pay_type pt","pt.pay_id = o.pay_id","LEFT")
		->field("o.*,os.name as state,c.uuid,concat(oa.province,oa.city,oa.district,oa.address) as address, ifnull(pt.name,'未支付') as pay_name, c.nickname,c.account,oa.name as address_name,oa.phone,".
				"GROUP_CONCAT(g.name) as goods_names,GROUP_CONCAT( g.cover) as images,GROUP_CONCAT(og.price) as prices, GROUP_CONCAT(og.quantity) as quantitys")
		->where($map)
		->order("o.date_add DESC")
		->join('customer c','o.customer_id=c.customer_id')
		->limit($page->firstRow . ',' . $page->listRows)
		->group("o.id")
		->select();
		
		foreach ($lists as &$l){
			$goods_name = explode(",", $l['goods_names']);
			$price = explode(",", $l['prices']);
			$quantity = explode(",", $l['quantitys']);
			$images = explode(",", $l['images']);
			$goods = [];
			for ($i = 0; $i< count($goods_name); $i++){
				$goods[] = [
						'goods_name' => $goods_name[$i],
						'price' => $price[$i],
						'quantity' => $quantity[$i],
						'image' => $images[$i]
				];
			}
			$l['goods'] = $goods;
		}
		$express= \app\library\SettingHelper::get_express();
		$this->assign("express",$express);
		$this->assign('page',$page->show());
		$this->assign('lists',$lists);
		$this->display();
	}
	
	public function batch_excel(){
		$map = " 1=1";
		$map .=" AND o.order_state = 2";
		I('get.min_date') && $map .= " AND o.date_add >= ".strtotime(I('get.min_date')) ;
		I("get.max_date") && $map .=" AND o.date_add <= ".(strtotime(I("get.max_date"))) ;
		I('get.order_number') && $map .=" AND o.order_sn = '".trim(I('get.order_number')) . "'";
		I('get.customer_name') && $map .=" AND c.nickname like '%".trim(I('get.customer_name'))."%'";
		I("get.phone") && $map .= " AND c.phone like '".trim(I("get.phone")). "%'";
		
		$lists = M('order')
		->alias('o')
		->join("order_goods og","og.order_id = o.id")
		->join("goods g","g.goods_id = og.goods_id", "LEFT")
		->join("order_state os","os.order_state_id = o.order_state")
		->join("order_address oa","oa.id = o.address_id")
		->join("pay_type pt","pt.pay_id = o.pay_id","LEFT")
        ->join("express e","e.express_id = o.express_id","LEFT")
		->field("o.order_sn,oa.name , '' as fax, oa.phone ,concat(oa.province,oa.city,oa.district,oa.address) as address,e.express_name,".
			"concat(case when og.option_name is null then GROUP_CONCAT(g.name) else group_concat(g.name, '(', og.option_name,')') end,' 派送前请电话联系客户务必本人签收') as goods_names,o.comment as remark, '' as price, '' as d , '' as type")
		->where($map)
		->order("o.date_add DESC")
		->join('customer c','o.customer_id=c.customer_id')
		->group("o.id")
		->select();

		foreach ($lists as $k => $l){
			$name = str_replace("'", "", $l['name']);
			$name = str_replace('"', '', $name);
			$lists[$k]['name'] = $name;
			
			$name = str_replace("'", "", $l['address']);
			$name = str_replace('"', '', $name);
			$reg = "[\x3041-\x3093\x30a1-\x30f6]";
			$name = preg_replace_callback($reg, function($s){
				return "";
			}, $name);
			$lists[$k]['address'] = $this->replace_emoji($name);
			
			$name = str_replace("'", "", $l['remark']);
			$name = str_replace('"', '', $name);
			$lists[$k]['remark'] = $this->replace_emoji($name);
		}
		
		$filename="待发货订单";
		$headArr=array("订单号","收件人","固话","手机","地址","快递公司", "发货信息","备注" ,"代收金额", "保价金额","业务类型");
		//$this->export($filename, $lists, $headArr);
		$this->getExcel($filename,$headArr,$lists);
	}
	
	public function batch_send_carrier(){
		if(!isset($_FILES['files'])){
			$this->error("请传入文件");
		}
		if(!$_FILES['files']['tmp_name']){
			$this->error("请传入文件");
		}
		$file = $_FILES['files']['tmp_name'];
		vendor("PHPExcel.Classes.PHPExcel");
		$dir = $_SERVER['DOCUMENT_ROOT']."/upload/excel/";
		if(!mkDirs($dir)){
			$this->error("创建目录失败");
		}
		$file_name = $dir . "/" . date("YmdHis") . rand(1000, 9999) . ".xls";
		if(!copy($file, $file_name)){
			$this->error("文件操作失败");
		}
		
		$objReader = \PHPExcel_IOFactory::createReader('Excel5');
		$objPHPExcel = $objReader->load($file_name);
		$sheet = $objPHPExcel->getSheet(0);
		$highestRow = $sheet->getHighestRow();           //取得总行数
		$highestColumn = $sheet->getHighestColumn();
		
		
		$datas = [];
		$rows = [];
		$order_sns = [];
		
		for ($i = 2 ; $i <= $highestRow; $i ++){
			$order_sn = $sheet->getCell("B$i")->getValue();
			$express = $sheet->getCell("N$i")->getValue();
			$express_sn = $sheet->getCell("O$i")->getValue();
			if(empty($order_sn) || empty($express) || empty($express_sn)){
				$rows[] = $i + 1;
				continue;
			}
			
			$datas[$order_sn] = ['order_sn' => $order_sn, 'express' => $express , 'express_sn' => $express_sn];
			$order_sns[] = $order_sn;
		}
		if(empty($order_sns)){
			$this->error("找不到相关订单号");
		}
		
		$express = M("express")->select();
		$express = array_key_arr($express, 'express_name');
		$orders = M("order")
		->alias("o")
		->join("order_goods og","og.order_id = o.id")
		->join("goods g","g.goods_id  = og.goods_id")
		->join("order_address oa","oa.id = o.address_id")
		->join("customer c","c.customer_id = o.customer_id ")
		->where([ 'o.order_sn' => ['in', $order_sns] , 'o.order_state' => 2])
		->field("o.id,c.wx_gz_openid as openid, concat(oa.name,' ',oa.province,oa.city,oa.district, oa.address) as address, o.id as order_id,o.customer_id , o.order_sn, GROUP_CONCAT(g.mini_name) as mini_name, GROUP_CONCAT(g.name) as names ")
		->group("o.id")
		->select();
		$order_settings = \app\library\SettingHelper::get("bear_order_settings",['received' => 7]);
		$start_time = microtime_float();
		
		$str = "";
		foreach($orders as $k => $o){
			
			if(!isset($express[$datas[$o['order_sn']]['express']])){
				continue;
			}
			
			$express1 = $express[$datas[$o['order_sn']]['express']][0];
			$express_sn = $datas[$o['order_sn']]['express_sn'];
			
			if($k % 500 == 0){
				$prefix = C("database.prefix");
				if(!empty($str)){
					$str = substr($str,0, -1);
					$str .= " on duplicate key update order_state=values(order_state) , ".
					"express_id = values(express_id), express_sn = values (express_sn), ".
					"express = values(express),date_send = values(date_send), date_received = values(date_received), user_id = values(user_id)";
					M()->execute($str);
				}
				
				$str = "insert into {$prefix}order (id, order_state, express_id, express_sn, express,date_send,date_received,user_id) values ";
			}
			
			$str .= "({$o['id']}, 3, '{$express1['express_id']}','{$express_sn}', '{$express1['code']}', ".time()." , ".
			(time() + $order_settings['received'] * 24 * 60 * 60) .", ".session("userid")."),";
			
		}
		
		if(!empty($str)){
			$str = substr($str,0, -1);
			$str .= " on duplicate key update order_state=values(order_state) , ".
					"express_id = values(express_id), express_sn = values (express_sn), ".
					"express = values(express),date_send = values(date_send), date_received = values(date_received), user_id = values(user_id)";
			M()->execute($str);
		}
		
		$cache = [];
		foreach($orders as $k => $o){
			if(!isset($express[$datas[$o['order_sn']]['express']])){
				continue;
			}
			$express1 = $express[$datas[$o['order_sn']]['express']][0];
			$express_sn = $datas[$o['order_sn']]['express_sn'];
			
			$cache[] = ['order_sn' => $o['order_sn'],
					'openid' => $o['openid'],
					'mini_name' => $o['mini_name'],
					'customer_id' => $o['customer_id'],
					'express_name' => $express1['express_name'],
					'express_sn' => $express_sn,
					'names' => $o['names'],
					'address' => $o['address'],
			];
		}
		S("bear_send_orders", serialize($cache));
		$this->success("发货成功");
	}
	
	
	public function excel(){
		$state = (int)I("get.state");
		$map = " o.order_state != 6";
		$map .=" AND o.order_state = ". $state;
		$state == 0 && $map = "o.order_state != 6";
		I('get.min_date') && $map .= " AND o.date_add >= ".strtotime(I('get.min_date')) ;
		I("get.max_date") && $map .=" AND o.date_add <= ".(strtotime(I("get.max_date"))) ;
		I('get.order_number') && $map .=" AND o.order_sn like '".trim(I('get.order_number')) . "%'";
		I('get.customer_name') && $map .=" AND (c.nickname like '%".trim(I('get.customer_name'))."%' OR c.realname like '%" .I("get.realname") . "%') ";
		I("get.phone") && $map .= " AND c.phone like '".trim(I("get.phone")). "%'";
		I("get.goods_id") && $map .=" AND og.goods_id = ". I("get.goods_id") . " ";
		I("get.uuid") && $map .= " AND c.uuid like '".I("uuid")."%'";
		I("get.pay_id") && $map .= " AND o.pay_id = " . I("get.pay_id");
		I("get.address_name") && $map .=" AND oa.name like '%".I("get.address_name")."%' ";
		I("get.address_phone") && $map .=" AND oa.phone like '".I("get.address_phone")."%' ";

		$query_sql = M('order')
		->alias('o')
		->join("order_goods og","og.order_id = o.id")
		->join("goods g","g.goods_id = og.goods_id", "LEFT")
		->join("order_state os","os.order_state_id = o.order_state")
		->join("order_address oa","oa.id = o.address_id")
		->join("pay_type pt","pt.pay_id = o.pay_id","LEFT")
		->join('customer c','o.customer_id=c.customer_id')
		->join("express e","e.express_id = o.express_id","LEFT")
		->field("o.order_sn,os.name as state,c.uuid, c.nickname,c.realname, ifnull(pt.name,'未支付') as pay_name,o.out_order_sn,oa.name as address_name, oa.phone as phone,".
				" concat(oa.province,oa.city,oa.district,oa.address) as address, o.comment, o.order_amount, o.goods_amount, o.express_amount, ".
				"e.express_name, o.express_sn, GROUP_CONCAT( g.name, '×', og.quantity) as goods_names, " .
				"FROM_UNIXTIME(o.date_add) as date_add , FROM_UNIXTIME(o.date_pay) as date_pay,FROM_UNIXTIME(o.date_send) as date_send, ".
				"FROM_UNIXTIME(o.date_received) as date_received, FROM_UNIXTIME(o.date_finish) as date_finish,FROM_UNIXTIME(o.date_refund) as date_refund")
		->where($map)
		->group("o.id")
		->buildSql();

		$headArr = ["订单号","订单状态","用户编号", "昵称",'真实姓名', '支付方式','支付订单号',  "收货人","收货人手机","收货地址", "备注", "订单金额",'商品金额',"运费","快递公司","快递号", '购买商品',"下单日期","支付日期","发货日期","收货日期",'完成日期','退款日期'];

		$this->export("orderlist",$query_sql,$headArr);


//		foreach ($lists as &$l){
//			$l['nickname'] = $this->replace_emoji($l['nickname']);
//		}
//		if(empty($lists)){
//			$this->error("数据为空");
//		}
//		$this->getExcel("订单列表", $headArr, $lists);
	}
	public function batch_alipay_refund(){
		$order = M("order_return")
		->alias("orr")
		->join("order o","o.id = orr.order_id")
		->join("order_info oi","o.out_order_sn = oi.order_sn")
		->field("oi.order_sn ,oi.out_trade_no ,sum(orr.price) as price,GROUP_CONCAT(orr.remark), GROUP_CONCAT(orr.order_return_id) as order_return_id")
		->group("oi.order_id")
		->where([ 'orr.state' => 1 ])
		->select();
		
		$ids = [];
		
		foreach ($order as &$o){
			$returns = explode(",", $o['order_return_id']);
			foreach ($returns as $r){
				$ids[] = $r;
			}
			unset($o['order_return_id']);
		}
		
		$batch_no = date("YmdHis") . rand(1000, 9999);
		
		$c = M("order_return")
		->alias("orr")
		->join("order o","o.id = orr.order_id")
		->join("order_info oi","o.out_order_sn = oi.order_sn")
		->field("'$batch_no' as batch_no, count(distinct(oi.order_sn)) as count, sum(orr.price) as s")
		->where([ 'orr.state' => 1 ])
		->select();
		
		M("order_return")->where(['order_return_id' => ['in', $ids]])->save(['out_trade_no' => $batch_no]);
		
		$headArr1 = ['批次号','总笔数','总金额'];
		
		$headArr2 = ['商户订单号','支付宝交易号','退款金额','退款备注'];
		
		$this->getExcel2("test",[$headArr1 , $headArr2],[$c, $order] );
	}

    public function refund_list(){

        $state = (int)I("get.state");

        $map = " 1=1 and g.seller_id = ".session("sellerid");
        $map .=" AND orr.state = ". $state;
        $state == 0 && $map = " 1=1 and g.seller_id = ".session("sellerid");
        I('get.min_date') && $map .= " AND orr.date_add >= ".strtotime(I('get.min_date')) ;
        I("get.max_date") && $map .=" AND orr.date_add <= ".(strtotime(I("get.max_date"))) ;
        I('get.refund_sn') && $map .=" AND o.refund_sn = '".trim(I('get.refund_sn')) . "'";
        I('get.customer_name') && $map .=" AND c.nickname like '%".trim(I('get.customer_name'))."%'";
        I("get.phone") && $map .= " AND c.phone like '".trim(I("get.phone")). "%'";
        $count = M('order_return')
            ->alias('orr')
            ->join("customer c","c.customer_id = orr.customer_id")
            ->join("goods g",'g.goods_id = orr.goods_id')
            ->where($map)
            ->count();
        $page = new \com\Page($count, 20);

        $lists = M('order_return')
            ->alias("orr")
            ->where($map)
            ->join("order_goods og","og.goods_id = orr.goods_id AND og.order_id = orr.order_id")
            ->join("goods g","g.goods_id = og.goods_id")
            ->join("order_return_state ors","ors.order_return_state_id = orr.state")
            ->join("order o","o.id = orr.order_id")
            ->join("order_address oa","oa.id = o.address_id")
            ->join("pay_type pt","pt.pay_id = o.pay_id","LEFT")
            ->join('customer c','o.customer_id=c.customer_id')
            ->field("orr.*,orr.state as refund_state,ors.name as state,concat(oa.province,oa.city,oa.district,oa.address) as address, ifnull(pt.name,'未支付') as pay_name, c.nickname,c.account,oa.name as address_name,oa.phone,".
                "GROUP_CONCAT(g.name) as goods_names,GROUP_CONCAT( g.cover) as images,GROUP_CONCAT(og.price) as prices, GROUP_CONCAT(og.quantity) as quantitys,".
                "o.order_amount,o.order_sn,o.id,o.address as offaddress")
            ->order("orr.date_add DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->group("orr.order_return_id")
            ->select();



//		$state = 7;
//
//		$map = " 1=1";
//		$map .=" AND o.order_state = ". $state;
//		$state == 0 && $map = "1=1";
//		I('get.min_date') && $map .= " AND o.date_add >= ".strtotime(I('get.min_date')) ;
//		I("get.max_date") && $map .=" AND o.date_add <= ".(strtotime(I("get.max_date"))) ;
//		I('get.order_number') && $map .=" AND o.order_sn = '".trim(I('get.order_number')) . "'";
//		I('get.customer_name') && $map .=" AND c.nickname like '%".trim(I('get.customer_name'))."%'";
//		I("get.phone") && $map .= " AND c.phone like '".trim(I("get.phone")). "%'";
//		$count=M('order')
//		->alias('o')
//		->join('customer c','o.customer_id=c.customer_id')
//		->where($map)->count();
//		$page = new \com\Page($count, 20);
//
//
//		$lists = M('order')
//		->alias('o')
//		->join("order_goods og","og.order_id = o.id")
//		->join("goods g","g.goods_id = og.goods_id", "LEFT")
//		->join("order_state os","os.order_state_id = o.order_state")
//		->join("order_address oa","oa.id = o.address_id")
//		->join("pay_type pt","pt.pay_id = o.pay_id","LEFT")
//		->field("o.*,os.name as state,concat(oa.province,oa.city,oa.district,oa.address) as address, ifnull(pt.name,'未支付') as pay_name, c.nickname,c.account,oa.name as address_name,oa.phone,".
//				"GROUP_CONCAT(g.name) as goods_names,GROUP_CONCAT( g.cover) as images,GROUP_CONCAT(og.price) as prices, GROUP_CONCAT(og.quantity) as quantitys")
//		->where($map)
//		->order("o.date_add DESC")
//		->join('customer c','o.customer_id=c.customer_id')
//		->limit($page->firstRow . ',' . $page->listRows)
//		->group("o.id")
//		->select();
//
        foreach ($lists as &$l){
            $goods_name = explode(",", $l['goods_names']);
            $price = explode(",", $l['prices']);
            $quantity = explode(",", $l['quantitys']);
            $images = explode(",", $l['images']);
            $goods = [];
            for ($i = 0; $i< count($goods_name); $i++){
                $goods[] = [
                    'goods_name' => $goods_name[$i],
                    'price' => $price[$i],
                    'quantity' => $quantity[$i],
                    'image' => $images[$i]
                ];
            }
            $l['goods'] = $goods;
        }

        $states = M("order_return_state")
            ->alias("ors")
            ->join("order_return orr","orr.state = ors.order_return_state_id","LEFT")
            ->join("goods g",'g.goods_id = orr.goods_id and g.seller_id = '.session("sellerid"),"left")
            ->group("ors.order_return_state_id")
            ->field("ors.order_return_state_id as id,count(g.goods_id) as count,ors.name")
            ->order("ors.order_return_state_id")
            ->select();


        $count = M("order_return")
        ->alias("orr")
        ->join("goods g",'g.goods_id = orr.goods_id and g.seller_id = '.session("sellerid"))
        ->count();


        $this->assign("count", $count);
        $this->assign("state", $state);
        $this->assign("states", $states);
        $this->assign('page',$page->show());
        $this->assign('lists',$lists);

        $this->display();
    }

    public function refund(){
        $seller_id = session("sellerid");
        (!$id = I("id")) && $this->error("请传入退款号");
        $state = (int)I("state");
        $reply = I("reply");
        $remark = I("reply_remark");
        $state == 2 && empty($remark) && $this->error("请输入拒绝理由");

        $state == 3 || $state == 2 && empty($reply) && $this->error("请输入处理方式");

        $refund = M('order_return')
            ->alias("orr")
            ->join("order o","o.id = orr.order_id")
            ->join("order_goods og",'og.order_id = orr.order_id AND og.goods_id = orr.goods_id AND og.option_id = orr.option_id')
            ->join("seller_shopinfo ss","ss.seller_id = o.seller_id")
            ->where(['orr.order_return_id' => $id])
            ->field("orr.*,o.order_sn,ss.customer_id as seller_customer_id,".
                "og.max_integration,og.max_shopping_coin,og.max_hongfu")
            ->find();

        if (empty($refund)) {
            $this->error("找不到退款信息");
        }
        if(($state == 2 || $state == 3) && $refund['state'] != 1){
            $this->error("该退款已被处理");
        }
        if ($refund['type'] == 1) {
            // 待发货
            $data = ['state' => $state == 2 ? 2 : 5];
            if ($state == 2) {
                // 拒绝退款
                $data['reply'] = $reply;
                $data['reply_remark'] = $remark;
                $data['date_deal'] = time();
                $data['date_finish'] = time();
            } elseif ($state == 3) {
                // 退款同意
                $data['reply'] = $reply;
                $data['date_deal'] = time();
                $data['date_finish'] = time();
            }
            M("order_return")->where(['order_return_id' => $refund['order_return_id']])->save($data);

            $content = "";
            $data = [
                'order_return_id' => $refund['order_return_id'],
                'title' => "",
                'content' => "",
                'date_add' => time(),
                'type' => 2
            ];
            if($state == 2){
                $data['title'] = "卖家拒绝了您的售后申请";

                $data['content'] = "您的退款申请未通过，原因为：" .$remark;
                $this->log("退款号：" . $refund['refund_sn'] . "拒绝退款");
            }elseif($state == 3){
                $data['title'] = "卖家同意了您的售后申请";
                $data['content'] = "成功退款 ¥".$refund['price'];

                $data['content'] = "您的退款申请已通过";
                $this->log("退款号：" . $refund['refund_sn'] . "申请成功");

                // 余额增加 积分会员积分退回
                $back = ['account' => ['exp', 'account + ' . $refund['price']], 'integration' => ['exp', 'integration + ' . $refund['max_integration']], 'shopping_coin' => ['exp', 'shopping_coin + ' . $refund['max_shopping_coin']],'hongfu' => ['exp','hongfu + '.$refund['hongfu']]];
                M('customer')->where(['customer_id' => $refund['customer_id']])->save($back);
                // 佣金减少
                M('customer')->where(['customer_id' => $refund['seller_customer_id']])->setDec('commission',$refund['price']);

                // 资金明细
                M('finance')->add([
                    'customer_id' => $refund['customer_id'],
                    'finance_type_id' => 3,
                    'type' => 4,
                    'amount' => $refund['price'],
                    'date_add' => time(),
                    'is_minus' => 2,
                    'order_sn' => $refund['refund_sn'],
                    'comments' => '退款 '.$refund['refund_sn'],
                    'title' => '+'.$refund['price'],
                ]);
                if ($refund['max_integration'] > 0) {
                    // 积分
                    M('finance')->add([
                        'customer_id' => $refund['customer_id'],
                        'finance_type_id' => 1,
                        'type' => 4,
                        'amount' => $refund['max_integration'],
                        'date_add' => time(),
                        'is_minus' => 2,
                        'order_sn' => $refund['refund_sn'],
                        'comments' => '退款 '.$refund['refund_sn'],
                        'title' => '+'.$refund['max_integration'],
                    ]);
                }

                if ($refund['max_shopping_coin'] > 0) {
                    // 会员积分
                    M('finance')->add([
                        'customer_id' => $refund['customer_id'],
                        'finance_type_id' => 2,
                        'type' => 4,
                        'amount' => $refund['max_shopping_coin'],
                        'date_add' => time(),
                        'is_minus' => 2,
                        'order_sn' => $refund['refund_sn'],
                        'comments' => '退款 '.$refund['refund_sn'],
                        'title' => '+'.$refund['max_shopping_coin'],
                    ]);
                }

                if ($refund['max_hongfu'] > 0){
                    //鸿府积分
                    M("finance")->add(array(
                       "customer_id" => $refund['customer_id'],
                       'finance_type_id' => 4,
                       'type' => 4,
                        'amount' => $refund['max_hongfu'],
                        'date_add' => time(),
                        'is_minus' => 2,
                        'order_sn' => $refund['refund_sn'],
                        'comments' => '退款'.$refund['refund_sn'],
                        'title' => "+".$refund['max_hongfu']
                    ));
                }

                M('finance_op')->add([
                    'customer_id' => $refund['seller_customer_id'],
                    'finance_type' => 4,
                    'amount' => $refund['price'],
                    'date_add' => time(),
                    'is_minus' => 1,
                    'order_sn' => $refund['refund_sn'],
                    'comments' => '退款 '.$refund['refund_sn'],
                    'title' => '-'.$refund['price'],
                ]);

                M('customer_withdraw_order')->where(['order_sn' => $refund['order_sn']])->setDec('commission_amount',$refund['price']);
            }

            M('order_return_message')->add($data);
        } else {
            $data = ['state' => $state];

            if ($state == 2) {
                // 拒绝退款
                $data['reply'] = $reply;
                $data['reply_remark'] = $remark;
                $data['date_deal'] = time();
                $data['date_finish'] = time();
            } elseif ($state == 3) {
                // 退款同意
                $data['reply'] = $reply;
                $data['date_deal'] = time();
            } else {
                $data['date_finish'] = time();
            }
            M("order_return")->where(['order_return_id' => $refund['order_return_id']])->save($data);

            $content = "";
            $data = [
                'order_return_id' => $refund['order_return_id'],
                'title' => "",
                'content' => "",
                'date_add' => time(),
                'type' => 2
            ];
            if($state == 2){
                $data['title'] = "卖家拒绝了您的售后申请";
                $data['content'] = "您的退款申请未通过，原因为：" .$remark;
                $this->log("退款号：" . $refund['refund_sn'] . "拒绝退款");
            }elseif($state == 3){
                $data['title'] = "卖家同意了您的售后申请";
                if (in_array($refund['type'],[2,3])){
                    $data['content'] = "请填写您所退货物的物流单号信息，\n商家确认收货后您将收到退款 ¥" . $refund['price'];
                }elseif ($refund['type'] == 4) {
                    $data['content'] = "请退货到自提点，\n商家确认收货后您将受到退款 ¥".$refund['price'];
                    M("order_return")->where(['order_return_id' => $id])->save(['state' => 7]);
                }
                $data['content'] = "您的退款申请已通过";
                $this->log("退款号：" . $refund['refund_sn'] . "申请成功");
            } else {
                $data['title'] = "卖家已确认收到退货";
                $data['content'] = "成功退款 ¥".$refund['price'];

                // 余额增加
                M('customer')->where(['customer_id' => $refund['customer_id']])->setInc('account',$refund['price']);
                // 佣金减少
                M('customer')->where(['customer_id' => $refund['seller_customer_id']])->setDec('commission',$refund['price']);

                // 资金明细
                M('finance')->add([
                    'customer_id' => $refund['customer_id'],
                    'finance_type_id' => 3,
                    'type' => 4,
                    'amount' => $refund['price'],
                    'date_add' => time(),
                    'is_minus' => 2,
                    'order_sn' => $refund['refund_sn'],
                    'comments' => '退款 '.$refund['refund_sn'],
                    'title' => '+'.$refund['price'],
                ]);

                if ($refund['max_integration'] > 0) {
                    // 积分
                    M('finance')->add([
                        'customer_id' => $refund['customer_id'],
                        'finance_type_id' => 1,
                        'type' => 4,
                        'amount' => $refund['max_integration'],
                        'date_add' => time(),
                        'is_minus' => 2,
                        'order_sn' => $refund['refund_sn'],
                        'comments' => '退款 '.$refund['refund_sn'],
                        'title' => '+'.$refund['max_integration'],
                    ]);
                }

                if ($refund['max_shopping_coin'] > 0) {
                    // 会员积分
                    M('finance')->add([
                        'customer_id' => $refund['customer_id'],
                        'finance_type_id' => 2,
                        'type' => 4,
                        'amount' => $refund['max_shopping_coin'],
                        'date_add' => time(),
                        'is_minus' => 2,
                        'order_sn' => $refund['refund_sn'],
                        'comments' => '退款 '.$refund['refund_sn'],
                        'title' => '+'.$refund['max_shopping_coin'],
                    ]);
                }

                M('finance_op')->add([
                    'customer_id' => $refund['seller_customer_id'],
                    'finance_type' => 4,
                    'amount' => $refund['price'],
                    'date_add' => time(),
                    'is_minus' => 1,
                    'order_sn' => $refund['refund_sn'],
                    'comments' => '退款 '.$refund['refund_sn'],
                    'title' => '-'.$refund['price'],
                ]);

                M('customer_withdraw_order')->where(['order_sn' => $refund['order_sn']])->setDec('commission_amount',$refund['price']);
            }

            M('order_return_message')->add($data);

        }
        
        // 检测对否需要关闭订单
        $this->checkRefund($refund['order_id']);


//		$template = $state == 2 ? "refund2" : "refund3";
//		$client = \app\library\message\MessageClient::getInstance();
//
//		$message = new \app\library\message\Message();
//		$message->setAction(\app\library\message\Message::ACTION_ORDER)
//		->setPlatform([ \app\library\message\Message::PLATFORM_ALL])
//		->setTargetIds($order['customer_id'])
//		->setExtras(['order_sn' => $order['order_sn'], 'title' => '退款通知', 'content' => $content])
//		->setWeixinExtra(['order' => [ 'goods_name' => $order['goods_name'], 'price' => $order['price']]])
//		->setTemplate($template);
//
//		$client->pushCache($message);



        $this->success("处理成功");
    }
	
	public function refund_code(){
		$user_id = session("userid");
		
		$count = M("refund_code")->order("date_add desc")->where(['user_id' => $user_id])->count();
		
		$page = new \com\Page($count , 15);
		$refund_code = M("refund_code")
		->where(['user_id' => $user_id])
		->limit($page->firstRow . "," . $page->listRows)
		->order("date_add desc")
		->select();
		
		$expire = \app\library\SettingHelper::get("bear_refund_code_expire", 10);
		
		foreach( $refund_code as &$r){
			if($r['state'] == 0 && $r['date_add'] + $expire * 60 < time()){
				$r['state'] = 2;
			}
		}
		
		$this->assign("expire", $expire);
		$this->assign("refund_code", $refund_code);
		$this->assign("page", $page->show());
		$this->display();
	}
	
	public function add_refund_code(){
		$user_id = session("userid");
		$code = rand(100000, 999999);
		$data = [
				'user_id' => $user_id,
				'code' => $code,
				'date_add' => time(),
				'state' => 0,
		];
		M("refund_code")->add($data);
		$this->success("生成成功",$code);
	}
	
	public function order_settings(){
		if(IS_POST){
			!($received = I("received")) && $this->error("请输入收货间隔时间");
			!($pay_expire = I("pay_expire")) && $this->error("请输入订单过期时间");
			$info = ['received' => $received , 'pay_expire' => $pay_expire];
			\app\library\SettingHelper::set("bear_order_settings", $info);
			$this->success("操作成功");
		}
		
		$settings = \app\library\SettingHelper::get("bear_order_settings",['received' => 7,'pay_expire' => 30]);
	
		$this->assign("settings", $settings);
		$this->display();
	}

    private function checkRefund($order_id) {
        // TODO 检测订单内商品是否都退款成功 若是 整个订单关闭
        $goods_count_sql = "SELECT COUNT(og.order_goods_id) FROM vc_order_goods as og WHERE og.order_id = ".$order_id;
        $refunds = M('order_return')
            ->alias("orr")
            ->where(['orr.order_id' => $order_id,'orr.state' => ['in','5']])
            ->field("COUNT(orr.order_return_id) as refund_count,($goods_count_sql) as goods_count")
            ->find();
        if ($refunds['refund_count'] == $refunds['goods_count']) {
            M("order")->where(['id' => $order_id])->save(['order_state' => 6, 'date_end' => time()]);
        }
    }
}