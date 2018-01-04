<?php
namespace app\admin\controller;
use app\admin\Admin;
class Transfer extends Admin
{
	public function index()
	{
		$this->display();
	}
	
	public function get_all_avater(){
		$limit = (int)I("limit");
		$offset = (int)I("offset");
		$customers = M("customer")->field("customer_id , avater")->where(['avater is not null'])->order("customer_id")->limit($offset . "," .$limit )->select();
		$upload = \app\library\UploadHelper::getInstance();
		foreach ($customers as &$c){
			$res = $upload->download_image($c['avater']);
			if($res['errcode'] == 0){
				$c['avater'] = $res['content']['name'];
			}
		}
		$headArr = ['customer_id', 'avater'];
		$this->getExcel("customer", $headArr, $customers);
		
		
	}
	
	private function getcustomer($open_id){
		$customer = S("bear_transfer_" . $open_id);
		if(empty($customer)){
			return null;
			//$customer = M("customer")->where(['wx_gz_openid' => $open_id])->field("customer_id,agent_id ,wx_gz_openid as open_id ")->find();
			//$customer = serialize($customer);
			//S("bear_transfer_". $open_id, $customer);
		}
		return unserialize($customer);
	}
	
	public function get_customer1(){
		$customer = $this->getcustomer("oyWn4wcPQ9max1uzn93Xstg8qVD8");
		var_dump($customer);
	}
	
	private function get_excel_data(){
		ini_set('max_execution_time',864000);
		
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
		$file_name = $dir . "/" . date("YmdHis") . rand(1000, 9999) . ".xlsx";
		if(!copy($file, $file_name)){
			$this->error("文件操作失败");
		}
		
		$objReader = \PHPExcel_IOFactory::createReader('Excel2007');
		$objPHPExcel = $objReader->load($file_name);
		$sheet = $objPHPExcel->getSheet(0);
		$highestRow = $sheet->getHighestRow();
		
		return [$highestRow, $sheet];
	}
	
	public function transfer_c(){
		$customer = M("customer")->field("customer_id,agent_id ,wx_gz_openid as open_id")->limit(100)->select();
		foreach($customer as $c){
			S("bear_transfer_" . $c['open_id'], serialize($c));
			var_dump(S("bear_transfer_" . $c['open_id']));
		}
		var_dump("sucess");
	}
	
	
	public function transfer_customer(){
		$data = $this->get_excel_data();
		$length = $data[0];
		$sheet = $data[1];
		$datas = [];
		$rows = [];
		$order_sns = [];
		for ($i = 1 ; $i <= $length; $i ++){
			$customer_id = $sheet->getCell("A$i")->getValue();
			$level = $sheet->getCell("E$i")->getValue() == 0 ? 1 : 3;
			$agent_id = $sheet->getCell("F$i")->getValue();
			$open_id = $sheet->getCell("G$i")->getValue();
			$realname = $sheet->getCell("H$i")->getValue();
			$phone = $sheet->getCell("I$i")->getValue();
			$weixin = $sheet->getCell("K$i")->getValue();
			$date_add = $sheet->getCell("O$i")->getValue();
			$nickname = $sheet->getCell("V$i")->getValue();
			$birthday = $sheet->getCell("Y$i")->getValue() ;
			if(!empty($birthday) ){
				$birthday = strtotime("$birthday-{$sheet->getCell("Z$i")->getValue()}-{$sheet->getCell("AA$i")->getValue()}");
			}
			$sex = $sheet->getCell("AB$i")->getValue() == 1 ? "男" : '女' ;
			$avater = $sheet->getCell("AC$i")->getValue();
			$province = $avater = $sheet->getCell("AD$i")->getValue();
			$city = $sheet->getCell("AE$i")->getValue();
			$commission = $sheet->getCell("AL$i")->getValue();
			$data = ['customer_id' => $customer_id,
					'uuid' => build_uuid(),
					'access_token' => token(),
					'group_id' => $level,
					'agent_id' => $agent_id,
					'wx_gz_openid' => $open_id,
					'realname' => $realname,
					'phone' => $phone,
					'weixin' => $weixin,
					'date_add' => $date_add,
					'nickname' => $nickname,
					'birthday' => $birthday,
					'sex' => $sex,
					'avater' => $avater,
					'province' => $province,
					'city' => $city,
					'commission' => $commission
			];
			$datas[] = $data;
			S("bear_transfer_" . $open_id , serialize(['customer_id' => $customer_id ,"agent_id" => $agent_id, "open_id" => $open_id]));
		}
		$header = ['customer_id', 'uuid', 'access_token', 'group_id', 'agent_id' , 'wx_gz_openid', "realname", "phone",'weixin'
				,'date_add','nickname', 'birthday','sex','avater','province','city', 'commission'
		];
		$this->getExcel("test", $header, $datas);
	}
	
