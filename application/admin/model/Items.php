<?php
namespace app\admin\model;
use think\Model;
use think\Cache;
class Items extends Model{
/**
 * 购买商品
 * @param unknown $order_number
 * @param unknown $sum
 * @param string $out_trade_number
 * @param number $type
 * @param string $force
 */	
	public function buy($order_number,$sum,$out_trade_number = null, $type = 0 ,$force = false){
		$condition = array("io.order_number"=> $order_number);
// 		订单的商品信息
		$order_goods = M("items_order")
		->alias("io")
		->join("__ORDER_GOODS__ og","io.order_id = og.order_id")
		->join("__ITEMS__ i","i.item_id = og.item_id","LEFT")
		->field("io.ip,io.ip_area,io.coupon_id,i.bucket,i.goods_id,io.cart_ids, io.pay_amount,i.state, io.order_amount,io.goods_amount,io.cash_amount,io.is_pay, og.quantity, og.item_id,og.order_number,(i.price -i.quantity) as stock,og.order_goods_id,og.item_id,io.customer_id,i.quantity as number,i.price,io.order_id")
		->where($condition)
		->select();
		if(empty($order_goods) || $order_goods[0]['is_pay'] == 1){
			return;
		}
		$total = 0;
		$num = 0;
// 		判断是否为强制
		if (!$force){
			foreach($order_goods as $key => $val){
				if(empty($val['item_id'])){
					continue;
				}
				$data = Cache::lranges("bear_buy_item_".$val['item_id'],0,0);
				
				Cache::rpush("bear_buy_item_".$val['item_id'], $order_number);
// 	同一商品有多人购买
				if(!empty($data)){
					$num ++;	
					Cache::rpush("bear_buy_itemlist",$val['item_id']);
				}
			}
// 	购买商品发生堵塞
			if($num > 0){
				Cache::set("bear_buy_order_number_".$order_number, serialize(
						array("order_number" => $order_number,
						"sum" => $sum,
						"out_trade_number" => $out_trade_number,
						"type" => $type,
						"num" => $num)),365*60*60*24);
				return 0;
			}
		}

		//判断支付总额与所需支付总额是否一致，若不一致，则将钱打到账户上，防止有人使用支付宝接口1分钱付款
		/*if($order_goods[0]['item_id'] != 0 && $sum != $order_goods[0]['order_amount']){
			M("customer")
			->where(array("customer_id"=> $order_goods[0]['customer_id']))
			->setInc("account",$sum);
			D("message")->send($order_goods[0]['customer_id'],"通知","由于您支付的金额与实际所需不符，因此将所支付金额转为账户余额", 0);
			return $total;
		}*/
		$order = array(
			'is_pay' => 1,
			'pay_amount' => $order_goods[0]['pay_amount'] + $sum,
			'pay_no' => $out_trade_number
			);
		//如果item_id为空或为0，则表示为充值
		if(empty($order_goods[0]['item_id']) || $order_goods[0]['item_id'] == 0){
			M("items_order")->alias("io")->where($condition)->save($order);
			
			//-1表示充值
			D("finance_op")->record($order_goods[0]['customer_id'],-1,$order_goods[0]['order_id'], $sum);
			
			D("message")->send($order_goods[0]['customer_id'],"充值成功","恭喜您充值成功".$sum."元");
			
			return $total;
		//如果不为空，则为直接购买
		}else{
			foreach ($order_goods as $key => $value) {
				$item_id = $value['item_id'];
				if($value['quantity'] > $value['stock']){
					$diff = $value['quantity'] - $value['stock'];
					$total += $diff;
					$value['quantity'] = $value['stock'];
					M("order_goods")->where(array("order_goods_id" => $value['order_goods_id']))->setDec('quantity',$diff);
				}

				if($value['stock'] == 0){
					Cache::lpop("bear_buy_item_".$value['item_id']);
					continue;
				}
				
				
				$quantity = $value['quantity'];
				$temp_luck_nums = [];
				$start_bucket = $value['bucket'];
				$end_bucket = $start_bucket;
				
				do{
					$temps = M("items_nums")->alias("inn")
					->join("items i", "i.item_id = inn.item_id")
					->where(['i.item_id' => $item_id, "inn.bucket" => $end_bucket])
					->field("inn.luck_nums,i.bucket")
					->find();
					if(empty($temps)){
						$end_bucket = -1;
					}else{
						$temp = explode(",", $temps['luck_nums']);
						$temp_luck_nums = array_merge($temp_luck_nums, $temp);
						$end_bucket ++;
					}
				}while($end_bucket >= 0 && $quantity > count($temp_luck_nums));
				if($end_bucket == -1){
					Cache::lpop("bear_buy_item_".$value['item_id']);
					continue;
				}
				$inc = array('quantity'=> $value['number'] + $value['quantity'],
						'percent' =>(int) (($value['number'] + $value['quantity']) * 100 / $value['price']));
				$this->where(array("item_id"=> $value['item_id']))->save($inc);
				$end_bucket --;
				$temp_nums = array_slice($temp_luck_nums, 0,$quantity);
				$chunk_number = \app\library\SettingHelper::get("bear_chunk_number", 10000);
				$bear_nums = array_slice($temp_luck_nums, $quantity, $chunk_number);
				unset($temp_luck_nums);
				//此处仅为数据库存储
				
				$luck_num_pre = (int)C("luck_num_pre");
				for ($i = 0; $i < $value['quantity']; $i++) {
					$temp_nums[$i] = $luck_num_pre +  $temp_nums[$i];
				}
				$now = round(microtime_float(),3);
				$items_customer = array(
						"luck_nums" => join(",",$temp_nums),
						"min_quantity" => $value['number'] + 1,
						"order_goods_id" => $value['order_goods_id'],
						"max_quantity" => $value['number'] + $value['quantity'],
						"date_add" => $now,
						"date_time" => get_microtime_time($now),
						"item_id" => $value['item_id'],
						"customer_id" => $value['customer_id'],
						"ip" => $value['ip'],
						"quantity" => $value['quantity'],
						"ip_area" => $value['ip_area']
					);
				unset($temp_nums);
				M("items_customer")->add($items_customer);
				for($i = $start_bucket; $i <= $end_bucket; $i ++){
					$nums = "";
					if($i == $end_bucket && !empty($bear_nums)){
						$nums = implode(",", $bear_nums);
					}
					M("items_nums")->where(['item_id' => $value['item_id'], 'bucket'=>$i])->save(['luck_nums' => $nums]);
				}
				M("items")->where(['item_id' =>$value['item_id']])->save(['bucket' => $end_bucket]);
				if($value['quantity'] == $value['stock']){
					//对老期号进行处理
					if($item_id > 0 && $value['state'] == 0){
						$date_now = time();
						$date_pub =((int)($date_now / 60 / 15 + 1)) * 60 * 15;
						if($date_pub - $date_now < 60 * 5){
							$date_pub =((int)($date_now / 60 / 15 + 2)) * 60 * 15;
						}
						$data = array(
								'date_pub' => $date_pub,
								'date_end' => $now,
								'state' => 1,
								'active' => 0
						);
						$this->where(array("item_id" => $item_id))->save($data);
						M("goods")->where(array("goods_id"=> $value['goods_id']))->setInc("salecount");
					}
					
					try{
						$this->buildNewItem($value['item_id']);
					}catch(\Exception $e){
					}
					
				}
				Cache::lpop("bear_buy_item_".$value['item_id']);
			}

			if($total > 0){
				$order['pay_amount'] = $order['pay_amount'] - $total;
				$order['order_amount'] = $order_goods[0]['order_amount'] - $total;
				$order['goods_amount'] = $order_goods[0]['goods_amount'] - $total;
				if($order_goods[0]['cash_amount'] > 0){
					$order['cash_amount'] = $order_goods[0]['cash_amount'] - $total; 
				}
				D("message")->send($order_goods[0]['customer_id'],"系统通知","很抱歉，您所购买的商品数量不足，余款".$total."元将打入您的账户");
				M("customer")->where(array("customer_id" => $order_goods[0]['customer_id']))->setInc("account",$total);
			}
			$coupon_id = isset($order_goods[0]['coupon_id']) ? $order_goods[0]['coupon_id'] : "";
			if(!empty($coupon_id)){
				M("customer_coupon")
				->where(array("customer_coupon_id" => $coupon_id))
				->save(array("state"=>1));
			}
			
			M("items_order")->alias("io")->where($condition)->save($order);
			
			$cart_ids = $order_goods[0]['cart_ids'];
			if(!empty($cart_ids)){
				$cart_ids = explode(",", $cart_ids);
				D("cart")->deleteCart($cart_ids);
			}
			//添加消费记录，并去除相应的余额
			D("finance_op")->record($order_goods[0]['customer_id'],$type,$order_goods[0]['order_id'], $total - $sum );
		}
		
		return $total;
	}


