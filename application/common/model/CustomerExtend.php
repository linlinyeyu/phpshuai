<?php

namespace app\common\model;

use think\Model;
use think\Cache;
use app\library\message\Message;

class CustomerExtend extends Model
{

    public function bind_extend($customer_id, $pid = "")
    {

        /*if(empty($pid)){
            $pid = I("uuid");
        }*/

        $now = time();
        if (!empty($pid)) {
            $p_customer = M("customer")->where(['uuid' => $pid])->field("customer_id,group_id")->find();
            if (empty($p_customer)) {
                weixin_log("extend", "extend", $customer_id . "---找不到推广人:" . $pid);
                return "找不到对应人员";
            }
            if ($p_customer['group_id'] <= 2) {
                weixin_log("extend", "extend", $customer_id . "---等级不够:" . $pid);
                return "等级不够，无法绑定人员";
            }


            $pid = $p_customer['customer_id'];
            $relation = $this->where(['customer_id' => $customer_id, 'pid' => $pid, "level" => 1])->find();
            if (!empty($relation)) {
                weixin_log("extend", "extend", $customer_id . "---关系不可重复绑定:" . $pid);
                Cache::rm("bear_bind_extend_" . $customer_id);
                return "关系不可重复绑定";
            }

            $test = M("customer")->where(['customer_id' => $customer_id])->save(['agent_id' => $pid]);
            weixin_log("extend", "extend", $customer_id . "---绑定结果:{$test}; pid:" . $pid);
            $this->bind($customer_id);


            //发送信息
            $client = \app\library\message\MessageClient::getInstance();
            $message = new Message();
            $message->setTargetIds($pid)
                ->setPlatform([Message::PLATFORM_ALL])
                ->setTemplate("register")
                ->setExtras(['customer_id' => $customer_id]);
            $client->pushCache($message);
            Cache::rm("bear_bind_extend_" . $customer_id);
            return true;
        } else {
            weixin_log("extend", "extend", $customer_id . "---找不到推广人:" . $pid);
            Cache::rm("bear_bind_extend_" . $customer_id);
            return "找不到对应的推广人";
        }
    }

    public function bind($customer_id)
    {
        $extends = [];
        $extend_id = $customer_id;

        for ($i = 1; $i <= 3; $i++) {
            $extend = M("customer")
                ->alias("c")
                ->join("customer p", "c.agent_id = p.customer_id")
                ->join("customer_extend ce", "ce.level = $i and ce.customer_id = $customer_id and ce.pid = p.customer_id", "LEFT")
                ->where(['c.customer_id' => $extend_id])
                ->field("ce.*, p.group_id, p.customer_id as ppid")
                ->find();
            if (empty($extend)) {
                break;
            }
            if (!empty($extend['ppid']) && empty($extend['customer_extend_id'])) {
                $data = [
                    'customer_id' => $customer_id,
                    'pid' => $extend['ppid'],
                    'level' => $i,
                    'date_add' => time()
                ];
                $customer_extend_id = $this->add($data);
                $extend['customer_extend_id'] = $customer_extend_id;
                $extend['customer_id'] = $customer_id;
                $extend['level'] = $i;
                $extend['pid'] = $extend['ppid'];
            }

            $extend_id = $extend['ppid'];
            $extends[] = $extend;
        }

        return $extends;
    }