	public function transfer_emoji(){
		$data = $this->get_excel_data();
		$length = $data[0];
		$sheet = $data[1];
		for ($i = 2 ; $i <= $length; $i++){
			$customer_id = $sheet->getCell("A$i")->getValue();
			$nickname = $sheet->getCell("V$i")->getValue();
			if($this->has_emoji($nickname)){
				M("customer")->where(['customer_id' => $customer_id])->save(['nickname' => $nickname]);
			}
		}
	}
	
	public function transfer_fans(){
		$data = $this->get_excel_data();
		$length = $data[0];
		$sheet = $data[1];
		
		$datas = [];
		for ($i = 2 ; $i <= 10000 ; $i++){
			$openid = $sheet->getCell("E$i")->getValue();
			$follow = $sheet->getCell("I$i")->getValue();
			$date = $sheet->getCell("J$i")->getValue();
			$customer_id = $this->getcustomer($openid);
			if(empty($customer_id)){
				continue;
			}
			$customer_id = $customer_id['customer_id'];
			$data = ['customer_id' => $customer_id, 'is_subscribe' => $follow, 'date_subscribe' => $date];
			$datas[] = $data;
		}
		$header = ['customer_id', 'is_subscribe', 'date_subscribe'];
		$this->getExcel("fans", $header, $datas);
	}
	public function qrcode(){
		$customer = M("customer")->where(['group_id' => ['gt' ,2]])->field("uuid,ticket,qrcode,");
		$config = \app\library\SettingHelper::get_pay_params(4);
		$client = \app\library\weixin\WeixinClient::getInstance($config);
		foreach($customer as &$c){
			if(empty($c['tickect'])){
				$result = $client->create_qrcode($c['uuid']);
				if(!empty($result) && isset($result['ticket'])){
					M("customer")->where(['uuid' => $c['uuid']])->save(['ticket' => $result['tickect']]);
				}
			}
		}
	}
	