	//创建新的item
	public function buildNewItem($item_id ,$goods_id = 0){
		
		$cache = Cache::get("bear_new_item_" . $item_id);
		if(!empty($cache)){
			return;
		}
		Cache::set("bear_new_item_" . $item_id, 1, 3);
		$condition = array();
		if($goods_id != 0){
			$condition['g.goods_id'] = $goods_id;
		}else{
			$condition['i.item_id'] = $item_id;
		}

		$goods = M("goods")
		->alias("g")
		->join("__ITEMS__ i","g.goods_id = i.goods_id","LEFT")
		->field("g.is_infinite,g.price,g.quantity,g.out_of_stock, g.on_sale,g.is_ten,g.goods_id,i.state,g.is_virtual")
		->where($condition)
		->find();
		if(empty($goods)){
			return;
		}
		
		$count = $this->where(array("active" => 1, "goods_id" => $goods['goods_id']))->count();
		if($count > 1){
			return;
		}
		if($goods['on_sale'] == 0){
			return;
		}

		if($goods['is_infinite'] == 0 && $goods['quantity'] == 0){
			return;
		}

		if($goods['is_infinite'] == 1 || $goods['quantity'] > 0){
			
			$item = array(
				'goods_id' => $goods['goods_id'],
				'price' => $goods['price'],
				'quantity' => 0,
				'percent' => 0,
				'state' => 0,
				'active' => 1,
				"is_virtual" => $goods['is_virtual'],
				'date_add' => microtime_float(),
				'is_ten' => $goods['is_ten']);
			
			
			$i = 0;
			$id = 0;
			do{
				$item['item_number'] = $this->create_item_number();
				$id = $this->add($item);
				$i++;
			}while(empty($id) && $i < 3);
			
			$arr = range(1, $item['price']);
			shuffle($arr);
			$luck_nums = array();
			foreach ($arr as $value) {
				$luck_nums[] = $value;
			}
			$chunk_number = \app\library\SettingHelper::get("bear_chunk_number",10000);
			$arr = array_chunk($arr, $chunk_number);
			foreach($arr as $key => $val){
				$lucks = join(",", $val);
				M("items_nums")
				->add(['item_id' => $id,'luck_nums' => $lucks, 'bucket' => $key]);
			}
			
			if($goods['is_infinite'] == 0){
				M("goods")->where(array('goods_id'=> $goods['goods_id']))->setDec('quantity',1);
				if($goods['quantity'] - 1 <= $goods['out_of_stock']){
					//通知；
				}
			}
		}
		
	}
/**
 * 创建商品号
 */
	public function create_item_number(){
		$count = M("items")
				->where(array(
					"date_add"=> array(
						"egt",strtotime(date('Y-m-d', time())))
					))->max("item_number");
		if(!empty($count)){
			return $count + 1;
		}else{
			return substr(date("Ymd",time()),3)."0001";
		}
	}
	//进程，对于公布时间已到的期号进行抽奖
	public function Win(){
		$condition = array(
				'i.state' => 1,
				'i.date_pub' => array("elt", microtime_float() + 5),
				'i.percent' => 100
			);
		$items = $this	
		->alias("i")
		->join("goods g","i.goods_id = g.goods_id")
		->field("i.item_id,i.price,i.luck_nums,i.date_end,i.date_pub,g.mini_name, g.name as goods_name")
		->where($condition)
		->select();
		if(empty($items)){
			return;
		}
		$lottery = M("lottery")->order("date_line desc")->find();
		
		$lottery_time = "0";
		$lottery_number = 0;
		$lottery_settings = \app\library\SettingHelper::get("bear_lottery", ['is_open' => 0, 'name' => 'cqssc', 'uid' => '464665', 'token' => 'a4cff42c918b4be5a7ec3c92b993be3d19db562b']);
		if($lottery_settings['is_open'] == 1 && !empty($lottery) && time() - $lottery['date_line'] < 60 * 60 *24){
			$lottery_number = str_replace(",", "", $lottery['number']);
			$lottery_time = $lottery['date_time'];
		}
		foreach ($items as $key => $value) {
		 	$order_goods = M("items_customer")
			->alias("ic")
			->where(array(
					"ic.date_add" => array("elt",$value['date_end']),
					"item_id" => array("gt",0)))
					->order("ic.date_add desc")
					->field("ic.date_time")
					->limit(50)
					->select();
			$sum = 0;
			foreach ($order_goods as $goods){
				$sum += $goods['date_time'];
			}
			//加上彩票的偏移量
			$sum = $sum + $lottery_number;
			
			//计算幸运号码
		 	$luck_num = C("luck_num_pre") + fmod($sum , $value['price']) + 1;
			
			$luck_customer = M("items_customer")->alias("ic")
			->join("customer c","c.customer_id = ic.customer_id")
			->where(array(
				"ic.item_id" => $value['item_id'],
				"ic.luck_nums" => array("like", "%$luck_num%")
				))
			->field("ic.customer_id, c.phone,c.is_robot")
			->find();
			
			if(empty($luck_customer)){
				continue;
			}
			$customer_id = $luck_customer["customer_id"];
			M("items")
			->where(array("item_id" => $value['item_id']))
			->save(array(
				"luck_num" => $luck_num,
				"luck_id" => $customer_id,
				"luck_nums" => '',
				"state" => 2,
				'lottery' => $lottery_time
			));
			$winning_record = array(
					"item_id" => $value['item_id'],
					"customer_id" => $customer_id,
					"state" => 0,
					"date_add" => $value['date_pub']
				);
			$id = M("winning_record")
			->add($winning_record);	
			if($luck_customer['is_robot'] != 1){
				$win_message = \app\library\SettingHelper::get("bear_win_message", "恭喜您中奖啦，赶紧去领奖吧");
				$mini_name = $value['mini_name'];
				if(empty($mini_name)){
					$mini_name = $value['goods_name'];
				}
				$win_message = str_replace("%goods", $mini_name, $win_message);
				
				D("message")->send($customer_id, "系统通知", $win_message, 2, $id);
				
				if(!empty($luck_customer['phone'])){
					$content = \app\library\SmsHelper::sign($win_message);
					\app\library\SmsHelper::send($luck_customer['phone'], $content);
				}
			}
		}
	}

	
/**
 * 获取幸运用户
 * @param unknown $item_id
 */
	public function GetLuckUser($item_id){
        $host = C('image_url');
		if(empty($item_id) || $item_id <= 0){
			return false;
		}
		return $this
		->alias("i")
		->join("__CUSTOMER__ c"," c.customer_id = i.luck_id")
		->join("__ITEMS_CUSTOMER__ ic", "ic.item_id = i.item_id and c.customer_id = ic.customer_id")
		->field("i.item_id, c.uuid as customer_id,concat('$host',c.avater) as avater, c.nickname, i.date_pub, i.item_number,sum(ic.quantity) as quantity")
		->group("c.customer_id")
		->where(array("i.item_id" => $item_id))
		->find();
	}
/**
 * 获取商品列表
 * @param unknown $type
 * @param unknown $page
 */
	public function GetHomeItems($type, $page){
		$order = "i.date_add desc";
		if($type == 0){
			$order = "g.sort , g.salecount desc,i.date_add desc";
		}
		else if($type == 2){
			$order = "i.percent desc";
		}else if($type == 3){
			$order = "i.price ";
		}else if($type == 4){
			$order = "i.price desc";
		}

		$host = \app\library\SettingHelper::get("bear_image_url");
		$where = "i.state = 0 and i.active = 1 and i.percent < 100 ";
		$goods = $this
		->alias("i")
		->limit(10)->page($page)
		->join('__GOODS__ g','g.goods_id = i.goods_id ','LEFT')
		->join("__IMAGE__ im",'im.goods_id = g.goods_id and im.cover = 1 ')
		->field("concat('$host', im.name) as goods_image,i.item_id,i.goods_id,i.percent,i.price,g.name as goods_name,g.is_ten")
		->where($where)
		->order($order)->select();
		
		
		return $goods;
	}
}