<?php
namespace app\admin\controller;
use app\admin\Admin;
class Coupon extends Admin
{
	public function index()
	{
		$map = 'c.active = 1';
		$count=M('coupon')->alias("c")->where($map)->count();
		$page = new \com\Page($count, 15);
		$lists = M('coupon')
		->alias("c")
		->join("coupon_type ct","ct.type_id = c.type", "LEFT")
		->field("c.name,c.limit,c.amount,c.date_add, ct.name as coupon_type ,c.use_total,c.coupon_id,c.validate_time,c.validate_type,c.date_expire,c.is_publish")
		->where($map)
		->limit($page->firstRow . ',' . $page->listRows)
		->select();
		$this->assign('page',$page->show());
		$this->assign('lists',$lists);			
		$this->display();
	}

	public function add()
	{
		if(IS_POST){
			$days = (int)I("days");
			$use_total = (int)I('use_total');
			(!$name = I('name')) && $this->error('红包名不能为空');
			(!$amount = (int)I('amount')) && $this->error('红包价值不能为空');
			(!$limit = (int)I('limit')) && $this->error('使用条件不能为空');
			$is_publish = (int)I("is_publish");

			/*$type == 0 && empty($take_condition) && $this->error('领取条件不能为空');
			$type == 0 && empty($use_total) && $this->error("领取次数不能为空");*/
			//TODO 仅为活动红包
			$type = 5;

			$days = empty($days) ? 0 : $days;

			$result = M('coupon')->add([
				'name'=>$name,
				'limit'=>$limit,
				'amount'=>$amount,
				'use_total' => $use_total,
				'date_add'=>time(),
				'date_expire'=>($days*86400),
				'type' => $type,
				'is_publish' => $is_publish
				]);
			D("user_action")->saveAction(session("userid"),7,$result,"添加优惠券".$name);
			$result ? $this->success('成功') : $this->error('失败');
		}else{
			$types = M("coupon_type")->select();
			$this->assign("types",$types);
			$this->display();
		}
	}

	public function edit()
	{
		if(IS_POST){
			(!$coupon_id = (int)I('coupon_id')) && $this->error('参数错误2');
            $days = (int)I("days");
            $use_total = (int)I('use_total');
            (!$name = I('name')) && $this->error('红包名不能为空');
            (!$amount = (int)I('amount')) && $this->error('红包价值不能为空');
            (!$limit = (int)I('limit')) && $this->error('使用条件不能为空');
            (!$is_publish = (int)I("is_publish")) && $this->error("请选择是否发布");

			/*$type == 0 && empty($take_condition) && $this->error('领取条件不能为空');
			$type == 0 && empty($use_total) && $this->error("领取次数不能为空");*/
			//TODO 仅为活动红包
			$type = 5;

			$days = empty($days) ? 0 : $days;
			$coupon = [
                'name'=>$name,
                'limit'=>$limit,
                'amount'=>$amount,
                'use_total' => $use_total,
                'date_add'=>time(),
                'date_expire'=>($days*86400),
                'type' => $type,
                'is_publish' => $is_publish
				];
	
			$result = M('coupon')->where(['coupon_id'=>$coupon_id])->save($coupon);
			D("user_action")->saveAction(session("userid"),7,$coupon_id,"修改优惠券".$name);
			$result ? $this->success('成功') : $this->error('失败');
		}else{
			(!$id = I('id')) && $this->error('参数错误');
			$coupon = M('coupon')
                ->alias("c")
                ->join("coupon_goods cg","cg.coupon_id = c.coupon_id")
			->where(['c.coupon_id'=>$id])
			->field("c.name,GROUP_CONCAT(cg.goods_id) as goods_ids,c.limit,c.amount,c.date_add, c.type,c.use_total,c.coupon_id,c.validate_time,c.validate_type,c.date_expire,c.take_condition,c.is_publish")
			->find();
			$types = M("coupon_type")->select();
			$this->assign("types",$types);
			$this->assign('coupon',$coupon);
			$this->display();
		}
	}

	public function del()
	{
		(!$id = I('id')) && $this->error('参数错误');
		D("user_action")->saveAction(session("userid"),7,$id,"删除优惠券".$id);
        M("coupon_goods")->where(['coupon_id' => $id])->delete();
        M('coupon')->where(['coupon_id'=>$id])->delete() ? $this->success(L('MSG_DEL_SUCCESS')):$this->error(L('MSG_DEL_ERROR'));
	}

