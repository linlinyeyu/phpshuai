<?php
namespace app\app\controller;
use think\Cache;
class Process{
	function __construct(){
		$process = Cache::get("shuaibo_" . ACTION_NAME );
	}

    /**
     * 自动取消订单
     */
	public function cancel_order_process(){
		$test = Cache::get("shuaibo_" .ACTION_NAME);
		if(!empty($test)){
			return;
		}
		Cache::set("shuaibo_" .ACTION_NAME, 1 , 20);

        $orders = M('order')
            ->where(['date_end' => ['lt', time()], 'order_state' => 1])
            ->field("customer_id,order_state,order_sn,date_add,date_cancel,date_end,max_integration,max_shopping_coin")
            ->select();
		
		M("order")->where(['date_end' => ['lt', time()], 'order_state' => 1])->save(['order_state' => 6, 'date_cancel' => time()]);

        foreach ($orders as $order) {
            $data['integration'] = ['exp', 'integration + '. $order['max_integration'] ];
            $data['shopping_coin'] = ['exp', 'shopping_coin + '. $order['max_shopping_coin'] ];
            M('customer')->where(['customer_id' => $order['customer_id']])->save($data);
        }

		Cache::rm("shuaibo_".ACTION_NAME);
		exit;
	}

    /**
     * 自动收货
     */
	public function received_process(){
		$test = Cache::get("shuaibo_" .ACTION_NAME);
		
		if(!empty($test)){
			return;
		}
		Cache::set("shuaibo_" .ACTION_NAME, 1 , 20);
		D("order_model")->batch_receive();
		Cache::rm("shuaibo_".ACTION_NAME);
		exit;
	}
	
	public function refresh_access_token(){
		$config = \app\library\SettingHelper::get_pay_params(4);
		if(!empty($config)){
			$client = \app\library\weixin\WeixinClient::getInstance($config);
			$client->getAccessToken(true);
		}
		Cache::rm("shuaibo_".ACTION_NAME);
	}
	
	public function refresh_rank(){
		\app\library\SettingHelper::set_rank(0);
		\app\library\SettingHelper::set_rank(1);
		\app\library\SettingHelper::set_rank(2);
	}
	
	public function batch_send(){
		$test = Cache::get("shuaibo_" .ACTION_NAME);
		
		if(!empty($test)){
			return;
		}
		Cache::set("shuaibo_" .ACTION_NAME, 1 , 20);
		$datas = S("shuaibo_send_orders");
		if(!empty($datas)){
			$datas = unserialize($datas);
			S("shuaibo_send_orders", null);
			$client = \app\library\message\MessageClient::getInstance();
			foreach ($datas as $o){
				$message = new \app\library\message\Message();
				$message->setAction(\app\library\message\Message::ACTION_ORDER)
				->setPlatform([\app\library\message\Message::PLATFORM_ALL])
				->setTargetIds($o['customer_id'])
				->setTemplate("send")
				->setExtras(['order_sn' => $o['order_sn']])
				->setPushExtra(['title' => '订单发货通知','content' => "您购买的{$o['mini_name']}已发货，快递为：{$o['express_name']}{$o['express_sn']}"])
				->setWeixinExtra(['has_openid' => '1', 'openid' => $o['openid'], 'order' => ['address' => $o['address'],'goods_name' => $o['names'],'order_sn' => $o['order_sn']] ,'express_name' => $o['express_name'],'express_sn' => $o['express_sn']]);
				$client->send($message);
			}
		}
		
		Cache::rm("shuaibo_".ACTION_NAME);
		exit;
	}
	
	public function send_message(){
		$test = Cache::get("shuaibo_".ACTION_NAME);
		if(empty($test)){
			return;
		}
		
		$client = \app\library\message\MessageClient::getInstance();
		$flag = 1;
		while($flag <= 20){
			$message = Cache::lranges("shuaibo_".ACTION_NAME,0,1);
			$message = implode("", $message);
			$message = unserialize($message);
			$client->send($message);
			$message = Cache::lpop("shuaibo_".ACTION_NAME);
			$flag++;
		}
		exit;
	}