    public function share_commission($customer_id, $order_sns, $state = 1)
    {
        $settings = \app\library\SettingHelper::get("bear_commission", ['is_open' => 1,
            'commission1_rate' => 0.04, 'commission2_rate' => 0.02, 'commission3_rate' => 0,
            'commission1_pay' => 0, 'commission2_pay' => 0, 'commission3_pay' => 0
        ]);
        if (empty($settings['is_open'])) {
            return;
        }

        if (!empty($order_sns) && !is_array($order_sns)) {
            $order_sns = [$order_sns];
        }


        $extends = [];

        $extend_id = $customer_id;

        for ($i = 1; $i <= 3; $i++) {
            $extend = M("customer")
                ->alias("c")
                ->join("customer p", "c.agent_id = p.customer_id")
                ->join("customer_extend ce", "ce.level = $i and ce.customer_id = $customer_id and ce.pid = p.customer_id", "LEFT")
                ->where(['c.customer_id' => $extend_id])
                ->field("ce.*, p.group_id, p.customer_id as ppid")
                ->find();
            if (empty($extend)) {
                break;
            }
            if ($extend['ppid'] && !$extend['customer_extend_id']) {
                $data = [
                    'customer_id' => $customer_id,
                    'pid' => $extend['ppid'],
                    'level' => $i,
                    'date_add' => time(),
                    'commission' => 0
                ];
                $customer_extend_id = $this->add($data);
                $extend['customer_extend_id'] = $customer_extend_id;
                $extend['customer_id'] = $customer_id;
                $extend['level'] = $i;
                $extend['pid'] = $extend['ppid'];
            }

            $extend_id = $extend['ppid'];
            $extends[] = $extend;
        }
        if (empty($extends)) {
            return;
        }
        $order_goods = M("order")
            ->alias("o")
            ->join("order_goods og", "og.order_id = o.id")
            ->join("customer c", "c.customer_id = o.customer_id")
            ->join("goods g", "g.goods_id = og.goods_id")
            ->where(['order_sn' => ['in', $order_sns], 'o.customer_id' => $customer_id, 'o.order_state' => ['in', '1,6']])
            ->field("og.order_id,og.price,o.order_amount, og.quantity,g.goods_id,g.has_commission,g.unique_commission," .
                "g.commission1_rate,g.commission2_rate,g.commission3_rate,g.commission1_pay,g.commission2_pay,g.commission3_pay,g.goods_type")
            ->select();


        $level = 3;

        $datas = [];

        $customers = [];

        $extends_fee = [];
        
        $order = [];
        foreach ($order_goods as $og) {
        	$order[$og['order_id']] = $og['order_amount'];
            if ($og['has_commission'] == 0) {
                continue;
            }

            $commission = $settings;
            if ($og['unique_commission'] == 1) {
                $commission = array_merge($commission, $og);
            }
            if ($og['price'] <= 0 && $og['quantity'] <= 0) {
                continue;
            }

            foreach ($extends as $e) {
                if ($e['group_id'] > 2 || $og['goods_type'] == 4) {
                    if ($e['level'] && $e['level'] <= $level) {
                        $key = 'commission' . $e['level'];
                        $rate = isset($commission[$key . '_rate']) ? $commission[$key . '_rate'] : 0;
                        $pay = isset($commission[$key . '_rate']) ? $commission[$key . '_pay'] : 0;
                        $fee = 0;
                        if ($rate > 0) {

                            $fee = ($rate / 100) * $og['quantity'] * $og['order_amount'];
                        } else if ($pay > 0) {
                            $fee = $pay * $og['quantity'];
                        }
                        if ($fee <= 0) {
                            continue;
                        }
                        
                        $user = D("user")->getUserInfo($e['pid']);
                        if($user['totay_commission'] >= $user['bonus_pool']){
                        	continue;
                        } elseif($user['totay_commission'] + (isset($customers[$e['pid']])?$customers[$e['pid']]:0) + $fee >= $user['bonus_pool']){
                        	$fee = ($user['bonus_pool'] - $user['totay_commission']);
                        	//更新用户总佣金
                        	if (isset($customers[$e['pid']])) {
                        		$customers[$e['pid']] += $fee;
                        	} else {
                        		$customers[$e['pid']] = $fee;
                        	}
                        	
                        	//更新佣金
                        	if (isset($extends_fee[$e['customer_extend_id']])) {
                        		$extends_fee[$e['customer_extend_id']] += $fee;
                        	} else {
                        		$extends_fee[$e['customer_extend_id']] = $fee;
                        	}
                        	
                        	$datas[] = [
                        			'customer_extend_id' => $e['customer_extend_id'],
                        			'commission' => $fee,
                        			'date_add' => time(),
                        			'goods_id' => $og['goods_id'],
                        			'order_id' => $og['order_id'],
                        			'state' => $state
                        	];
                        } else{
                        	//更新用户总佣金
                        	if (isset($customers[$e['pid']])) {
                        		$customers[$e['pid']] += $fee;
                        	} else {
                        		$customers[$e['pid']] = $fee;
                        	}
                        	
                        	//更新佣金
                        	if (isset($extends_fee[$e['customer_extend_id']])) {
                        		$extends_fee[$e['customer_extend_id']] += $fee;
                        	} else {
                        		$extends_fee[$e['customer_extend_id']] = $fee;
                        	}
                        	
                        	$datas[] = [
                        			'customer_extend_id' => $e['customer_extend_id'],
                        			'commission' => $fee,
                        			'date_add' => time(),
                        			'goods_id' => $og['goods_id'],
                        			'order_id' => $og['order_id'],
                        			'state' => $state
                        	];
                        }
                        //D("customer_extend_record")->record($e['customer_extend_id'], $fee,$og['goods_id'], $og['order_id'] , $state);
                    }
               }
            }
        }
        $prefix = C("database.prefix");
        if (!empty($customers)) {
            $sql = "insert into {$prefix}customer (customer_id,commission) values  ";

            foreach ($customers as $k => $v) {
                $sql .= " ($k, $v),";
            }

            $sql = substr($sql, 0, -1);
            $sql .= " on duplicate key update commission= commission + values(commission),bonus_pool = bonus_pool - values(commission)";
        	M()->execute($sql);
        }

        if (!empty($extends_fee)) {
            $sql = "insert into {$prefix}customer_extend (customer_extend_id, commission) values  ";

            foreach ($extends_fee as $k => $v) {
                $sql .= " ($k, $v),";
            }

            $sql = substr($sql, 0, -1);
            $sql .= " on duplicate key update commission = commission + values(commission) ";
            M()->execute($sql);
        }

        if (!empty($datas)) {
            D("customer_extend_record")->addAll($datas);
            $customer = M("customer")->where(['customer_id' => $customer_id])->field("realname")->find();
            $sql = "insert into {$prefix}finance (customer_id,finance_type_id,amount,date_add,extend_id,comments,title) values ";
            $flag = 0;
            foreach ($datas as $data){
            	if ($flag == $data['customer_extend_id']){
            		continue;
            	}
            	$id = M("customer_extend")->where(['customer_extend_id' => $data['customer_extend_id']])->field("customer_id,pid")->find();
            	$sql .= " (".$id['pid'].",2,".$customers[$id['pid']].",".time().",".$customer_id.",'好友".$customer['realname']."消费".$order[$data['order_id']]."','余额+".$customers[$id['pid']]."'),";
            	$flag = $data['customer_extend_id'];
            }
            $sql = substr($sql, 0, -1);
            M()->execute($sql);
            
        }

        return $extends;

    }

