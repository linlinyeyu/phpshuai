<?php
namespace app\app\controller;
class Commission
{
	public function getrecords(){
		$goods_id = (int)I("goods_id") ;
		$customer_id = get_customer_id();
		$page = (int)I("page");
		if(empty($customer_id)){
			return ['errcode' => 99 , 'message' => '请重新登录'];
		}
		
		if($goods_id <= 0){
			return ['errcode' => -101 ,'message' => '请传入商品信息'];
		}
		
		if($page <= 0){
			$page = 1;
		}
		
		$records = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->join("customer c","c.customer_id = ce.customer_id")
		->join("customer_group cg", "c.group_id = cg.group_id")
		->join("order o","o.id = cer.order_id")
		->where(['cer.goods_id' => $goods_id, "ce.pid" => $customer_id])
		->field("c.nickname,cer.date_add ,o.id, cer.commission , cg.name as group_name, o.date_received, o.order_state")
		->order("cer.date_add desc")
		->limit(20)
		->page($page)
		->select();
		foreach ($records as &$r){
			$r['end'] = $r['date_received'] - time();
			$r['refund'] = $r['order_state'] == 8 ? 1: 0;
			
			$r['text'] = "";
			if($r['order_state'] == 8){
				$r['text'] = "已退款";
			}else{
				$end = $r['date_received'] - time();
				if($end > 24 * 60 * 60){
					$r['text'] = intval($end / 24 / 60 / 60) . "天后可兑换";
				}else if($end > 60 * 60){
					$r['text'] = intval($end / 60 / 60) . "小时后可兑换";
				}else if($end > 60 ){
					$r['text'] = intval($end / 60 ) . "分后可兑换";
				}else if($end > 0){
					$r['text'] = intval($end) . "秒后可兑换";
				}
			}
			
			unset($r['date_received']);
			unset($r['order_state']);
		}
		
		
		
		return ['errcode' => 0 ,'message' =>'请求成功', 'content' => $records];
	}
	
	public function excharge_records(){
		$goods_id = (int)I("goods_id") ;
		$customer_id = get_customer_id();
		$page = (int)I("page");
		if(empty($customer_id)){
			return ['errcode' => 99 , 'message' => '请重新登录'];
		}
		
		if($goods_id <= 0){
			return ['errcode' => -101 ,'message' => '请传入商品信息'];
		}
		
		if($page <= 0){
			$page = 1;
		}
		
		$records = M("customer_withdraw")
		->alias("cw")
		->field("cw.state,cw.date_add,cw.account,cw.type, case when cw.type != 2 then '微信账号' else '支付宝账号' end as pay_name,cw.money")
		->where(['cw.customer_id' => $customer_id,'goods_id' => $goods_id])
		->order("cw.date_add desc")
		->limit(20)
		->page($page)
		->select();

		return ['errcode' => 0 ,'message' => '请求成功', 'content' => $records];
	}
	
	public function withdraw(){
		
		$customer_id = get_customer_id();

        $account_id = I("account_id");

        if (!$account_id){
            return ['errcode' => -101 , 'message' => '请选择支付宝账号'];
        }

        $customer_account = M("customer_account")
            ->where(['account_id' => $account_id, 'customer_id' => $customer_id])
            ->find();
        if (empty($customer_account)){
            return ['errcode' => -102 , 'message' => '未添加支付宝账号'];
        }

        $score = (double)I("score");
		
		if(!$customer_id){
			return ['errcode' => 99 , 'message' => '请重新登录'];
		}

		$withdraw = \app\library\SettingHelper::get("bear_commission_withdraw",['is_open' => 1, 'weixin' => 1, 'min_withdraw' => 100 , 'min_audit' => 500 , 'min_date' => 7 ]);
		
		if($withdraw['is_open'] == 0){
			return ['errcode' => -109 , 'message' => '提现系统暂不开放'];
		}
		
		$min_date = time() - $withdraw['min_date'] * 24 * 60 * 60;

		$customer = M("customer")->where(['customer_id' => $customer_id])->field("active, phone")->find();
		
		if(!$customer || $customer['active'] == 0){
			$seller_info = \app\library\SettingHelper::get("shuaibo_seller_info",['qq' => '123456789','address' => '杭州市']);
        	return ['errcode' => -106, 'message' => '抱歉，您无法执行此操作，如有疑问，请拨打客服电话:' . $seller_info['qq']];
		}
		if(!$customer['phone']){
			return ['errcode' => -107 , 'message' =>'抱歉，您需先绑定手机号，方可提现'];
		}
		
		$total_sql = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->where(['cer.state' => 1, 'ce.pid' => $customer_id])
		->field("sum(cer.commission) as commission")
		->buildSql();

		$used_sql = M("customer_withdraw")
		->where(['customer_id' => $customer_id, 'state' => ['in',"0,1"]])
		->field("sum(money)")
		->buildSql();

		$commission = M()->query("select IFNULL({$total_sql},0) as total, IFNULL({$used_sql},0) as used from dual");
        if(empty($commission)){
            return ['errcode' => -101 ,'message' => '没有佣金信息'];
        }
		$commission = $commission[0];

		$available = $commission['total'] - $commission['used'] ;
		
		if($score  > $available){
			return ['errcode' => -107 ,'message' =>'可提现佣金不足'];
		}
		
		if($withdraw['min_withdraw'] > $score){
			return ['errcode' => -103 ,'message' =>'最小提现金额为：' . $withdraw['min_withdraw']];
		}
		
		$order_sn = createNo("customer_withdraw","order_sn", "CA");

		$rate = \app\library\SettingHelper::get("bear_withdraw_rate", 0.9);

		$data = [
				'order_sn' => $order_sn,
				'state' => 0,
				'money' => $score,
                'real_money' => $score * $rate,
				'date_add' => time(),
				'customer_id' => $customer_id,
				'account' => $customer_account['pay_account'],
				'realname' => $customer_account['pay_account_name'],
				'type' => 2,
		];
		$id = M("customer_withdraw")->add($data);
        return ['errcode' => 0,'message'=>'支付宝兑换到账时间为1~3个工作日，您可在兑换记录中查看兑换是否成功','title' => '已提交支付宝兑换申请' ];

	}
	
	public function get_customer_record(){
		$customer_id = get_customer_id();
		if(!$customer_id){
			return ['errcode' => 99 , 'message' => '请重新登录'];
		}
		$records = D("customer_extend_record")->getCustomerRecord($customer_id);
		return ['errcode' => 0 , 'message' => '请求成功', 'content' => $records];
	}
}