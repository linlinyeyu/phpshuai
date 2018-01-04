<?php
namespace app\library\order;
use app\library;
abstract class OrderHelper {
	protected $type = 0;
	
	public $pay_id = 1;
	
	public $pay_name = "余额支付";
	
	protected $subject = "标题";
	
	protected $body = "商品描述";
	
	protected $goods = [];
	
	protected $coupon_id = [];

	protected $integartion = 0;

	protected $shopping_coin = 0;

	protected $hongfu = 0;

    protected $order = [];
	
	protected $return = [];
	
	protected $order_number = "";
	
	protected $goods_amount = -1;
	
	protected $sum = 0;
	
	protected $customer_id = "";
	
	protected $order_type = 1;
	
	protected $goods_tag;
	
	/**
	 * 是否使用余额
	 * */
	protected $balance_type = 0;
	
	public function getOrder(){
		return $this->order;
	}
	
	public function getReturn(){
		return $this->return;
	}
	
	public function setOrderNumber($order_number){
		$this->order_number = $order_number;
		return $this;
	}
	
	public function getType(){
		return $this->type;
	}
	
	public function setBalanceType($balance_type){
		$this->balance_type = $balance_type;
	}
	
	//设置商品
	public function setGoods($goods){
		if(empty($goods) || !is_array($goods)){
			return $this;
		}
		if(!isset($goods[0]['goods_id'])){
			return $this;
		}
		$this->goods = $goods;
		return $this;
	}
	
	public function setSubject($subject){
		$this->subject = $subject;
		return $this;
	}
	
	public function setBody($body){
		$this->body = $body;
		return $this;
	}
	
	//设置优惠券
	public function setCoupon($coupon_ids){
		$this->coupon_ids = $coupon_ids;
		return $this;
	}

    public function setIntegartion($integartion){
        $this->integartion = $integartion;
        return $this;
    }

    public function setShoppingCoin($shopping_coin){
        $this->shopping_coin = $shopping_coin;
        return $this;
    }

    /**
     * @param int $hongfu
     */
    public function setHongfu($hongfu)
    {
        $this->hongfu = $hongfu;
    }
	
	public function setGoodsAmount($goods_amount){
		$this->goods_amount = $goods_amount;
		return $this;
	}
	
	public function setOrderType($order_type){
		$this->order_type = $order_type;
		return $this;
	}
	
	public function setGoodsTag($goods_tag){
		$this->goods_tag = $goods_tag;
		return $this;
	}
	
	public static function getInstance($type = 1){
		
		$pay_type = M("pay_type")->where(['pay_id' => $type])->field("code, name , pay_id,active")->find();
		if(empty($pay_type)){
			return null;
		}
		if($pay_type['active'] == 0){
			return $pay_type['name'] ."服务暂未开通";
		}
		
		$code = convertUnderline($pay_type['code']);
		if(empty($code)){
			return null;
		}
		$cls_name = "\\app\\library\\order\\". $code . "OrderHelper";
		if(class_exists($cls_name)){
			$cls = new $cls_name();
			$cls->pay_id = $pay_type['pay_id'];
			$cls->pay_name = $pay_type['name'];
			return $cls;
		}else{
			return null;
		}
	}
	
