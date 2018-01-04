<?php
namespace app\wap\controller;
use think\Controller;
class Address extends \app\wap\UserController
{
	public function getalladdress(){
		$goods_id = (int)cookie("s_goods_id");
		$customer_id = $this->customer_id;

		$address = M("address")
		->where(['customer_id' => $customer_id, 'active' =>1])
		->order("status desc,date_add desc")
		->select();
		
		if($goods_id > 0 && empty($address)){
			$this->redirect(U('address/addaddress'));
		}
		$this->assign("address", $address);
		$this->display();
	}


	public function detaile_getalladdress(){
		$customer_id = $this->customer_id;
		$goods_id = (int)cookie("s_goods_id");
		
		$address = M("address")
		->where(['customer_id' => $customer_id, 'active' =>1])
		->order("status desc,date_add desc")
		->select();
		$this->assign("address", $address);
		$this->display();
	}

	public function addaddress(){

		$customer_id = $this->customer_id;
		$id = (int)I("id");
		$address = null;
		if($id > 0){
			$address = M("address")->where(['customer_id' => $this->customer_id, 'address_id' => $id])->find();
		}
		if(empty($address)){
			$address = M("address")->getEmptyFields();
		}
		$this->assign("address", $address);
		$this->display();
	}
}