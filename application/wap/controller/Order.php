<?php
namespace app\wap\controller;
use think\Controller;
class Order extends \app\wap\UserController{
	
	public function place_order(){
		
		$quantity = (int)I("quantity");
		
		if($quantity <= 0){
			$quantity;
		}
		
		$goods_id = (int)I("goods_id");
		
		if($goods_id <= 0){
			$this->error("请传入商品信息");
		}

		$option_id = (int)I("option_id");

		$address_id = (int)I("address_id");

		setcookie("s_quantity", $quantity, time() + 24 * 60 * 60, "/");
		setcookie("s_goods_id", $goods_id , time() + 24 * 60 * 60 , "/");
		setcookie("s_option_id", $option_id, time() + 24 * 60 * 60, "/");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		
		$goods = D("goods")->getGoods($goods_id,['cover', 'stock','goods_name', 'on_sale', 'date_end','price', "options"]);

		if(empty($goods)){
			$this->error("找不到相应商品");
		}
		$goods['image'] = $goods['cover'];
		$goods['option_name'] = "默认";
		if( isset($goods['options'])){
			foreach ($goods['options'] as $g){
				if($g['id'] == $option_id || $option_id == 0){
					$option_id = $g['id'];
					$goods['option_name'] = $g['name'];
					$goods['stock'] = $g['stock'];
					$goods['price'] = $g['sale_price'];
					break;
				}
			}
		}
		if(empty($goods)){
			$this->error("找不到对应商品");
		}

		unset($goods['options']);
		$customer_id = $this->customer_id;
		$address = "";
		if($address_id > 0){
			$address = D("address")->getAddress($address_id);
			if(!empty($address) && $address['customer_id'] != $customer_id){
				$address = "";
			}
		}else {
			$address = M("address")->where(['customer_id' => $customer_id , 'status' => 1])->find();
			if(!empty($address)){
				$address_id = $address['address_id'];
			}
		}

        $express_list = D("express")->getAddOrderShow();

		$goods['express_fee'] = D("express_template")->calculateExpress($goods_id, $address_id, 1, $option_id) * $quantity;
		$goods['quantity'] = $quantity;
		$cart_number = D("customer")->CartNumber($this->customer_id);
		$this->assign("goods_number", $cart_number);
		$this->assign("goods", $goods);
		$this->assign("option_id", $option_id);
		$this->assign("address", $address);
		$this->assign("express_list",$express_list);
		$this->display();
	}
	
	public function order_detail(){

		$customer_id = $this->customer_id;
		$order_sn = I('order_sn');
		$order =  D("order")->get_order_detail($customer_id , $order_sn);
		if(is_string($order)){
			$this->error($order);
		}
		$this->assign("express", $order['express']);
		$this->assign("seller_info", $order['seller_info']);
		$this->assign("refund_info", $order['refund_info']);
		$this->assign("order",$order['order']);
		$this->display();
	}
	
	public function pay_bal(){
		$order_sns = I("orders");
		if(empty($order_sns)){
			$this->error("请传入订单信息");
		}
		$order_sns = explode(",", $order_sns);
		
		$expire = \app\library\SettingHelper::get("bear_order_settings", ['pay_expire' => 30]);
		
		$orders = M("order")->where(['order_sn' => ['in' , $order_sns]])->field("order_sn,order_amount,date_end")->select();
		
		foreach ($orders as &$o){
			$o['date_end'] = $o['date_end'] - time(); 
		}
		
		$this->assign("orders", $orders);
		$this->assign("expire", $expire['pay_expire']);
		$this->assign("pay_ids", [(is_weixin() ? 4 : 5)]);
		$this->display();
	}
	
	// 获取订单列表
	public function my_order() {
		$this->display();		
	}
	
	
	
	// 申请退款
	public function apply_refund() {
		$customer_id = get_customer_id();
		
		$order_sn = I('order_sn');
		
		$order = D("order")->apply_refund($order_sn);
		if(is_string($order)){
			$this->error($order);
		}
		
		// 获取退款原因
		$reason = M('order_return_reason')->select();
		
		$seller_info = \app\library\SettingHelper::get("shuaibo_seller_info",['address' => '杭州市' ,'qq' => '123456789']);
		
		$this->assign("service_phone", $seller_info['qq']);
		$this->assign("reason",$reason);
		$this->assign("order",$order);
		$this->display();
	}
	
	
	public function evaluate(){
		$order_sn = I("order_sn");
		
		$goods_id = (int)I("goods_id");
		
		$host = $this->host;
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		$order = M("order")
		->alias("o")
		->join("order_goods og","og.order_id = o.id")
		->join("goods g","g.goods_id =og.goods_id")
		->field("o.order_state,concat('$host',g.cover, '$suffix') as image, g.name,  ifnull(og.option_name,'默认') as option_name,og.price,og.quantity,o.order_sn")
		->where(['o.order_sn' => $order_sn , 'g.goods_id'=> $goods_id])
		->find();
		if(empty($order)){
			$this->error("找不到相关订单");
		}
		
		if($order['order_state'] != 4){
			$this->error("该订单未处于待评价状态，无法进行评价");
		}
		
		$this->assign("order", $order);
		$this->display();
		
	}
}