	public function transfer_cate(){
		$data = $this->get_excel_data();
		$length = $data[0];
		$sheet = $data[1];
		for ($i = 2 ; $i <= $length ; $i++){
			$id = $sheet->getCell("A$i")->getValue();
			$name = $sheet->getCell("C$i")->getValue();
			$pid = $sheet->getCell("E$i")->getValue();
			$level = $sheet->getCell("M$i")->getValue();
			$data = [
					'category_id' => $id,
					'pid' => $pid,
					'name' => $name,
					'level' => $level,
					'active' => 1
			];
			M('category')->add($data);
		}
	}
	public function transfer_goods(){
		$data = $this->get_excel_data();
		$length = $data[0];
		$sheet = $data[1];
		for ($i = 2 ; $i <= $length ; $i++){
			$id = $sheet->getCell("A$i")->getValue();
			$pcate = $sheet->getCell("C$i")->getValue();
			$ccate = $sheet->getCell("D$i")->getValue();
			$sort = $sheet->getCell("G$i")->getValue();
			$name = $sheet->getCell("H$i")->getValue();
			$pic = $sheet->getCell("I$i")->getValue();
			$content = $sheet->getCell("L$i")->getValue();
			$price = $sheet->getCell("O$i")->getValue();
			$marketprice = $sheet->getCell("P$i")->getValue();
			$stock = $sheet->getCell("S$i")->getValue();
			$virtual_sale = $sheet->getCell("U$i")->getValue(); 
			$sale = $sheet->getCell("V$i")->getValue();
			$date_add = $sheet->getCell("X$i")->getValue();
			$max_once_buy = $sheet->getCell("AA$i")->getValue();
			$max_buy = $sheet->getCell("AB$i")->getValue();
			$click_count = $sheet->getCell("AO$i")->getValue();
			$deleted = $sheet->getCell("AP$i")->getValue();
			
			$has_commission = $sheet->getCell("AQ$i")->getValue();
			$commission1_rate = $sheet->getCell("AR$i")->getValue();
			$commission1_fee = $sheet->getCell("AS$i")->getValue();
			$commission2_rate = $sheet->getCell("AT$i")->getValue();
			$commission2_fee = $sheet->getCell("AU$i")->getValue();
			$commission3_rate = $sheet->getCell("AV$i")->getValue();
			$commission3_fee = $sheet->getCell("AW$i")->getValue();
			
			$data = ['goods_id' =>$id,
					'category_id' => $pcate,
					'price' => $price,
					'sort' => $sort,
					'name' => $name,
					'cover' => $pic,
					'market_price' => $marketprice,
					'quantity' => $stock,
					'virtual_count' => $virtual_sale,
					'sale_count' => $sale,
					'is_deleted' => $deleted,
					'mini_name' => $name,
					'has_commission' => $has_commission,
					'click_count' => $click_count,
					'unique_commission' => 1,
					'on_sale' => 1,
					'goods_type' => 1
			];
			M("goods")->add($data);
			if($ccate){
				$data = ['goods_id' => $id , 'category_id' => $ccate];
				M("category_goods")->add($data);
			}
			if($pcate){
				$data = ['goods_id' => $id , 'category_id' => $pcate];
				M("category_goods")->add($data);
			}
		}
	}
	
	
	public function transfer_order(){
		$data = $this->get_excel_data();
		$length = $data[0];
		$sheet = $data[1];
		$pays = M("pay_type")->select();
		$pays = array_key_arr($pays, "pay_id");
		$pay_map = ['0' => 0,'1' => '1', '11' => 6,'21' => 4,'22' => 5,'23' => 7];
		$state_map = ['-1' => 6 ,'0' => 1,'1' => 2 ,'2' => 3, '3' => 5, '4' =>  7,'5' =>8 ];
		$express = M("express")->select();
		$express_map = array_key_arr($express, "code");
		
		$datas = [];
		
		$t = 1;
		for ($i = 2 ; $i <= $length ; $i++){
			$id = $sheet->getCell("A$i")->getValue();
			$openid = $sheet->getCell("C$i")->getValue();
			$order_sn = $sheet->getCell("E$i")->getValue();
			$order_amount = $sheet->getCell("F$i")->getValue();
			$goods_amount = $sheet->getCell("G$i")->getValue();
			$state = $sheet->getCell("I$i")->getValue();
			$pay = $sheet->getCell("J$i")->getValue();
			$comment = $sheet->getCell("L$i")->getValue();
			$express_fee = $sheet->getCell("N$i")->getValue();
			$change_fee = $sheet->getCell("AY$i")->getValue();
			$date_add = $sheet->getCell("P$i")->getValue();
			$finish_time = $sheet->getCell("X$i")->getValue();
			$pay_time = $sheet->getCell("Y$i")->getValue();
			$express_sn = $sheet->getCell("AA$i")->getValue();
			$express = $sheet->getCell("AB$i")->getValue();
			$express_id = "";
			if(!empty($express) && isset($express_map[$express])){
				$express_id = $express_map[$express][0]['express_id'];
			}
			$send_time = $sheet->getCell("AC$i")->getValue();
			$receive_time = $sheet->getCell("AD$i")->getValue();
			$cancel_time = $sheet->getCell("AF$i")->getValue();
			$refund_time = $sheet->getCell("AH$i")->getValue();
			$address = $sheet->getCell("AV$i")->getValue();
			$change_fee = $sheet->getCell("AY$i")->getValue();
			$address_id = 0;
			
			$customer_id = 0;
			if(!$this->getcustomer($openid)){
				continue;
			}
			
			/*if(!empty($address)){
				$address = unserialize($address);
				$data = ['name' => $address['realname'],
						'phone' => $address['mobile'],
						'address' => $address['address'],
						'province' => $address['province'],
						'city' => $address['city'],
						'district' => $address['area']
				];
				$zone = M("zone")
				->alias("p")
				->where(['p.name' => $address['province']])
				->join("zone c","c.pid = p.zone_id and c.name = '{$address['city']}'","LEFT")
				->join("zone a", "a.pid = c.zone_id and a.name = '{$address['area']}'","LEFT")
				->field("p.zone_id as province_id , c.zone_id as city_id , a.zone_id as district_id")
				->find();
				if(!empty($zone)){
					$data = array_merge($data, $zone);
				}
				$address_id = M("order_address")->add($data);
			}*/
			
			$data = [
					'id' => $id,
					'address_id' => $t ++,
					'customer_id' => $this->getcustomer($openid)['customer_id'],
					'order_sn' => $order_sn,
					'out_order_sn' => $order_sn,
					'order_amount' => $order_amount,
					'goods_amount' => $goods_amount,
					'org_amount' => $goods_amount + $express_fee,
					'express_amount' => $express_fee,
					'change_amount' => $change_fee,
					'pay_id' => $pay_map[$pay],
					'order_state' => $state_map[$state],
					'date_add' => $date_add,
					'date_end' => $date_add + 30 * 60,
					'date_pay' => $pay_time,
					'date_send' => $send_time,
					'date_received' => $receive_time,
					'date_cancel' => $cancel_time,
					'date_refund' => $refund_time,
					'date_finish' => $finish_time,
					'express_sn' => $express_sn,
					'express_id' => $express_id,
					'express' => $express
			];
			
			$datas[] = $data;
			
			$header = ["id","address_id","customer_id", "order_sn", "out_order_sn","order_amount",
					"goods_amount","org_amount","express_amount","change_amount","pay_id","order_state",
					"date_add","date_end","date_pay","date_send","date_received","date_cancel","date_refund",
					"date_finish","express_sn","express_id","express"
			];
			
			//M("order")->add($data);
			
			/*$pay_name = "未知支付";
			
			if(isset($pays[$pay_map[$pay]])){
				$pay_name = $pays[$pay_map[$pay]][0]['name'];
			}
			
			$data = ['order_sn' => $order_sn,'order_type' => 1,'foregin_infos' => $order_sn,
					'customer_id' => $this->getcustomer($openid)[0],
					'pay_id' => $pay_map[$pay],
					'pay_name' => $pay_name,
					'state' => empty($pay_time) ? 1 : 2,
					'order_amount' => $order_amount,
					'goods_amount' => $goods_amount,
					'date_add' => $date_add,
					'date_pay' => $pay_time
			];
			
			M("order_info")->add($data);*/
			
		}
		
		$this->getExcel("order", $header, $datas);
	}
	
