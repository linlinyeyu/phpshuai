<?php
namespace app\common\model;
use think\Model;
class CustomerModel extends Model{
	public function GetUserInfo($customer_id){

		if(empty($customer_id) || $customer_id < 0){
			return;
		}
		$customer = $this->alias("c")
		->join("customer pc","pc.customer_id = c.agent_id","LEFT")
		->join("customer_group cg","cg.group_id = c.group_id","LEFT")
		->field("c.email,c.province,c.city,c.birthday,c.sex,c.uuid,c.nickname,c.realname, c.avater , c.account, c.phone, c.passwd,c.access_token,c.agent_id,c.last_check_date,".
				"c.active,c.commission, c.group_id,c.hx_id,c.integration,c.shopping_coin,cg.name as group_name, ifnull(pc.nickname,'总店') as pname,c.reward_amount,c.transfer_amount,c.hongfu")
		->where(array("c.customer_id" => $customer_id))->find();

		if(empty($customer)){
			return; 
		}

        $customer['real_phone'] = "";
		if(!empty($customer['phone'])){
			$phone = $customer['phone'];
			$customer['phone'] = substr_replace($phone,'******',3,6);
			$customer['real_phone'] = $phone;
		}
    	$customer['cart_num'] = $this->CartNumber($customer_id);
		$customer['has_pwd'] = !empty($customer['passwd']);
		$customer['has_phone'] = !empty($customer['phone']);
        $customer['has_email'] = !empty($customer['email']);
        unset($customer['passwd']);
		if($customer && $customer['avater'] && strpos($customer['avater'], "http") !== 0){
			$host = \app\library\SettingHelper::get("shuaibo_image_url");
			$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
			$customer['avater'] = $host . $customer['avater'] . $suffix;
		}

//		$customer['unread_count'] = D("message_model") -> getUnReadCount($customer_id);
		return $customer;
	}
	
	//判断是否有该用户
	public function checkCustomer($param,$password,$field){
		$customer = $this->where(["$field" => $param,'passwd' => md5(sha1($password))])->find();
		if (!empty($customer)) {
            return $customer;
        }
        $customer = $this->where(["$field" => $param,'passwd' => $password])->find();
        return $customer;
    }
	
	//更新
	public function mySave($condition,$data){
		return $this->where($condition)->save($data);
	}
	
	//查找
	public function myFind($condtion,$field="*"){
		return $this->field($field)->where($condtion)->find();
	}
	
	public function CartNumber($customer_id){
		if($customer_id){
			return M("cart")
			->alias("c")
			->where(['customer_id' => $customer_id])
			->count();
		}
		return 0;
	}
	
	public function SetCustomer($customer_id){
		return;
		if(empty($customer_id) || $customer_id < 0){
			return;
		}
		$customer = $this->alias("c")
		->join("customer pc","pc.customer_id = c.agent_id","LEFT")
		->join("customer_group cg","cg.group_id = c.group_id","LEFT")
		->field("c.city,c.birthday,c.sex,c.uuid,c.nickname, c.avater , c.account, c.phone, c.passwd,c.access_token,c.agent_id,".
				"c.commission, c.group_id,cg.name as group_name, ifnull(pc.nickname,'总店') as pname,need_push,c.reward_amount,c.hongfu")
				->where(array("c.customer_id" => $customer_id))->find();
		if(empty($customer)){
			return;
		}
		if(!empty($customer['phone'])){
			$phone = $customer['phone'];
			$customer['phone'] = substr_replace($phone,'******',3,6);
		}
	
		$customer['has_pwd'] = !empty($customer['passwd']);
		$customer['has_phone'] = !empty($customer['phone']);
		unset($customer['passwd']);
		if($customer && $customer['avater'] && strpos($customer['image'], "http") !== 0){
			$host = \app\library\SettingHelper::get("bear_image_url");
			$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
			$customer['avater'] = $host . $customer['avater'] . $suffix;
		}
		if($customer){
			$customer['message_count'] = D("message")->getUnReadCount($customer_id);
		}
		$customer = serialize($customer);
		S("bear_customer_info_" . $customer_id, $customer);
	
		return $customer;
	}
	