    /**
     * 优惠券商品
     */
	public function coupongoods() {
        !($coupon_id = I('id')) && $this->error("请传入优惠券ID");

        $map['cg.coupon_id'] = $coupon_id;
        $goods_name = I("get.goods_name");
        $min_date = I("get.min_date");
        $max_date = I("get.max_date");
        if(!empty($goods_name)){
            $map['g.name'] = ['like',"%" . $goods_name . "%"];
        }
        if(!empty($min_date)){

            $map['g.date_add'] = ['egt', strtotime($min_date)];
        }
        if(!empty($max_date)){
            if (!empty($map['g.date_add']) ) {
                $map['g.date_add'][] =  ['elt', strtotime($max_date) ];
            }else{
                $map['g.date_add'] = ['elt', strtotime($max_date) ];
            }
        }

        $count = M('coupon_goods')
            ->alias("cg")
            ->join("goods g","g.goods_id = cg.goods_id")
            ->where($map)
            ->count();
        $page = new \com\Page($count, 15);

        $goods = M('coupon_goods')
            ->alias("cg")
            ->join("goods g","g.goods_id = cg.goods_id")
            ->where($map)
            ->field("g.name,g.goods_id,g.date_add,cg.coupon_id")
            ->group("g.goods_id")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $this->assign('page',$page->show());
        $this->assign("goods",$goods);
        $this->assign("coupon_id",$coupon_id);
        $this->display();
    }

    public function coupongoodsadd() {
        !($coupon_id = I('id')) && $this->error("请传入优惠券ID");
        $goods_id = I("goods_id");
        if (IS_POST) {
            $coupon_goods = M('coupon_goods')->where(['goods_id' => $goods_id, 'coupon_id' => $coupon_id])->find();
            if (empty($coupon_goods)) {
                M('coupon_goods')->add([
                    'goods_id' => $goods_id,
                    'coupon_id' => $coupon_id
                ]);
                $this->success("成功");
            }
            $this->error("已添加");
        }
        if (empty($goods_id)) {
            $goods = [
                'goods_id' => '',
                'name' => '',
                'shop_name' => ''
            ];
        } else {
            $goods = M('coupon_goods')
                ->alias("cg")
                ->join("goods g","g.goods_id = cg.goods_id")
                ->join("seller_shopinfo ss","ss.seller_id = g.seller_id")
                ->where(['cg.coupon_id' => $coupon_id, 'cg.goods_id' => $goods_id])
                ->field("g.name,g.goods_id,g.date_add,cg.coupon_id,ss.shop_name")
                ->find();
        }

        $this->assign("goods",$goods);
        $this->assign("coupon_id",$coupon_id);
        $this->display();
    }


	public function send()
	{
		(!$cid = (int)I('post.cid')) && $this->error('参数错误');
		(!$username = I('post.username')) && $this->error('用户名不能为空');
		$customer = M('customer')->where("uuid = '".$username."' or nickname = '" . $username . "'")->find();
		!$customer && $this->error('用户不存在');
		
		$res = D("coupon")->trans_coupon($cid, $customer['customer_id']) ;
		if ($res) {
			D("user_action")->saveAction(session("userid"),7,$cid,"分发优惠券".$cid ."至". $customer['nickname']);
		}
		$res ? $this->success("成功") : $this->error("失败");
	}
	
	public function pay_coupon(){
		if(IS_POST){
			!($data = I("post.data") ) && $this->error("请传入数据");
			try{
				$data = json_decode($data, true);
			}catch(\Exception $e){
			}
			
			$pay_coupon = $data['pay_coupon'];
			
			$inc_coupon = $data['inc_coupon'];
			//优惠券状态1表示充值红包，7表示累计充值红包，将其发布状态全部变为0，即未发布
			M("coupon")->where(['type' => ['in', "1 , 7"],'is_publish' => 1])->save(['is_publish' => 0]);
			//充值红包
			if(!empty($pay_coupon)){
				foreach($pay_coupon as $coupon){
					$data = $coupon;
					$data['is_publish'] = 1;
					$data['type'] = 1;
					if(empty($data['coupon_id'])){
						$data['date_add'] = time();
						M("coupon")->add($data);
					}else{
						M("coupon")->save($data);
					}
				}
			}
			//累计充值红包
			if(!empty($inc_coupon)){
				foreach($inc_coupon as $coupon){
					$data = $coupon;
					$data['is_publish'] = 1;
					$data['type'] = 7;
					if(empty($data['coupon_id'])){
						$data['date_add'] = time();
						M("coupon")->add($data);
					}else{
						M("coupon")->save($data);
					}
				}
			}
			$this->success("操作成功");
		}
		$pay_coupon = M("coupon")->where(['type'=> 1])->select();
		$inc_coupon = M("coupon")->where(['type' => 7])->select();
		$this->assign("pay_coupon", $pay_coupon);
		$this->assign("inc_coupon", $inc_coupon);
		$this->display();
	}
	
