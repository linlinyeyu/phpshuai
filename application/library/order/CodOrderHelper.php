<?php
namespace app\library\order;
class CodOrderHelper extends OrderHelper{
	public function pay_params($customer_id){
		
        $this->return['count'] = 0;
		
		return ['errcode' => 0 , 'message' => '成功'];
	}
}