	public function overdue_coupon_process() {
        $test = Cache::get("shuaibo_" .ACTION_NAME);
        if(!empty($test)){
            return;
        }
        Cache::set("shuaibo_" .ACTION_NAME, 1 , 20);

        M("customer_coupon")->where(['date_end' => ['lt', time()]])->save(['state' => 2]);

        Cache::rm("shuaibo_".ACTION_NAME);
        exit;
    }

    //更新公钥
    public function upd_pub_key(){
	    $test = Cache::get("shuaibo".ACTION_NAME);
	    if (!empty($test)){
	        return;
        }

        Cache::set("shuaibo".ACTION_NAME,1,20);

        $param = \app\library\SettingHelper::get_pay_params(8);
        $data = array(
            'version' => '1.0',
            'charset' => 'UTF-8',
            'signType' => 'SHA-256',
            'reqData' => [
                'dateTime' => date('YmdHis'),
                'txCode' => 'FBPK',
                'branchNo' => $param['branch_no'],
                'merchantNo' => $param['merchant_no']
            ]
        );

        $sign = sign($data['reqData'],$param['secret']);
        $data['sign'] = $sign;
        $data = json_encode($data);
        $res = httpPost($data,$param['pubkey_address']);
        $res = json_decode($res,true);
        $rsp_data = $res['rspData'];
        if ($rsp_data['rspCode'] == "SUC0000"){
            if (!file_exists("./cert/cmb_pay")){
                mkdir("./cert/cmb_pay",0777,true);
            }
            $file = fopen("./cert/cmb_pay/cmb.pem","w");
            fwrite($file,$rsp_data['fbPubKey']);
            fclose($file);
        }

        Cache::rm("shuaibo".ACTION_NAME);
        exit;
    }

    //奖励金落实
    public function reward_implement(){
        $test = Cache::get("shuaibo".ACTION_NAME);
        if (!empty($test)){
            return;
        }

        Cache::set("shuaibo".ACTION_NAME,1,20);
        $end_time = strtotime("-7 day");
        $orders = M("order")
            ->alias("o")
            ->join("order_info oi",'oi.foregin_infos = o.order_sn')
            ->where(['o.order_state' => ['in',"4,5"],'o.is_reward' => 0,'oi.order_type' => 1])
            ->field("o.customer_id,o.order_sn")
            ->select();

        if (!empty($orders)){
            $order_sns = [];
            foreach ($orders as $order) {
                //计算奖励金
                $agent_id = M("customer")->where(['customer_id' => $order['customer_id']])->getField("agent_id");
                if ($agent_id > 0) {
                    $this->countReward($order['customer_id'],$agent_id,$order['order_sn']);
                }
                $order_sns[] = $order['order_sn'];
            }

            M("order")->where(['order_sn' => ['in',$order_sns]])->save(['is_reward' => 1]);
        }

        Cache::rm("shuaibo".ACTION_NAME);
        exit;
    }

    //检测七天退款
    public function refund_reject(){
        $test = Cache::get("shuaibo".ACTION_NAME);
        if (!empty($test)){
            return;
        }

        Cache::set("shuaibo".ACTION_NAME,1,20);
        $end_time = strtotime("-7 day");
        $orders = M("order")
            ->alias("o")
            ->join("order_return orr",'orr.order_id = o.id')
            ->where(['o.date_received' => ['lt',$end_time],'o.order_state' => ['in',"4,5"],'orr.state' => ['in',"1,3,4,7"]])
            ->field("orr.order_return_id")
            ->select();

        if (!empty($orders)){
            $ids = [];
            foreach ($orders as $order) {
                $ids[] = $order['order_return_id'];
            }
            M("order_return")->where(['order_return_id' => ['in',$ids]])->save(['state' => 6]);
        }

        Cache::rm("shuaibo".ACTION_NAME);
        exit;
    }

