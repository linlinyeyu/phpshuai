<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Coupon extends Admin
{
	public function index()
	{
		$map = 'c.type = 5';
		$count=M('coupon')->alias("c")->where($map)->count();
		$page = new \com\Page($count, 15);
		$lists = M('coupon')
		->alias("c")
		->join("coupon_type ct","ct.type_id = c.type", "LEFT")
		->field("c.coupon_name,c.coupon_limit,c.coupon_money,c.date_add, ct.type_name as coupon_type ,c.use_total,c.coupon_id,validate_time,c.validate_type,c.date_expire,c.is_publish")
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
			$validate_time = I("validate_time");
			$take_condition = (int)I('take_condition');
			$use_total = (int)I('use_total');
			$type = (int)I("coupon_type");
			$validate_type = (int)I("validate_type");
			$goods_type = (int)I("goods_type");
			$goods_id = (int)I("goods_id");
			(!$name = I('name')) && $this->error('红包名不能为空');
			(!$amount = (int)I('amount')) && $this->error('红包价值不能为空');
			(!$limit = (int)I('limit')) && $this->error('使用条件不能为空');
			(!$is_publish = (int)I("is_publish")) && $this->error("请选择是否发布");
			($validate_type == 0 && empty($days)) && $this->error("请输入过期天数");
			($validate_type == 1 && empty($validate_time)) && $this->error("请输入日期");
			($goods_type == 1 && empty($goods_id)) && $this->error("请传入商品信息");
			
			/*$type == 0 && empty($take_condition) && $this->error('领取条件不能为空');
			$type == 0 && empty($use_total) && $this->error("领取次数不能为空");*/
			//TODO 仅为活动红包
			$type = 5;
			
			
			$validate_time = empty($validate_time) ? 0 :strtotime($validate_time);

			$days = empty($days) ? 0 : $days;

			$result = M('coupon')->add([
				'coupon_name'=>$name,
				'take_condition' => $take_condition,
				'coupon_limit'=>$limit,
				'coupon_money'=>$amount,
				'use_total' => $use_total,
				'validate_type' => $validate_type,
				'validate_time' => $validate_time,
				'date_add'=>time(),
				'date_expire'=>($days*86400),
				'type' => $type,
				'goods_type' => $goods_type,
				'goods_id' =>$goods_id,
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
			$is_publish = (int)I("is_publish");
			$validate_type = (int)I("validate_type");
			$validate_time = I("validate_time");
			$take_condition = (int)I('take_condition');
			$use_total = (int)I('use_total');
			$type = (int)I("coupon_type");
			$goods_type = (int)I("goods_type");
			$goods_id = (int)I("goods_id");
			(!$name = I('name')) && $this->error('红包名不能为空');
			(!$amount = (int)I('amount')) && $this->error('红包价值不能为空');
			(!$limit = (int)I('limit')) && $this->error('使用条件不能为空');
			($validate_type == 0 && empty($days)) && $this->error("请输入过期天数");
			($validate_type == 1 && empty($validate_time)) && $this->error("请输入日期");
			($goods_type == 1 && empty($goods_id)) && $this->error("请传入商品信息");

			/*$type == 0 && empty($take_condition) && $this->error('领取条件不能为空');
			$type == 0 && empty($use_total) && $this->error("领取次数不能为空");*/
			//TODO 仅为活动红包
			$type = 5;

			$validate_time = empty($validate_time) ? 0 :strtotime($validate_time);
			
			$days = empty($days) ? 0 : $days;
			$coupon = [
				'coupon_name'=>$name,
				'take_condition' => $take_condition,
				'coupon_limit'=>$limit,
				'coupon_money'=>$amount,
				'use_total' => $use_total,
				'validate_type' => $validate_type,
				'validate_time' => $validate_time,
				'date_add'=>time(),
				'date_expire'=>$days * 86400,
				'type' => $type,
				'is_publish' => $is_publish,
				'goods_type' => $goods_type,
				'goods_id' => $goods_id
				];
	
			$result = M('coupon')->where(['coupon_id'=>$coupon_id])->save($coupon);
			D("user_action")->saveAction(session("userid"),7,$coupon_id,"修改优惠券".$name);
			$result ? $this->success('成功') : $this->error('失败');
		}else{
			(!$id = I('id')) && $this->error('参数错误');
			$coupon = M('coupon')->alias("c")
			->join("goods g","g.goods_id = c.goods_id ", "LEFT")
			->where(['coupon_id'=>$id])
			->field("c.coupon_name,c.goods_type,g.name as goods_name,c.coupon_limit,c.coupon_money,c.date_add,c.goods_id, c.type,c.use_total,c.coupon_id,c.validate_time,c.validate_type,c.date_expire,c.take_condition,c.is_publish")
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
		M('coupon')->where(['coupon_id'=>$id])->delete() ? $this->success(L('MSG_DEL_SUCCESS')):$this->error(L('MSG_DEL_ERROR'));
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
	
}