	public function get_init_order($customer_id, $sum){
		$this->type = $this->pay_id;
		$res = array(
				'message' => '',
				'errcode' => 0);
		
		if(empty($this->order_number)){
			$this->order_number = createNo();
		}
		
		
		$time = time();
		//        客户端ip
		$ip = \app\library\IpHelper::get_client_ip();
		$this->order = array(
				'order_sn' => $this->order_number,
				'customer_id' => $customer_id,
				'order_amount' => $sum,
				'goods_amount' => $this->goods_amount >= 0 ? $this->goods_amount : $sum,
				'coupon_amount' => 0,
				'full_amount' => 0,
				'integration_amount' => $this->integartion > 0 ? $this->integartion : 0,
				'shopping_coin_amount' => $this->shopping_coin > 0 ? $this->shopping_coin : 0,
				'hongfu_amount' => $this->hongfu > 0 ? $this->hongfu : 0,
				'new_user_amount' => 0,
				'date_add' => $time,
				'ip' => $ip,
				'ip_area' => \app\library\IpHelper::get_location($ip),
				'state' => 1,
				'order_type' => $this->order_type,
				'pay_id' => $this->pay_id,
				'pay_name' => $this->pay_name
		);

		if ($this->order_type == 1) {
            //优惠券判断
            if(!empty($this->coupon_ids) ){
                $coupons = M("customer_coupon")
                    ->alias("cc")
                    ->join("coupon c","c.coupon_id = cc.coupon_id")
                    ->where(['cc.customer_id' => $customer_id,'cc.customer_coupon_id' => ['in',$this->coupon_ids]])
                    ->field('c.amount')
                    ->select();
                $coupon_amount = 0;
                foreach ($coupons as $coupon) {
                    $coupon_amount += $coupon['amount'];
                }
                $sum = $sum - $coupon_amount;
                if($sum < 0){
                    $sum = 0;
                }

                $this->order['coupon_amount'] = $coupon_amount;
            }

            // 积分/会员积分/鸿府积分
            $sum = $sum - $this->integartion;
            $sum = $sum - $this->shopping_coin;
            $sum = $sum - $this->hongfu;
            if($sum < 0){
                $sum = 0;
            }
            //        TODO
            // 是否新用户 是新用户减去相应的钱
            $is_new = M('customer')->where(['customer_id' => $customer_id])->getField("is_new");
            $new_user = \app\library\SettingHelper::get("shuaibo_new_user",['limit' => 0, 'amount' => 10]);
            if ($is_new == 1) {
                $new_user_amount = $new_user['amount'];
            } else {
                $new_user_amount = 0;
            }
            $sum = $sum - $new_user_amount;
            if($sum < 0){
                $sum = 0;
            }
            $this->order['new_user_amount'] = $new_user_amount;

            // 满减
            $full = M('fullcut')
                ->alias('f')
                ->where(['f.active' => 1,'f.limit' => ['elt',$sum]])
                ->field("f.id as full_id,f.name,f.limit,f.amount")
                ->order('f.limit DESC')
                ->find();
            if (!empty($full)) {
                $sum -= $full['amount'];
                if($sum < 0){
                    $sum = 0;
                }
                $this->order['full_amount'] = $full['amount'];
            }
        }

        $this->order['order_amount'] = $sum;
        $this->sum = $sum;
		
		$res = [];

		//如果金额为0，则表示为余额支付
		if($this->sum == 0 && $this->type != 6 && $this->type != 7){
			$this->type = 1;
		}
		//返回参数
		$this->return = array_merge( array(
				'type' => $this->type,
				'order_number' => $this->order_number,
                'amount' => $sum
		), $this->return);
		
		$this->return['goods_name'] = $this->body;
		$this->return['body'] = $this->body;
		$this->return['subject'] = $this->subject;
		
		
		if($this->type == 1 && empty($res)){
			$res = $this->account_pay($customer_id);
		}else if($this->type != 1){
			$res = $this->pay_params($customer_id);
		}
		return $res;
	}
	
	public function account_pay($customer_id){
	    if ($this->type == 1){
            $res = D("customer_model")->CalculateMoney($customer_id, $this->sum,"account");
        }elseif ($this->type == 6){
	        $res = D("customer_model")->CalculateMoney($customer_id,$this->sum,"reward_amount");
        }elseif ($this->type == 7){
            $res = D("customer_model")->CalculateMoney($customer_id,$this->sum,"transfer_amount");
        }

		if($res['errcode'] == 0){
            if ($this->type == 1){
                $this->order['pay_name'] = "余额支付";
                $this->order['pay_id'] = 1;
            }elseif ($this->type == 6){
                $this->order['pay_name'] = '余额宝支付';
                $this->order['pay_id'] = 6;
            }elseif ($this->type == 7){
                $this->order['pay_name'] = "转账金支付";
                $this->order['pay_id'] = 7;
            }
            $this->order['cash_amount'] = $this->sum;
            $this->return['count'] = 0;
		}
		return $res;
	}
	
	public abstract function pay_params($customer_id);
	
	
}