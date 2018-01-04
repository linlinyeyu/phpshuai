<?php
namespace app\admin\controller;

use app\admin\Admin;
class MobileRecharge extends Admin{
	public function index(){
		$map = array();
		$type = I("type");
		if ($type == 1){
			$map['type'] = 1;
		}elseif ($type == 2){
			$map['type'] = 2;
		}
		$fee = M("mobile_recharge_fee")
		->where($map)
		->select();
		$this->assign("fee",$fee);
		$this->display();
	}
	
	public function edit(){
		$mobile_recharge_fee_id = I("mobile_recharge_fee_id");
		if (IS_POST) {
			$data['amount'] = I("amount");
			$data['actual_fee'] = I("actual_fee");
			$data['type'] = I("type");
			if ($mobile_recharge_fee_id){
				$data['date_upd'] = time();
				$res = M("mobile_recharge_fee")->where(['mobile_recharge_fee_id' => $mobile_recharge_fee_id])->save($data);
				if ($res === false){
					$this->error("操作失败");
				}
				$this->log("修改".$data['amount']."充值金额为".$data['actual_fee']);
				$this->success("操作成功",null,U("MobileRecharge/index"));
			}else {
				$data['date_add'] = time();
				$data['date_upd'] = time();
				$res = M("mobile_recharge_fee")->add($data);
				if ($res === false){
					$this->error("操作失败");
				}
				$this->log("添加".$data['amount']."充值金额为".$data['actual_fee']);
				$this->success("操作成功",null,U("MobileRecharge/index"));
			}
		}else {
			if ($mobile_recharge_fee_id){
				$fee = M("mobile_recharge_fee")->where(['mobile_recharge_fee_id' => $mobile_recharge_fee_id])->find();
				$this->assign("fee",$fee);
				$this->display();
			}else{
				$fee = array(
						'mobile_recharge_fee_id' => 0,
						'amount' => '',
						'actual_fee' => '',
						'type' => 1
				);
				
				$this->assign("fee",$fee);
				$this->display();
			}
		}
	}
	
	public function del(){
		!($mobile_recharge_fee_id = I("mobile_recharge_fee_id")) && $this->error("请传入id");
		$res = M("mobile_recharge_fee")->where(['mobile_recharge_fee_id' => $mobile_recharge_fee_id])->delete();
		if ($res === false){
			$this->error("操作失败");
		}
		$this->log("删除充值金额为".M("mobile_recharge_fee")->where(['mobile_recharge_fee_id' => $mobile_recharge_fee_id])->getField("amount")."成功");
		$this->success("操作成功",null,U("MobileRecharge/index"));
	}
}