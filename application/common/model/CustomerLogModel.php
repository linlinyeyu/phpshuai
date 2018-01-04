<?php
namespace app\common\model;
use think\Model;
class CustomerLogModel extends Model{
	const EDIT_PHONE = 1;

	const EDIT_PWD = 2;

	const EDIT_AVATER = 3;

	const EDIT_ADDRESS = 4;

	const SEND_CODE = 5;
/**
 * 客户日志信息
 * @param unknown $customer_id
 * @param unknown $type
 * @param unknown $comment
 */
	public function Log($customer_id, $type, $comment){
			$comment = date("Y-m-d H:i:s") . ":" . M("customer")->where(['customer_id' => $customer_id])->getField("nickname") . " =>" . $comment;
			$this->add(['customer_id' => $customer_id, 'type' => $type, 'comment' => $comment,'date_add' => time(), 'ip' => get_client_ip()]);
	}

	
}