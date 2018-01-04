<?php
namespace app\common\model;
use think\Model;
class CustomerExtendRecord extends Model{

	public function record($extend_id, $commission,$goods_id, $order_id , $state = 1){
		$data = [
				'customer_extend_id' => $extend_id,
				'commission' => $commission,
				'date_add' => time(),
				'goods_id' => $goods_id,
				'order_id' => $order_id,
				'state' => $state
		];
		$this->add($data);
	}
	
	public function getCustomerRecord($customer_id){
		$total = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->where(['cer.state' => ['in', "1,3"], 'ce.pid' => $customer_id])
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
		->where(['g.is_deleted' => 0 , 'cer.state' => ['in', "1,3"],  'ce.pid' => $customer_id
				,'_string' => " (cer.date_add > $min_date or (o.order_state = 3 or o.order_state = 2 or o.order_state = 7 ) )" ])
				->sum("cer.commission");
		
		$total_balance = empty($total_balance) ? 0 : $total_balance;
		
		
		$total = ['total' => $total, 'used' => $total_used, 'balance' => $total_balance, 'available' => $total - $total_used - $total_balance];
		
		$total_sql = M("customer_extend_record")
		->alias("cer")
		->join("customer_extend ce","ce.customer_extend_id = cer.customer_extend_id")
		->where(['cer.state' => ['in', "1,3"], 'ce.pid' => $customer_id , 'cer.goods_id = g.goods_id'])
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
		->where(['goods_id = g.goods_id' , 'cer.state' => ['in', "1,3"],  'ce.pid' => $customer_id
				,'_string' => " (cer.date_add > $min_date or (o.order_state = 3 or o.order_state = 2 or o.order_state = 7 ) )" ])
				->field("sum(cer.commission)")
				->buildSql();
		
		
		$audit_sql =  M("customer_withdraw")
		->where(['customer_id' => $customer_id, 'state' => 0, 'goods_id = g.goods_id'])
		->field("sum(money)")
		->buildSql();
		
		$exists = M("order_goods")->alias("og")
		->where(['og.goods_id = g.goods_id', 'o.id = og.order_id'])->field("1 as t")->buildSql();
		
		
		$order_count = M("order")->alias("o")
		->where(['_string' => "exists " . $exists,
				'o.customer_id' => $customer_id,
				'o.order_state' => ['in', '2,3,4,5,7']])
		->field("count(o.id)")->buildSql();

		$host = \app\library\SettingHelper::get("bear_image_url");
		$cover_suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		$goods = M("goods")
		->alias("g")
		->where(['is_deleted' => 0 ,'has_commission' => 1])
		->field("IFNULL({$total_sql},0) as total,IFNULL({$balance_sql},0) as balance, IFNULL({$used_sql},0) as used,IFNULL({$order_count}, 0) as order_count, IFNULL({$audit_sql}, 0) as audit ,".
		" g.goods_id,g.name,concat('$host',g.cover,'$cover_suffix') as cover")
		->select();
		foreach ($goods as &$g){
			$g['available'] = $g['total'] - $g['balance'] - $g['used'];
			$g['withdraw'] = $g['available'] - $g['audit'];
			unset($g['audit']);
		}
		
		$records = ['total' => $total, 'goods' => $goods];
		
		return $records;
	}
	
	
	/**
	 * 获取用户单个商品的可提现金额，
	 * */
	public function getOneGoodsExtendRecord($customer_id , $goods_id){
		
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
		->where(['goods_id = g.goods_id' , 'cer.state' => 1,  'ce.pid' => $customer_id
				,'_string' => " (cer.date_add > $min_date or (o.order_state = 3 or o.order_state = 2 or o.order_state = 7 ) )" ])
				->field("sum(cer.commission)")
				->buildSql();
		
		$host = \app\library\SettingHelper::get("bear_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		$goods = M("goods")
		->alias("g")
		->where(['has_commission' => 1,'is_deleted' => 0, 'goods_id' => $goods_id])
		->field("IFNULL({$total_sql},0) as total,IFNULL({$balance_sql},0) as balance, IFNULL({$used_sql},0) as used, ".
		" g.goods_id,g.name,concat('$host',g.cover,'$suffix') as cover")
		->find();
		
		if(empty($goods)){
			return ;
		}
		
		$money = M("customer_withdraw")->where(['customer_id' => $customer_id ,'state' => 0,'goods_id' => $goods_id])->sum("money");
		
		$money = empty($money) ? 0 : $money;
		$available = $goods['total'] - $goods['balance'] - $goods['used'] - $money ;
		$goods['available'] = $available;
		return $goods;
	}
	
}