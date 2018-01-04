<?php
namespace app\library\handler;
use app\library\message\Message;
class GoodsHandler extends Handler{
	public function orderNotify($order){

		$start_time = time();
		$customer_id = $order['customer_id'];
		$out_order_sn = isset($order['order_sn']) ? $order['order_sn'] : '';
		$ordersn = $order['foregin_infos'];
		$ordersn = explode(",", $ordersn);
		
		/*$orders = M("order")->where(['order_sn' => ['in', $ordersn]])->field("id, order_amount")->select();
		$amount = 0;
		foreach ($orders as $o){
			$amount += $o['order_amount'];
		}*/
		
		$order_goods = M("order")
		->alias("o")
		->join("order_goods og","og.order_id = o.id")
		->join("goods g","g.goods_id = og.goods_id")
        ->join("customer_coupon cc","cc.customer_coupon_id = o.coupon_id","LEFT")
        ->join("coupon c","c.coupon_id = cc.coupon_id","LEFT")
		->field("g.goods_id,g.stock_type,g.max_integration,g.max_shopping_coin,og.quantity,og.option_id,og.price,og.order_goods_id,og.special_type,".
            "IFNULL(c.amount,0) as coupon_amount")
		->where(['o.order_sn' => ['in', $ordersn]])
		->select();

		$has_commission = 0;
		
		$datas = [];
		
		$all_goods = [];
		
		foreach ($order_goods as $og){
            $goods_ids[] = $og['goods_id'];
			$goods_id = $og['goods_id'];
			if(!isset($datas[$goods_id])){
				$datas[$goods_id] = ['stock_type' => 0,'sale_count' => 0, 'stock' => 0, 'options' => [], 'special' => []];
			}
			$datas[$goods_id][ 'sale_count'] += $og['quantity'];
            $datas[$goods_id][ 'stock_type'] += $og['stock_type'];
			//$data = ['sale_count' => ['exp', 'sale_count + ' . $og['quantity']]];
			//$value = [];

			$goods = [];
			if(!isset($all_goods[$og['goods_id']])){
				$all_goods[$og['goods_id']] = D("goods_model")->GetGoods($og['goods_id']);
			}

			$goods = $all_goods[$og['goods_id']];

			$new_user_amount = $og['quantity'] * $og['price'] / $order['goods_amount'] * $order['new_user_amount'];
            $full_amount = $og['quantity'] * $og['price'] / $order['goods_amount'] * $order['full_amount'];
            $coupon_amount = $og['quantity'] * $og['price'] / $order['goods_amount'] * $order['coupon_amount'];
            M('order_goods')->where(['order_goods_id' => $og['order_goods_id']])->save(['full_amount' => $full_amount, 'new_user_amount' => $new_user_amount, 'coupon_amount' => $coupon_amount]);

			if($og['stock_type'] == 0){
				$datas[$goods_id]['stock'] += $og['quantity'];
				//$data['quantity'] = ['exp', 'quantity - ' . $og['quantity']];
				//$value['stock'] = $goods['stock'] - $og['quantity'];
			}
//			if($og['has_commission'] == 1){
//				$has_commission = 1;
//			}
            if ($og['special_type'] == 1) {
                $special_goods_id = M('special_goods')->where(['goods_id' => $og['goods_id'], 'special_id' => 1])->getField("special_goods_id");
                $datas[$goods_id]['special'][$special_goods_id] = ['sale_count' => $og['quantity']];
            }

			if($og['option_id'] > 0){
				$option_id = $og['option_id'];
				$key = 0;
				$o_stock = 0;
				if(isset($goods['options']) && !empty($goods ['options'])){
					//$value['options'] = $goods['options'];
					foreach ($goods['options'] as $k => $v){
						if($v['id'] == $og['option_id']){
							$key = $k;
							$o_stock = $v['stock'];
							//$value['options'][$k]['stock'] = $v['stock'] - $og['quantity'];
							break;
						}
					}
				}
				if(!isset($datas[$goods_id]['options'][$option_id])){
					$datas[$goods_id]['options'][$option_id] = ['stock' => 0, 'key' => $key, 'o_stock' => $o_stock];
				}
				
				$datas[$goods_id]['options'][$option_id]['stock'] += $og['quantity'];
				//M("goods_option")->where(['id' => $og['option_id']])->setDec("stock", $og['quantity']);
			}
			//$value['sale_count'] = $goods['sale_count'] + $og['quantity'];
			
			//M("goods")->where(['goods_id' => $og['goods_id']])->save($data);
			//D("goods")->updateGoods($og['goods_id'], $value);
		}

		foreach ($datas as $k => &$d){
		    $stock_type = $d['stock_type'];
            unset($d['stock_type']);
			$data = ['sale_count' => ['exp' , 'sale_count + ' . $d['sale_count']], 'quantity' => ['exp', 'quantity - ' . $d['stock']]];
			$goods = $all_goods[$k];
			$value = ['sale_count' => $goods['sale_count'] + $d['sale_count'], 'stock' => $goods['stock'] - $d['stock'], 'options' => []];

			if(!empty($d['options'])){
				$value['options'] = $goods['options'];
				foreach ($d['options'] as $option_id => $o){
					$value['options'][$o['key']]['stock'] = $o['o_stock'] - $o['stock'];
                    if ($stock_type == 0) {
                        M("goods_option")->where(['id' => $option_id])->setDec("stock", $o['stock']);
                    }
				}
			}
			if (!empty($d['special'])) {
                foreach ($d['special'] as $special_goods_id => $s){
                    $data_s = ['sale_count' => ['exp' , 'sale_count + ' . $s['sale_count']]];
                    if ($stock_type == 0) {
                        M("special_goods")->where(['special_goods_id' => $special_goods_id])->save($data_s);
                    }
                }
            }
            if ($stock_type == 0) {
                D("goods")->where(['goods_id' => $k])->save($data);
            }
			D("goods_model")->updateGoods($k, $value);
		}
		
		$state = isset($order['out_trade_no']) && !empty($order['out_trade_no']) ? 1 : 3;
		
//		$extends = D("customer_extend")->share_commission($customer_id, $ordersn, $state);
		
		$order_settings = \app\library\SettingHelper::get("shuaibo_order_settings",['received' => 7]);
		
		$count = M("order")->where(['customer_id' => $customer_id,'order_state' => ['in', '2,3,4,5,7']])->count();
		
		M("order")->where(['order_sn' => ['in', $ordersn]])
		->save(['date_received' => time() + $order_settings['received'] * 24 * 60 * 60,
				'order_state'=>2,
				'date_pay' => time(),
				'pay_id' => $order['pay_id'],
				'out_order_sn' => $out_order_sn]);

		// 新用户使用 积分/会员积分
        $customer_data = [
            'is_new' => 2
        ];
//        $customer_data['integration'] = ['exp', 'integration - '. $order['integration_amount'] ];
//        $customer_data['shopping_coin'] = ['exp', 'shopping_coin - '. $order['shopping_coin_amount'] ];

        M('customer')->where(['customer_id' => $customer_id])->save($customer_data);

        // 资金明细
        if (in_array($order['pay_id'],[1,2,3,4,5])) {
            M('finance')->add([
                'customer_id' => $customer_id,
                'finance_type_id' => 3,
                'type' => 1,
                'amount' => $order['order_amount'],
                'date_add' => time(),
                'is_minus' => 1,
                'order_sn' => $out_order_sn,
                'comments' => '支付订单 ' . $out_order_sn,
                'title' => '-' . $order['order_amount'],
            ]);
        }elseif ($order['pay_id'] == 6){
            M("finance")->add(array('customer_id' => $customer_id,'finance_type_id' => 3,'type' => 8,'amount' => $order['order_amount'],
                'date_add' => time(),'is_minus' => 1,'order_sn' => $out_order_sn,'comments' => '支付订单'.$out_order_sn,'title' => '-'.$order['order_amount']));
        }elseif ($order['pay_id'] == 7){
            M('finance')->add(array('customer_id' => $customer_id,'finance_type_id' => 3,'type' => 9,'amount' => $order['order_amount'],
                'date_add' => time(),'is_minus' => 1,'order_sn' => $out_order_sn,'comments' => '支付订单'.$out_order_sn,'title' => '-'.$order['order_amount']));
        }
        // 积分/会员积分明细
        if ($order['integration_amount'] > 0) {
            M('finance')->add([
                'customer_id' => $customer_id,
                'finance_type_id' => 1,
                'type' => 1,
                'amount' => $order['integration_amount'],
                'date_add' => time(),
                'is_minus' => 1,
                'order_sn' => $out_order_sn,
                'comments' => '支付订单 '.$out_order_sn,
                'title' => '-'.$order['integration_amount'],
            ]);
        }
        if ($order['shopping_coin_amount'] > 0) {
            M('finance')->add([
                'customer_id' => $customer_id,
                'finance_type_id' => 2,
                'type' => 1,
                'amount' => $order['shopping_coin_amount'],
                'date_add' => time(),
                'is_minus' => 1,
                'order_sn' => $out_order_sn,
                'comments' => '支付订单 '.$out_order_sn,
                'title' => '-'.$order['shopping_coin_amount'],
            ]);
        }

        if ($order['hongfu_amount'] > 0){
            M("finance")->add(array(
                'customer_id' => $customer_id,
                'finance_type_id' => 4,
                'type' => 1,
                'amount' => $order['hongfu_amount'],
                'date_add' => time(),
                'is_minus' => 1,
                'order_sn' => $out_order_sn,
                'comments' => '支付订单'.$out_order_sn,
                'title' => '-'.$order['hongfu_amount']
            ));
        }

		//$client = \app\library\message\MessageClient::getInstance();

        $orders = M('order')
            ->alias("o")
            ->where(['o.order_sn' => ['in', $ordersn]])
            ->join("seller_shopinfo ss","ss.seller_id = o.seller_id")
            ->join("customer c","c.customer_id = ss.customer_id")
            ->join("order_address oa","oa.id = o.address_id")
            ->field("o.id as order_id,o.order_sn,o.order_amount,o.express_amount,o.max_integration,o.max_shopping_coin,o.date_add,c.customer_id,c.nickname,".
                "oa.name,oa.phone,oa.province,oa.city,oa.district,oa.address")
            ->select();
        $seller_data = [];
        $withdraw_order = [];
        foreach ($orders as $order) {
            $sum = $order['order_amount'] + $order['express_amount'] - $order['max_integration'];
            $amount = 0;
            $goods = M('order_goods')
                ->alias("og")
                ->join('goods g',"g.goods_id = og.goods_id")
                ->where(['og.order_id' => $order['order_id']])
                ->field("og.quantity,og.price,og.max_integration,og.max_shopping_coin,g.shop_price,g.pv,g.manage_fee,g.pv_self")
                ->select();
            $amount = 0;

            foreach ($goods as $good) {
                $amount += ($good['price'] - $good['max_integration']) * ($good['pv'] + $good['manage_fee']) * $good['quantity'];
            }

            $sum = $sum - $amount;
            if ($sum <= 0) {
                $sum = 0;
            }
            M('customer')->where(['customer_id' => $order['customer_id']])->setInc('commission',$sum);
            $seller_data[] = [
                'customer_id' => $order['customer_id'],
                'finance_type' => 1,
                'amount' => $order['order_amount'] + $order['express_amount'] - $order['max_integration'],
                'real_amount' => $sum,
                'date_add' => time(),
                'is_minus' => 2,
                'order_sn' => $order['order_sn'],
                'comments' => '订单 '.$order['order_sn'].' 交易成功收款',
                'title' => '+'.$sum,
            ];
            $withdraw_order[] = [
                'customer_id' => $order['customer_id'],
                'order_sn' => $order['order_sn'],
                'order_amount' => $order['order_amount'] + $order['express_amount'] - $order['max_integration'],
                'commission_amount' => $sum,
                'date_add' => $order['date_add']
            ];

            // 同步LG
            $LG = new \app\api\LGApi();
            $lg_user = $LG->getUsername($order['nickname']);
            if ($lg_user == true) {
                // lg $order['nickname']
                $lgdata = [
                    'userid' => $order['nickname'],
                    'orderid' => $order['order_sn'],
                    'ordermoney' => $sum,
                    'orderpv' => $order['max_integration'],
                    'orderdate' => date("Y-m-d H:i:s",$order['date_add']),
                    'name' => $order['name'],
                    'mobiletele' => $order['phone'],
                    'country' => '中国',
                    'province' => $order['province'],
                    'city' => $order['city'],
                    'xian' => $order['district'],
                    'address' => $order['address']
                ];
                $res = $LG->AddOrderInfo($lgdata);
                $res = json_decode($res['return_content'],true);
//                if ($res['code'] != 0) {
//                    return ['errcode' => 0, 'message' => $res['message']];
//                }
            }
        }
        M('finance_op')->addAll($seller_data);
        M('customer_withdraw_order')->addAll($withdraw_order);
        /*
        // 商家收款
        $orders = M('order')
            ->alias("o")
            ->where(['o.order_sn' => ['in', $ordersn]])
            ->join("seller_shopinfo ss","ss.seller_id = o.seller_id")
            ->join("customer c","c.customer_id = ss.customer_id")
            ->field("o.order_sn,o.order_amount,c.customer_id")
            ->select();
        $seller_data = [];
        foreach ($orders as $order) {
            M('customer')->where(['customer_id' => $order['customer_id']])->setInc('commission',$order['order_amount']);
            $seller_data[] = [
                'customer_id' => $order['customer_id'],
                'amount' => $order['order_amount'],
                'date_add' => time(),
                'is_minus' => 2,
                'order_sn' => $order['order_sn'],
                'comments' => '订单 '.$order['order_sn'].' 交易成功收款',
                'title' => '+'.$order['order_amount'],
            ];
        }
        M('finance_op')->addAll($seller_data);
        */


		
		$data = [];
		return ['errcode' => 0 , 'message' => 'success', 'content' => ['customer' => $data]];
	}
	
}