	public function register_coupon(){
		if(IS_POST){
			(!$data = I("post.data")) && $this->error("请传入数据");
			try{
				$data = json_decode($data, true);
			}catch(\Exception $e){
				$this->error("数据有误");
			}
			
			empty($data['coupon_limit']) || $data['coupon_limit'] < 0 && $data['coupon_limit'] = 0; 
			
			\app\library\SettingHelper::set("bear_register_coupon", $data);
			$this->success("操作成功");
		}
		
		$register_coupon = \app\library\SettingHelper::get("bear_register_coupon",['is_open'=> 0, 'time' => 7 , 'coupon_money' => 1, 'coupon_limit' => 0 ,'interval' => 60 * 60 * 24 , 'expire'=> 60 * 60 * 24]);
		$this->assign("coupon", $register_coupon);
		$this->display();
	}

	// 满减
    public function fc_index() {
        $map = 'f.active = 1';
        $count=M('fullcut')->alias("f")->where($map)->count();
        $page = new \com\Page($count, 15);
        $lists = M('fullcut')
            ->alias("f")
            ->field("f.name,f.limit,f.amount,f.date_add,f.use_total,f.id,f.is_publish")
            ->where($map)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        $this->assign('page',$page->show());
        $this->assign('lists',$lists);
        $this->display();
    }

    public function fc_add() {
        if(IS_POST){
            (!$name = I('name')) && $this->error('满减名不能为空');
            (!$amount = (int)I('amount')) && $this->error('满减价值不能为空');
            (!$limit = (int)I('limit')) && $this->error('使用条件不能为空');
            (!$is_publish = (int)I("is_publish")) && $this->error("请选择是否发布");

            /*$type == 0 && empty($take_condition) && $this->error('领取条件不能为空');
            $type == 0 && empty($use_total) && $this->error("领取次数不能为空");*/
            //TODO 仅为活动红包
            $type = 5;


            $result = M('fullcut')->add([
                'name'=>$name,
                'limit'=>$limit,
                'amount'=>$amount,
                'date_add'=>time(),
                'type' => $type,
                'is_publish' => $is_publish
            ]);
            D("user_action")->saveAction(session("userid"),7,$result,"添加满减".$name);
            $result ? $this->success('成功') : $this->error('失败');
        }else{
            $this->display();
        }
    }

    public function fc_edit()
    {
        if(IS_POST){
            (!$id = (int)I('id')) && $this->error('参数错误2');
            (!$name = I('name')) && $this->error('红包名不能为空');
            (!$amount = (int)I('amount')) && $this->error('红包价值不能为空');
            (!$limit = (int)I('limit')) && $this->error('使用条件不能为空');
            (!$is_publish = (int)I("is_publish")) && $this->error("请选择是否发布");

            /*$type == 0 && empty($take_condition) && $this->error('领取条件不能为空');
            $type == 0 && empty($use_total) && $this->error("领取次数不能为空");*/
            //TODO 仅为活动红包
            $type = 5;

            $fullcut = [
                'name'=>$name,
                'limit'=>$limit,
                'amount'=>$amount,
                'type' => $type,
                'is_publish' => $is_publish
            ];

            $result = M('fullcut')->where(['id'=>$id])->save($fullcut);
            D("user_action")->saveAction(session("userid"),7,$id,"修改满减".$name);
            $result ? $this->success('成功') : $this->error('失败');
        }else{
            (!$id = I('id')) && $this->error('参数错误');
            $fullcut = M('fullcut')
                ->alias("f")
                ->where(['f.id'=>$id])
                ->field("f.name,f.limit,f.amount,f.date_add,f.type,f.id,f.date_expire,f.take_condition,f.is_publish")
                ->find();
            $this->assign('fullcut',$fullcut);
            $this->display();
        }
    }

    public function fc_del()
    {
        (!$id = I('id')) && $this->error('参数错误');
        D("user_action")->saveAction(session("userid"),7,$id,"删除满减".$id);
        M('fullcut')->where(['id'=>$id])->delete() ? $this->success(L('MSG_DEL_SUCCESS')):$this->error(L('MSG_DEL_ERROR'));
    }

    // 新用户
    public function new_user() {
        if(IS_POST){
            $amount = I("amount");
            $limit = I("limit");
            $info = ['amount' => $amount,'limit' => $limit];
            \app\library\SettingHelper::set("shuaibo_new_user", $info);
            $this->success("操作成功");
            //1211065934
        }

        $new_user = \app\library\SettingHelper::get("shuaibo_new_user",['limit' => 0, 'amount' => 10]);
        $this->assign("new_user",$new_user);
        $this->display();
    }
}