	public function transfer_order_goods(){
		$data = $this->get_excel_data();
		$length = $data[0];
		$sheet = $data[1];
		
		$datas = [];
		for($i = 1 ; $i <= $length ; $i++){
			$order_id = $sheet->getCell("C$i")->getValue();
			$goods_id = $sheet->getCell("D$i")->getValue();
			$price = $sheet->getCell("E$i")->getValue();
			$quantity = $sheet->getCell("F$i")->getValue();
			$option_id = 0;
			$commission1 = $sheet->getCell("J$i")->getValue();
			
			if(!empty($commission1)){
				$commission1 = unserialize($commission1);
				if(!empty($commission1) && isset($commission1['default']) && is_int($commission1['default'])){
					$commission1 = $commission1['default'];
				}
			}
			
			$state1 = $sheet->getCell("P$i")->getValue();
			
			
			$commission2 = $sheet->getCell("R$i")->getValue();
				
			if(!empty($commission2)){
				$commission2 = unserialize($commission2);
				if(!empty($commission2) && isset($commission2['default']) && is_int($commission2['default'])){
					$commission2 = $commission2['default'];
				}
			}
			
			$state2 = $sheet->getCell("X$i")->getValue();
			
			$commission3 = $sheet->getCell("Z$i")->getValue();
			
			if(!empty($commission3)){
				$commission3 = unserialize($commission3);
				if(!empty($commission3) && isset($commission3['default']) && is_int($commission3['default'])){
					$commission3 = $commission3['default'];
				}
			}
				
			$state3 = $sheet->getCell("AF$i")->getValue();
			$data = [
					'order_id' => $order_id,
					'goods_id' => $goods_id,
					'quantity' => $quantity,
					'price' => $price,
					'option_id' => 0,
					'commission1' => $commission1,
					'state1' => $state1,
					'commission2' => $commission2,
					'state2' => $state2,
					'commission3' => $commission3,
					'state3' => $state3
			];
			
			$datas[] = $data;
		}
		
		$header = ['order_id', 'goods_id', 'quantity' , 'price', 'option_id', 'commission1', 'state1'
				,'commission2', 'state2','commission3' , 'state3'
		];
		
		$this->getExcel("qweqw", $header, $datas);
	}
	
