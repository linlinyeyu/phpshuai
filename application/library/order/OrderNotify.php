<?php
namespace app\library\order;
class OrderNotify{
	
	protected $sum;
	
	protected $order_sn;
	
	protected $out_trade_no;
	
	protected $pay_id = 1;
	
	protected $is_recharge = false;
	
	protected $order;
	
	protected $customer_id;
	
	public function setOutTradeNo($out_trade_no){
		$this->out_trade_no = $out_trade_no;
		return $this;
	}
	
	public function setIsRecharge($is_recharge){
		$this->is_recharge = $is_recharge;
		return $this;
	}
	
	public function setPayId($pay_id){
		$this->pay_id = $pay_id;
		return $this;
	}
	
	public function setSum($sum){
		$this->sum = $sum;
		return $this;
	}
	
	public function setOrderSn($order_sn){
		$this->order_sn = $order_sn;
		return $this;
	}
	
	
	public function __construct($order_sn , $sum , $pay_id){
		$this->order_sn = $order_sn;
		
		$this->sum = $sum;
		
		$this->pay_id = $pay_id;
	}
	
	
	public function notify(){
		
		if(empty($this->order_sn)){
			return ['errcode' => -100 , 'message' =>'找不到订单号'];
		}
		
		
		S("shuaibo_pay_order_" . $this->order_sn, 1);
		
		$this->order = M("order_info")->alias("o")
		->join("order_type ot","ot.order_type_id = o.order_type","LEFT")
		->field("o.*, ot.code")
		->where(['order_sn' => $this->order_sn])
		->find();
		if(empty($this->order)){
			return ['errcode' => -101 , 'message' => '找不到订单号'];
		}
		
		if($this->order['state'] == 2){
			return ['errcode' => -102 , 'message' => '订单已支付'];
		}
		
		if(($this->order['order_amount'] - $this->order['cash_amount']) != $this->sum){
			return ['errcode' => -103 , 'message' => '订单金额与支付金额不一致'];
		}
		
		$this->customer_id = $this->order['customer_id'];
		
		$this->order['out_trade_no'] = $this->out_trade_no;
		$code = convertUnderline($this->order['code']);
		$cls_name = "\\app\\library\\handler\\". $code . "Handler";
		$res = [];
		if(class_exists($cls_name)){
			$cls = new $cls_name();
			$res = $cls->orderNotify($this->order);
		}else{
			return ['errcode' => -102, 'message' => '找不到对应的handler'];
		}
		
		if($res['errcode'] != 0 ){
			return $res;
		}
		
		$customer_data = [];
		if($this->pay_id > 1 && $this->pay_id != 6){
			//如果为其他支付，则给金额
			$customer_data['total_account'] = ['exp', 'total_account + '. $this->sum ];
		}

		if($this->order['cash_amount'] > 0){
		    if ($this->pay_id == 1){
                $customer_data['account'] = ['exp', 'account - '. ($this->order['cash_amount']) ];
            }elseif ($this->pay_id == 6){
		        $customer_data['reward_amount'] = ['exp','reward_amount - '.($this->order['cash_amount'])];
            }elseif ($this->pay_id == 7){
                $customer_data['transfer_amount'] = ['exp','transfer_amount - '.($this->order['cash_amount'])];
            }

		}
//		if($this->order['coupon_id']){
//			M("customer_coupon")->where(['customer_coupon_id' => $this->order['coupon_id']])->save(['active' => 1,'date_upd' => time()]);
//		}
		if(isset($res['content']['customer'])){
			$customer_data = array_merge($customer_data, $res['content']['customer']);
		}
		
		if(!empty($customer_data)){
			M("customer")->where(['customer_id' => $this->customer_id])->save($customer_data);
		}
		
		$order_data = ['state' => 2,'date_pay' => time(),'out_trade_no' => $this->out_trade_no];
		if(isset($res['content']['order'])){
			$order_data = array_merge($order_data, $res['content']['order']);
		}
		
		//订单转化为已支付
		M("order_info")->where(['order_sn' => $this->order_sn])->save($order_data);
		
		return ['errcode' => 0, 'message' => '操作成功'];
		
	}
}