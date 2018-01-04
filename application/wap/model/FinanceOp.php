<?php
namespace app\app\model;
use think\Model;
class FinanceOp extends Model{
	
	public function record($customer_id, $type,$foreign_id,$amount, $edit = true){
		
		$data = ['customer_id' => $customer_id,
				'type'=>$type,
				'type_name' => get_pay_name($type),
				'amount'=> $amount,
				'foreign_id' =>$foreign_id,
				'date_add' => time()
		];
		$this->add($data);
		
		if($edit){
			M("customer")->where(['customer_id' => $customer_id])->setInc("account", $amount);
		}
		
	}
}