    private function currentPv($customer_id,$goods,$order_sn){
        $pv = 0;
        foreach ($goods as $good) {
            $pv += bcmul($good['price'],bcmul($good['pv'],$good['pv_self']));
        }

        M("customer")->where(['customer_id' => $customer_id])->setInc("account",$pv);
        $data = array(
            'customer_id' => $customer_id,
            'finance_type_id' => 3,
            'type' => 12,
            'amount' => $pv,
            'order_sn' => $order_sn,
            'date_add' => time(),
            'comments' => 'pv奖励'.$pv,
            'title' => "+".$pv,
            'is_minus' => 2
        );
        M("finance")->add($data);
    }

    private function countReward($customer_id,$agent_id,$order_sn){
        bcscale(2);
        $goods = M("order_goods")
            ->alias("og")
            ->join("order o",'o.id = og.order_id')
            ->join("goods g",'g.goods_id = og.goods_id')
            ->where(['o.order_sn' => ['in',$order_sn]])
            ->field("og.quantity,og.price,g.reward_fee,g.purchase_fee,g.integra_fee,g.pv,g.pv_superior,g.pv_self,og.max_integration,g.manage_fee")
            ->select();

        $this->currentPv($customer_id,$goods,$order_sn);

        $reward = 0;
        $hongfu = 0;
        $shopping = 0;
        $pv = 0;

        foreach ($goods as $value) {
            if ($value['reward_fee'] != null && $value['reward_fee'] > 0) {
                $temp = bcmul(bcmul($value['quantity'],$value['price']),$value['reward_fee']);
                $reward += bcmul(bcsub(1,$value['integra_fee']),$temp);
                $shopping += bcmul($value['purchase_fee'],bcmul($temp,$value['integra_fee']));
                $hongfu += bcmul(bcsub(1,$value['purchase_fee']),bcmul($temp,$value['integra_fee']));
                $pv += bcmul(bcmul($value['quantity'], $value['price']), $value['pv']*$value['pv_superior']);
            }
        }

        M("customer")->where(['customer_id' => $agent_id])->save(['reward_amount' => ['exp','reward_amount+'.$reward],'hongfu' => ['exp','hongfu+'.$hongfu],'shopping_coin' => ['exp','shopping_coin+'.$shopping],'account' => ['exp','account+'.$pv]]);

        //积分，奖励金，鸿府积分记录
        $data = array(
            array(
                'customer_id' => $agent_id,
                'finance_type_id' => 3,
                'type' => 11,
                'amount' => $reward,
                'order_sn' => $order_sn,
                'date_add' => time(),
                'comments' => '获得奖励金'.$reward.'，订单号'.$order_sn,
                'title' => '+'.$reward,
                'is_minus' => 2
            ),
            array(
                'customer_id' => $agent_id,
                'finance_type_id' => 2,
                'type' => 11,
                'amount' => $shopping,
                'order_sn' => $order_sn,
                'date_add' => time(),
                'comments' => '获得购物积分'.$shopping.",订单号".$order_sn,
                'title' => "+".$shopping,
                'is_minus' => 2
            ),
            array(
                'customer_id' => $agent_id,
                'finance_type_id' => 4,
                'type' => 11,
                'amount' => $hongfu,
                'order_sn' => $order_sn,
                'date_add' => time(),
                'comments' => '获得鸿府积分'.$hongfu.",订单号".$order_sn,
                'title' => "+".$hongfu,
                'is_minus' => 2
            ),
            array(
                'customer_id' => $agent_id,
                'finance_type_id' => 3,
                'type' => 12,
                'amount' => $pv,
                'order_sn' => $order_sn,
                'date_add' => time(),
                'comments' => 'pv奖励'.$pv,
                'title' => "+".$pv,
                'is_minus' => 2
            )
        );

        M("finance")->addAll($data);
    }

	
}