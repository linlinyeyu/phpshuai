<?php
namespace app\app\model;
use think\Model;
use think\Cache;
class Items extends Model{
	
	public function buy($order_number,$sum,$out_trade_number = null, $type = 0){

		$type_name = "余额支付";
			switch($type){
				case 0:
				 $type_name = "余额支付";
				 break;
				 case 1:
				 $type_name = "支付宝";
				 break;
				 case 2:
				 $type_name = "微信支付";
				 break;
			}

		$condition = array("io.order_number"=> $order_number);
		$order_goods = M("items_order")
		->alias("io")
		->join("__ORDER_GOODS__ og","io.order_id = og.order_id")
		->join("__ITEMS__ i","i.item_id = og.item_id","LEFT")
		->field("io.ip,io.ip_area,i.goods_id,io.cart_ids, io.pay_amount,i.state, i.temp_nums, io.order_amount,io.goods_amount,io.cash_amount,io.is_pay, og.quantity, og.item_id,og.order_number,(i.price -i.quantity) as stock,og.order_goods_id,og.item_id,io.customer_id,i.quantity as number,i.price,io.order_id")
		->where($condition)
		->select();
		
		if(empty($order_goods) || $order_goods[0]['is_pay'] == 1){
			return;
		}
		
		$total = 0;
		$num = 0;
		foreach($order_goods as $key => $val){
			if(empty($val['item_id'])){
				continue;
			}
			$data = Cache::lranges("bear_buy_item_".$val['item_id'],0,0);
			
			Cache::rpush("bear_buy_item_".$val['item_id'], $order_number);

			if(!empty($data)){
				$num ++;	
				Cache::rpush("bear_buy_itemlist",$val['item_id']);
			}
		}
		if($num > 0){
			Cache::set("bear_buy_order_number_".$order_number, serialize(
					array("order_number" => $order_number,
					"sum" => $sum,
					"out_trade_number" => $out_trade_number,
					"type" => $type,
					"num" => $num)),365*60*60*24);
			return 0;
		}

		//判断支付总额与所需支付总额是否一致，若不一致，则将钱打到账户上，防止有人使用支付宝接口1分钱付款
		/*if($sum != $order_goods[0]['io.order_amount']){
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
			M("customer")
			->where(array("customer_id"=> $order_goods[0]['customer_id']))
			->setInc("account",$sum);
			D("message")->send($order_goods[0]['customer_id'],"充值成功","恭喜您充值成功".$sum."元");

			$finance_op = array(
		    			'customer_id' => $order_goods[0]['customer_id'],
		    			'type' => $type,
		    			'type_name' => $type_name,
		    			'foreign_id' => 0,
		    			'amount' => $sum,
		    			'date_add' => time()
		    		);
			M("finance_op")->add($finance_op);
			return $total;
		//如果不为空，则为直接购买
		}else{
			
			foreach ($order_goods as $key => $value) {
				

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

				
				$inc = array('quantity'=> $value['number'] + $value['quantity'],
					'percent' =>(int) (($value['number'] + $value['quantity']) * 100 / $value['price']));

				$this->where(array("item_id"=> $value['item_id']))->save($inc);

				$temp_nums = explode(",", $value['temp_nums']);
				$luck_nums = array();
				$luck_num_pre = (int)C("luck_num_pre");
				for ($i = 0; $i < $value['quantity']; $i++) {
					$luck_nums[] = $luck_num_pre + (int)array_shift($temp_nums);
				}
				$now = round(microtime_float(),3);
				$items_customer = array(
						"luck_nums" => join(",",$luck_nums),
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
				M("items_customer")->add($items_customer);
					
				M("items")->where(array("item_id"=> $value['item_id']))->save(array("temp_nums" => join(",",$temp_nums)));

				if($value['quantity'] == $value['stock']){
					$item_id = $value['item_id'];
					$this->buildNewItem($value['item_id']);

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
				D("message")->send($order_goods[0]['customer_id'],"斗米通知","很抱歉，您所购买的商品数量不足，余款".$total."元将打入您的账户");
				M("customer")->where(array("customer_id" => $order_goods[0]['customer_id']))->setInc("account",$total);
			}
			
			M("items_order")->alias("io")->where($condition)->save($order);


			
			$cart_ids = $order_goods[0]['cart_ids'];
			if(!empty($cart_ids)){
				$cart_ids = explode(",", $cart_ids);
				D("cart")->deleteCart($cart_ids);
			}
			
			$finance_op = array(
		    			'customer_id' => $order_goods[0]['customer_id'],
		    			'type' => $type,
		    			'type_name' => $type_name,
		    			'foreign_id' => $order_goods[0]['order_id'],
		    			'amount' => $sum - $total,
		    			'date_add' => time()
		    		);
			M("finance_op")->add($finance_op);
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
			$arr = range(1, $item['price']);
			shuffle($arr); 
			$luck_nums = array();
			foreach ($arr as $value) {
				$luck_nums[] = $value;
			}
			$luck_nums = join(",",$luck_nums);
			$item['luck_nums'] = $luck_nums;
			$item['temp_nums'] = $luck_nums;
			$i = 0;
			$id = 0;
			do{
				
				$item['item_number'] = $this->create_item_number();
				$id = $this->add($item);
				$i++;
			}while(empty($id) && $i < 3);

			if($goods['is_infinite'] == 0){
				M("goods")->where(array('goods_id'=> $goods['goods_id']))->setDec('quantity',1);
				if($goods['quantity'] - 1 <= $goods['out_of_stock']){
					//通知；
				}
			}
		}
		
	}

	public function create_item_number(){
		$count = M("items")
				->where(array(
					"date_add"=> array(
						"egt",strtotime(date('Y-m-d', time())))
					))->count();
		$count = str_pad($count,4,'0',STR_PAD_LEFT);
		return substr(date("Ymd",time()),3).$count;
	}
	//进程，对于公布时间已到的期号进行抽奖
	public function Win(){
		$condition = array(
				'state' => 1,
				'date_pub' => array("elt", microtime_float() + 5),
				'percent' => 100
			);
		$items = $this	
		->field("item_id,price,luck_nums,date_end,date_pub")
		->where($condition)
		->select();
		if(empty($items)){
			return;
		}
		foreach ($items as $key => $value) {
		 	$sum = M("items_customer")
		 	->where(array(
		 		"date_add" => array("elt",$value['date_end']),
		 		"item_id" => array("gt", 0)))
		 	->order("date_add desc")
		 	->limit(50)
		 	->sum("date_time");
		 	$luck_num = (int)C("luck_num_pre") + $sum % $value['price'] + 1;
		 	$luck_nums = explode(",", $value['luck_nums']);
		 	$index = array_search($luck_num, $luck_nums);
		 	if(empty($index) && $index != 0){
		 		continue;
		 	}

			$luck_customer = M("items_customer")
			->where(array(
				"item_id" => $value['item_id'],
				"luck_nums" => array("like", "%$luck_num%")
				))
			->field("customer_id")
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
				"state" => 2));
			$winning_record = array(
					"item_id" => $value['item_id'],
					"customer_id" => $customer_id,
					"state" => 0,
					"date_add" => $value['date_pub']
				);
			$id = M("winning_record")
			->add($winning_record);	

			$customer = M("customer") ->where(['customer_id' => $customer_id])->field("is_robot")->find();
			
			if($customer['is_robot'] != 1){
				D("message")->send($customer_id, "中奖啦", "恭喜您已中奖，请尽快填写地址信息", 2, $id);
			}

			
		}
	}

	

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

	public function GetHomeItems($type, $page){
		$order = "i.date_add desc";
		if($type == 0){
			$order = "g.salecount desc,i.date_add desc";
		}
		else if($type == 2){
			$order = "i.percent desc";
		}else if($type == 3){
			$order = "i.price ";
		}else if($type == 4){
			$order = "i.price desc";
		}


		$where = "i.state = 0 and i.active = 1 and i.percent < 100 ";
		$goods = $this
		->alias("i")
		
		->limit(10)->page($page)
		->join('__GOODS__ g','g.goods_id = i.goods_id ','LEFT')
		->join("__IMAGE__ im",'im.goods_id = g.goods_id and im.cover = 1 ')
		->field("im.image_id,im.name as goods_image,i.item_id,i.goods_id,i.percent,i.price,g.name as goods_name,g.is_ten")
		->where($where)
		->order($order)->select();
		$host = C("goods_url");
		if(!empty($goods)){
			foreach ($goods as $key => $value) {
				if($goods[$key]['image_id']){
					$url = $host;
					$url = $url.get_image_url($goods[$key]["image_id"], $goods[$key]["goods_image"]);
					$goods[$key]['goods_image'] =  $url;
				}
				unset($goods[$key]['image_id']);
			}
		}
		return $goods;
	}
}