    public function refresh($extends)
    {
        if (empty($extends)) {
            return;
        }
        $pids = [];

        $extend_ids = [];

        foreach ($extends as $e) {
            if (isset($e['pid'])) {
                $pids[] = $e['pid'];
            }
            if (isset($e['customer_extend_id'])) {
                $extend_ids[] = $e['customer_extend_id'];
            }
        }
        //升级
        $this->refresh_level($pids);

        //更新佣金
        return $this->refresh_extend_commission($extend_ids);
    }

    public function refresh_extend_commission($extend_ids = [])
    {
        if (empty($extend_ids)) {
            return;
        }
        $commission = $this
            ->alias("ce")
            ->join("customer_extend_record cer", "cer.customer_extend_id = ce.customer_extend_id and cer.state = 1", "LEFT")
            ->field("ce.customer_extend_id , ifnull(sum(cer.commission),0) as commission")
            ->group("ce.customer_extend_id")
            ->where(['ce.customer_extend_id' => ['in', $extend_ids]])
            ->select();

        foreach ($commission as $c) {
            M("customer_extend")
                ->where(['customer_extend_id' => $c['customer_extend_id']])
                ->save(['commission' => $c['commission']]);
        }
        return $commission;
    }

    public function refresh_level($customer_ids = [])
    {
        if (empty($customer_ids)) {
            return;
        }

        $p_customers = M("customer")
            ->alias("c")
            ->join("customer_extend ce", "ce.pid = c.customer_id")
            ->join("customer_extend_record cer", "cer.customer_extend_id = ce.customer_extend_id and cer.state = 1", "LEFT")
            ->join("customer_group cg", "cg.group_id = c.group_id")
            ->field("c.customer_id, c.group_id,cg.upgrade, ifnull(sum(cer.commission),0) as commissions")
            ->group("c.customer_id")
            ->where(['c.customer_id' => ['in', $customer_ids]])
            ->select();
        $levels = \app\library\SettingHelper::get_levels();
        foreach ($p_customers as $p) {
            $group_id = 1;
            $upgrade = 0;
            foreach ($levels as $l) {
                if ($p['commissions'] >= $l['upgrade'] && $upgrade < $l['upgrade']) {
                    $group_id = $l['group_id'];
                    $upgrade = $l['upgrade'];
                }
            }
            if ($p['upgrade'] > $upgrade) {
                $group_id = $p['group_id'];
            }
            if ($group_id < 3) {
                $group_id = 3;
            }

            M("customer")->where(['customer_id' => $p['customer_id']])->save(['group_id' => $group_id, 'commission' => $p['commissions']]);
        }

        return $p_customers;
    }

}