	public function CalculateMoney($customer_id, $money,$field){
		$res = array(
				'errcode' => 0,
				'message' => '请求成功'
		);
		if($money <= 0){
			$res['message'] = "传入资金为有误";
			$res['errcode'] = 0;
			return $res;
		}
		$condition = array("customer_id" => $customer_id);
		$amount = $this->where($condition)->getField($field);
		if(empty($amount)){
			$res['message'] = "用户信息有误";
			$res['errcode'] = -102;
			return $res;
		}
		if( $amount - $money < 0){
			$res['message'] = "余额不足";
			$res['errcode'] = -103;
			return $res;
		}
		return $res;
	}
	
	public function getRankList($customer_id){
		$host = \app\library\SettingHelper::get("bear_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		$customer = M("customer")
		->where(['customer_id' => $customer_id])
		->field("commission,  avater as image, nickname")
		->find();
		
		if(empty($customer)){
			return "找不到相关人员";
		}
		if($customer['image'] && strpos($customer['image'], "http") !== 0){
			$customer['image'] = $host . $customer['image'] . $suffix;
		}
		
		
		
		//total
		$records = \app\library\SettingHelper::get_rank(0);
		$commission = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","cer.customer_extend_id = ce.customer_extend_id")
		->join("customer c","ce.pid = c.customer_id")
		->where(['cer.state' => ['in', '1,3'], 'c.customer_id' => $customer_id, 'cer.date_add' => ['lt',$records['time']]])
		->sum("cer.commission");
		$commission = empty($commission) ? 0 : $commission;
		/*$count = M("customer")
		 ->alias("c")
		 ->join("customer_extend ce","ce.pid = c.customer_id","LEFT")
		 ->join("customer_extend_record cer","cer.customer_extend_id = ce.customer_extend_id and cer.state in (1,3) and cer.date_add < {$records['time']}","LEFT")
		 ->field("1 as t")
		 ->group("c.customer_id")
		 ->having("ifnull(sum(cer.commission),0) > $commission or (ifnull(sum(cer.commission),0) = $commission and c.customer_id < $customer_id)  "  )
		 ->buildSql();
		 $count = M()->query("select count(1) as c from {$count} as t");
		 $count = $count[0]['c'];
		 $count = empty($count) ? 0 : $count;
		 $count ++;*/
		
		$total_ranks = $records['total_rank'];
		
		$count = "1000+";
		if(isset($total_ranks[$customer_id])){
			$count = $total_ranks[$customer_id][0]['i'];
		}
		
		$total = ['count' => $count, 'commission' =>$commission,  'record' => array_slice($records['rank'], 0 , 20)];
		
		
		
		$records = \app\library\SettingHelper::get_rank(1);
		
		$last_monday = strtotime("last monday");
		if($records['time'] < $last_monday){
			$last_monday = strtotime("-1 week last monday");
		}
		$commission = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","cer.customer_extend_id = ce.customer_extend_id")
		->join("customer c","ce.pid = c.customer_id")
		->where(['c.customer_id' => $customer_id, 'cer.date_add' => [['lt',$records['time']],['gt' , $last_monday]]])
		->sum("cer.commission");
		
		$commission = empty($commission) ? 0 : $commission;
		/*$count = M("customer")
		 ->alias("c")
		 ->join("customer_extend ce","ce.pid = c.customer_id","LEFT")
		 ->join("customer_extend_record cer","cer.customer_extend_id = ce.customer_extend_id AND cer.state in (1,3) AND cer.date_add > $last_monday and cer.date_add < {$records['time']}","LEFT")
		 ->field("count(1) as count")
		 ->group("c.customer_id")
		 ->having("ifnull(sum(cer.commission),0) > $commission or (ifnull(sum(cer.commission),0) = $commission and c.customer_id < $customer_id)  "  )
		 ->buildSql();
		 $count = M()->query("select count(1) as c from {$count} as t");
		 $count = $count[0]['c'];
		 $count = empty($count) ? 0 : $count;
		 $count ++;*/
		
		$total_ranks = $records['total_rank'];
		
		$count = "1000+";
		if(isset($total_ranks[$customer_id])){
			$count = $total_ranks[$customer_id][0]['i'];
		}
		
		$week = ['count' => $count, 'commission' =>$commission,  'record' => array_slice($records['rank'], 0 , 20)];
		
		
		$records = \app\library\SettingHelper::get_rank(2);
		
		$last_month = strtotime(date("Y-m"));
		if($records['time'] < $last_month){
			$last_month = strtotime("-1 month");
			$last_month = strtotime(date("Y-m", $last_month));
		
		}
		$commission = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","cer.customer_extend_id = ce.customer_extend_id")
		->join("customer c","ce.pid = c.customer_id")
		->where(['cer.state' => ['in' , '1,3'], 'c.customer_id' => $customer_id, 'cer.date_add' => [['lt',$records['time']],['gt' , $last_month]]])
		->sum("cer.commission");
		
		$commission = empty($commission) ? 0 : $commission;
		/*$count = M("customer")
		 ->alias("c")
		 ->join("customer_extend ce","ce.pid = c.customer_id","LEFT")
		 ->join("customer_extend_record cer","cer.customer_extend_id = ce.customer_extend_id AND cer.state in (1,3) and  cer.date_add > $last_month and cer.date_add < {$records['time']}","LEFT")
		 ->field("count(1) as count")
		 ->group("c.customer_id")
		 ->having("ifnull(sum(cer.commission),0) > $commission or (ifnull(sum(cer.commission),0) = $commission and c.customer_id < $customer_id)  "  )
		 ->buildSql();
		 $count = M()->query("select count(1) as c from {$count} as t");
		
		 $count = $count[0]['c'];
		 $count = empty($count) ? 0 : $count;
		 $count ++;*/
		
		$total_ranks = $records['total_rank'];
		
		$count = "1000+";
		if(isset($total_ranks[$customer_id])){
			$count = $total_ranks[$customer_id][0]['i'];
		}
		
		$month = ['count' => $count, 'commission' =>$commission,  'record' => array_slice($records['rank'], 0 , 20)];
		
		$records = ['customer' => $customer, 'week' => $week, 'total' => $total , 'month' => $month];
		
		return $records;
	}
	
	public function getCustomerDetail($uuid){
		if(empty($uuid)){
			return "找不到用户";
		}
		
		$total = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->where(['ce.pid = c.customer_id'  ,'cer.state' => 1])
		->field("sum(cer.commission)")
		->buildSql();
		
		$used = M("customer_withdraw")
		->where(['state' => 1, 'customer_id = c.customer_id ' ])
		->field("sum(money)")
		->buildSql();
		
		$count = M("customer")
		->where(['agent_id = c.customer_id'])
		->field('count(customer_id)')
		->buildSql();
		
		$host = $this->host;
		
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		
		$order_count = M("order")
		->where(['customer_id = c.customer_id','order_state' => ['in', "2,3,4,5,7"]])
		->field("count(1)")
		->buildSql();
		
		$customer = M("customer")
		->alias("c")
		->where(['c.uuid' => $uuid])
		->join("customer_group cg","cg.group_id = c.group_id")
		->join("customer pc","pc.customer_id = c.agent_id","LEFT")
		->field("ifnull($total, 0) as total_commission, ifnull($used , 0) as used_commission ,ifnull( $count,0) as count ,ifnull($order_count, 0) as order_count,".
				"c.phone,c.group_id, c.avater as image, c.nickname, ifnull(pc.nickname,'总店') as pname, cg.name as group_name,c.uuid")
				->find();
		if(empty($customer)){
			return "找不到用户";
		}
		if($customer['image'] && strpos($customer['image'], "http") !== 0){
			$customer['image'] = $host . $customer['image'] . $suffix;
		}
		if($customer['order_count'] == 0){
			unset($customer['uuid']);
		}
		return $customer;
	}

}