	public function transfer_withdraw(){
		
		$data = $this->get_excel_data();
		$length = $data[0];
		$sheet = $data[1];
		
		$datas = [];
		for($i = 1 ; $i <= $length ; $i++){
			$order_id = $sheet->getCell("C$i")->getValue();
			$goods_id = $sheet->getCell("D$i")->getValue();
			$price = $sheet->getCell("E$i")->getValue();
			$quantity = $sheet->getCell("F$i")->getValue();
			$option_id = 0;
			$commission1 = $sheet->getCell("J$i")->getValue();
				
			if(!empty($commission1)){
				$commission1 = unserialize($commission1);
				if(!empty($commission1) && isset($commission1['default']) && is_int($commission1['default'])){
					$commission1 = $commission1['default'];
				}
			}
				
			$state1 = $sheet->getCell("P$i")->getValue();
				
				
			$commission2 = $sheet->getCell("R$i")->getValue();
		
			if(!empty($commission2)){
				$commission2 = unserialize($commission2);
				if(!empty($commission2) && isset($commission2['default']) && is_int($commission2['default'])){
					$commission2 = $commission2['default'];
				}
			}
				
			$state2 = $sheet->getCell("X$i")->getValue();
				
			$commission3 = $sheet->getCell("Z$i")->getValue();
				
			if(!empty($commission3)){
				$commission3 = unserialize($commission3);
				if(!empty($commission3) && isset($commission3['default']) && is_int($commission3['default'])){
					$commission3 = $commission3['default'];
				}
			}
		
			$state3 = $sheet->getCell("AF$i")->getValue();
			$data = [
					'order_id' => $order_id,
					'goods_id' => $goods_id,
					'quantity' => $quantity,
					'price' => $price,
					'option_id' => 0,
					'commission1' => $commission1,
					'state1' => $state1,
					'commission2' => $commission2,
					'state2' => $state2,
					'commission3' => $commission3,
					'state3' => $state3
			];
				
			$datas[] = $data;
		}
		
		$header = ['order_id', 'goods_id', 'quantity' , 'price', 'option_id', 'commission1', 'state1'
				,'commission2', 'state2','commission3' , 'state3'
		];
		
		$this->getExcel("qweqw", $header, $datas);
	}
	
	public function upload_files(){
		$dir = $_SERVER['DOCUMENT_ROOT']."/upload";
		$this->listDir($dir);
	}
	
	private function listDir($dir){
		if(is_dir($dir))
		{
			if ($dh = opendir($dir))
			{
				while (($file = readdir($dh)) !== false)
				{
					if((is_dir($dir."/".$file)) && $file!="." && $file!="..")
					{
						echo "<b><font color='red'>文件名：</font></b>",$file,"<br><hr>";
						$this->listDir($dir."/".$file."/");
					}
					elseif(strpos($file, "_w=") === false && strpos($file, ".xls") === false && strpos($file, ".xlxs") === false)
					{
						if($file!="." && $file!="..")
						{
							$s = $dir . $file;
							var_dump($dir);
							$index = strpos( $dir , "upload" );
							$str = substr($dir, $index);
							$str = str_replace("//", "/", $str);
							var_dump($str);
							\app\library\UploadHelper::getInstance()->set_cate($str)->setName($file)->upload_image($s);
							echo $file."<br>";
						}
					}
				}
				closedir($dh);
			}
		}
	}
	
	
	
	
}