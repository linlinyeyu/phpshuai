<?php
namespace app\admin\controller;

use app\admin\Admin;

//结算类
class Finance extends Admin{
	//保证金
	public function deposit_index(){
		$map = ['sc.is_pay' => 2,'sc.state' => 2];
		$seller_id = I("seller_id");
		if (!empty($seller_id)){
			$map['ss.seller_id'] = $seller_id;
		}
		
		$count = M("seller_check")
		->alias("sc")
		->join("seller_shopinfo ss","ss.customer_id = sc.customer_id")
		->where($map)
		->count();
		
		$page = new \com\Page($count,15);
		$deposit = M("seller_check")
		->alias("sc")
		->join("seller_shopinfo ss","ss.customer_id = sc.customer_id")
		->where($map)
		->field("ss.seller_id,sc.cash_deposit,ss.shop_name")
		->page($page->firstRow.",".$page->listRows)
		->select();
		
		$this->assign("deposit",$deposit);
		$this->assign("page",$page->show());
		$this->display();
	}
	
	//往期结算管理
	public function settlement_history(){
		
	}
	
	//结算
	public function settlement(){
		$map = [];
		$seller_id = I("seller_id");
		$shop_name = '';
		if (!empty($seller_id)){
			$map['ss.seller_id'] = $seller_id;
			$shop_name = M("seller_shopinfo")->where(['seller_id' => $seller_id])->getField("shop_name");
		}
		$min_date = I("min_date");
		$max_date = I("max_date");
		
		if (!empty($min_date)){
			$map['fo.date_add'] = ['egt',strtotime($min_date)];
		}
		if (!empty($max_date)){
			if (!empty($min_date)){
				$map['fo.date_add'][] = ['elt',strtotime($max_date)];
			}else {
				$map['fo.date_add'] = ['elt',strtotime($max_date)];
			}
		}

        // 全部
        $condition = [
            'op1.finance_type' => 1,
            'op1.is_minus' => 2,
            'o.order_state' => ['in','2,3,4,5,7']
        ];
        $condition['_string'] = "op1.customer_id = fo.customer_id";
        $sql1 = M('finance_op')
            ->alias("op1")
            ->where($condition)
            ->join("order o","o.order_sn = op1.order_sn")
            ->field("IFNULL(sum(real_amount),0)")
            ->buildSql();

        // 可提现
        $condition = [
            'op1.finance_type' => 1,
            'op1.is_minus' => 2,
            'o.order_state' => ['in','4,5,7'],
            'cwo.state' => 1
        ];
        $condition['_string'] = "op1.customer_id = fo.customer_id";
        $sql2 = M('finance_op')
            ->alias("op1")
            ->where($condition)
            ->join("order o","o.order_sn = op1.order_sn")
            ->join('customer_withdraw_order cwo','cwo.order_sn = op1.order_sn')
            ->field("IFNULL(sum(real_amount),0)")
            ->buildSql();

		$count = M("finance_op")
		->alias("fo")
		->join("seller_shopinfo ss","ss.customer_id = fo.customer_id")
		->where($map)
		->count("distinct(fo.customer_id)");
		
		$page = new \com\Page($count,15);
		$settlement = M("finance_op")
		->alias("fo")
		->join("seller_shopinfo ss","ss.customer_id = fo.customer_id")
		->join("customer c",'c.customer_id = fo.customer_id')
		->where($map)
		->field("ss.shop_name,c.phone,fo.customer_id,
				ifnull((select sum(amount) from vc_finance_op op1 where op1.finance_type = 1 and op1.is_minus = 2 and op1.customer_id = fo.customer_id),0) as amount,
				ifnull($sql1,0) as real_amount,
				ifnull((select sum(amount) from vc_finance_op op1 where op1.finance_type = 5 and is_minus = 1 and op1.customer_id = fo.customer_id),0) as withdrawals_amount,
				ifnull($sql2,0) as available_amount
				")
		->group("fo.customer_id")
		->page($page->firstRow.",".$page->listRows)
		->select();
		
		$this->assign("settlement",$settlement);
		$this->assign("page",$page->show());
		$this->assign("shop_name",$shop_name);
		$this->display();
	}
	
	public function settlement_excel(){
		$seller_id = I("seller_id");
		$map = [];
		if(!empty($seller_id)){
			$map['ss.seller_id'] = $seller_id;
		}
		
		$min_date = I("min_date");
		$max_date = I("max_date");
		
		if (!empty($min_date)){
			$map['fo.date_add'] = ['egt',strtotime($min_date)];
		}
		if (!empty($max_date)){
			if (!empty($min_date)){
				$map['fo.date_add'][] = ['elt',strtotime($max_date)];
			}else {
				$map['fo.date_add'] = ['elt',strtotime($max_date)];
			}
		}
		
		$settlement = M("finance_op")
		->alias("fo")
		->join("seller_shopinfo ss","ss.customer_id = fo.customer_id")
		->join("customer c",'c.customer_id = fo.customer_id')
		->where($map)
		->field("ss.seller_id,ss.shop_name,c.phone,
				ifnull((select sum(amount) from vc_finance_op op1 where op1.finance_type = 1 and op1.is_minus = 2 and op1.customer_id = fo.customer_id),0) as amount,
				ifnull((select sum(real_amount) from vc_finance_op op1 where op1.finance_type = 1 and op1.is_minus = 2 and op1.customer_id = fo.customer_id),0) as real_amount,
				format(ifnull((select sum(real_amount)/sum(amount) from vc_finance_op op1 where op1.finance_type = 1 and op1.is_minus = 2 and op1.customer_id = fo.customer_id),0),2) as commission_rate,
				ifnull((select sum(amount) from vc_finance_op op1 where op1.finance_type = 2 and is_minus = 1 and op1.customer_id = fo.customer_id),0) as withdrawals_amount,
				ifnull((ifnull((select sum(real_amount) from vc_finance_op op1 where op1.finance_type = 1 and is_minus = 2 and op1.customer_id = fo.customer_id),0) - ifnull((select sum(amount) from vc_finance_op op1 where op1.finance_type = 2 and is_minus = 1 and op1.customer_id = fo.customer_id),0)),0) as available_amount
				")
		->group("fo.customer_id")
		->select();
		
		$headArr = ['店铺id','店铺名','店铺电话','销售额','总佣金','佣金比例','已提现金额','可提现金额'];
		$this->getExcel("settlement_list",$headArr, $settlement);
	}
	
	//往期结算
	public function withdrawals_history(){
		!($customer_id = I("customer_id")) && $this->error("请传入卖家id");
        $map = [
            'cwo.customer_id' => $customer_id,
            'cwo.state' => ['gt',0],
            'cwo.order_amount' => ['gt',0]
        ];
        $shop_name = I("shop_name");
        $phone = I("phone");
        $min_date = I("min_date");
        $max_date = I("max_date");
        if (!empty($min_date)){
            $map['cwo.date_add'] = ['egt',$min_date];
        }
        if (!empty($max_date)){
            if (!empty($min_date)){
                $map['cwo.date_add'][] = ['elt',$max_date];
            }else {
                $map['cwo.date_add'] = ['elt',$max_date];
            }
        }

        $count = M("customer_withdraw_order")
            ->alias('cwo')
            ->where($map)
            ->count();
        $page = new \com\Page($count,15);

		$withdraw_orders = M('customer_withdraw_order')
            ->alias("cwo")
            ->join("order o","o.order_sn = cwo.order_sn")
            ->where($map)
            ->field("cwo.date_add,cwo.order_sn,cwo.order_amount,cwo.commission_amount,cwo.state")
            ->order("cwo.date_add DESC")
            ->limit($page->firstRow.",".$page->listRows)
            ->select();
        foreach ($withdraw_orders as &$order) {
            $order_sn = $order['order_sn'];
            $order['goods'] = M('order')
                ->alias("o")
                ->where(['o.order_sn' => $order_sn])
                ->join("order_goods og","og.order_id = o.id")
                ->join("goods g","g.goods_id = og.goods_id")
                ->field("g.name as goods_name,og.price,og.quantity")
                ->select();
        }

		$this->assign("withdrawals",$withdraw_orders);
		$this->assign("shop_name",$shop_name);
		$this->assign("phone",$phone);
		$this->assign("customer_id",$customer_id);
		$this->assign("page",$page->show());
		$this->display();
	}
	
	public function withdrawals_excel(){
		!($customer_id = I("customer_id")) && $this->error("请传入卖家id");
		$map = ['customer_id' => $customer_id,'finance_type' => 2,'is_minus' => 1];
		$min_date = I("min_date");
		$max_date = I("max_date");
		if (!empty($min_date)){
			$map['date_add'] = ['egt',$min_date];
		}
		if (!empty($max_date)){
			if (!empty($min_date)){
				$map['date_add'][] = ['elt',$max_date];
			}else {
				$map['date_add'] = ['elt',$max_date];
			}
		}
		
		$withdrawals = M("finance_op")
		->where($map)
		->field("amount,DATE_FORMAT(FROM_UNIXTIME(date_add),'%Y-%m-%d')")
		->select();
		
		$headArr = ['提现金额','日期'];
		$this->getExcel("往期结算表", $headArr, $withdrawals);
	}

	public function commission() {
        !($customer_id = I("customer_id")) && $this->error("请传入卖家id");
        $seller_id =  M("seller_user")->where(['customer_id' => $customer_id])->getField("seller_id");
        $map['ss.seller_id'] = $seller_id;
        
        // 全部
        $condition = [
            'op1.finance_type' => 1,
            'op1.is_minus' => 2,
            'o.order_state' => ['in','2,3,4,5,7']
        ];
        $condition['_string'] = "op1.customer_id = fo.customer_id";
        $sql1 = M('finance_op')
            ->alias("op1")
            ->where($condition)
            ->join("order o","o.order_sn = op1.order_sn")
            ->field("IFNULL(sum(real_amount),0)")
            ->buildSql();

        // 可提现
        $condition = [
            'op1.finance_type' => 1,
            'op1.is_minus' => 2,
            'o.order_state' => ['in','4,5,7'],
            'cwo.state' => 1
        ];
        $condition['_string'] = "op1.customer_id = fo.customer_id";
        $sql2 = M('finance_op')
            ->alias("op1")
            ->where($condition)
            ->join("order o","o.order_sn = op1.order_sn")
            ->join('customer_withdraw_order cwo','cwo.order_sn = op1.order_sn')
            ->field("IFNULL(sum(real_amount),0)")
            ->buildSql();

        $settlement = M("finance_op")
            ->alias("fo")
            ->join("seller_shopinfo ss","ss.customer_id = fo.customer_id")
            ->join("customer c",'c.customer_id = fo.customer_id')
            ->where($map)
            ->field("ss.shop_name,c.phone,fo.customer_id,
				ifnull((select sum(amount) from vc_finance_op op1 where op1.finance_type = 1 and op1.is_minus = 2 and op1.customer_id = fo.customer_id),0) as amount,
				ifnull($sql1,0) as real_amount,
				ifnull((select sum(amount) from vc_finance_op op1 where op1.finance_type = 5 and is_minus = 1 and op1.customer_id = fo.customer_id),0) as withdrawals_amount,
				ifnull($sql2,0) as available_amount
				")
            ->group("fo.customer_id")
            ->find();

        $map = [
            'cwo.customer_id' => $settlement['customer_id'],
            'o.order_state' => ['in','1,2,3,4,5,7'],
            'cwo.order_amount' => ['gt',0]
        ];
//        $map['_string'] = " (orr.state is null OR orr.state in (2,6)) ";

        $count = M("customer_withdraw_order")
            ->alias('cwo')
            ->join("order o","o.order_sn = cwo.order_sn")
//            ->join("order_return orr","orr.order_id = o.id","LEFT")
            ->where($map)
            ->count();
        $page = new \com\Page($count,15);

        $withdraw_orders = M('customer_withdraw_order')
            ->alias("cwo")
            ->join("order o","o.order_sn = cwo.order_sn")
//            ->join("order_return orr","orr.order_id = o.id","LEFT")
            ->where($map)
            ->field("cwo.date_add,cwo.order_sn,cwo.order_amount,cwo.commission_amount,cwo.state")
            ->order("cwo.date_add DESC")
            ->limit($page->firstRow.",".$page->listRows)
            ->select();
        foreach ($withdraw_orders as &$order) {
            $order_sn = $order['order_sn'];
            $order['goods'] = M('order')
                ->alias("o")
                ->where(['o.order_sn' => $order_sn])
                ->join("order_goods og","og.order_id = o.id")
                ->join("goods g","g.goods_id = og.goods_id")
                ->field("g.name as goods_name,og.price,og.quantity")
                ->select();
        }

        $this->assign("settlement",$settlement);
        $this->assign("withdrawals",$withdraw_orders);
        $this->assign("page",$page->show());
        $this->display();
    }

    public function excel() {
        !($customer_id = I("customer_id")) && $this->error("请传入卖家id");
        $map = [
            'cwo.customer_id' => $customer_id,
            'o.order_state' => ['in','1,2,3,4,5,7'],
            'cwo.order_amount' => ['gt',0]
        ];
        $lists = M('customer_withdraw_order')
            ->alias("cwo")
            ->join("order o","o.order_sn = cwo.order_sn")
            ->join("order_goods og","og.order_id = o.id")
            ->join("goods g","g.goods_id = og.goods_id")
            ->join("customer c","c.customer_id = o.customer_id")
            ->where($map)
            ->field("o.order_sn,FROM_UNIXTIME(o.date_add) as date_add,GROUP_CONCAT( g.name, '×', og.quantity) as goods_names,c.nickname,cwo.order_amount,cwo.commission_amount,".
                "CASE cwo.state WHEN 1 THEN '可提现' WHEN 2 THEN '申请中' WHEN 3 THEN '已提现' WHEN 4 THEN '已完成' ELSE '待结算' END")
            ->order("cwo.date_add DESC")
            ->group("o.id")
            ->select();
        $filename="佣金明细";
        $headArr=["订单编号","下单时间", "下单商品/数量",'会员名','订单金额','商家佣金',"结算状态"];
        $this->getExcel($filename,$headArr,$lists);
    }
}