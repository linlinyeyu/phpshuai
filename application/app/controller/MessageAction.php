<?php 
namespace app\app\controller;
class MessageAction{

/**
 * 获取所有信息
 */
	public function GetAllMessage(){

		$res = array(
				'message' => '请求成功',
				'errcode' => 0
			);
		$customer_id = get_customer_id();
		
		if(empty($customer_id) ||$customer_id <= 0){
            return ['errcode' => 99, 'message' => '请重新登陆'];
        }
		$page = (int)I("page");
		if($page <= 0){
			$page = 1;
		}

		$page_size = (int)I("page_size");
		if($page_size <= 0){
			$page_size = 10;
		}
 
		$t = (int)I("t");
		if($t <= 0){
			$t = 0;
		}

		$res['content'] = D("message")->getCustomerMessage($customer_id, $page,$page_size ,$t);
		return $res;
	}
/**
 * 读取信息
 */
	public function ReadAllMessage(){
		$res = array(
				'message' => '请求成功',
				'errcode' => 0
			);
		$customer_id = get_customer_id();
// 读取信息进行判断
		if(empty($customer_id) ||$customer_id <= 0){
            return ['errcode' => 99, 'message' => '请重新登陆'];
        }

		D("message")->readAllMessage($customer_id);
		return $res;
	}
/**
 * 删除所有信息
 */
	public function DeleteAllMessage(){
		$res = array(
				'message' => '请求成功',
				'errcode' => 0
			);
		$customer_id = get_customer_id();
// 判断某个用户
		if(empty($customer_id) ||$customer_id <= 0){
            return ['errcode' => 99, 'message' => '请重新登陆'];
        }
//  删除某用户所有信息
		D("message") -> deleteAllMessage($customer_id);
		return $res;
	}

	/**
	 * 获取未读信息
	 */
	public function getUnReadCount(){

		$res = array(
			'message' => '请求成功',
			'errcode' => 0
		);
		$customer_id = get_customer_id();

		if(empty($customer_id) ||$customer_id <= 0){
			return ['errcode' => 99, 'message' => '请重新登陆'];
		}

		$res['content'] = D("message")->getUnReadCount($customer_id);
		return $res;

	}

    /**
     * 消息
     * @return array
     */
	public function getMessage() {
        $customer_id = get_customer_id();
        if(empty($customer_id) ||$customer_id <= 0){
            return ['errcode' => 99, 'message' => '请重新登陆'];
        }
        return D("message_model")->getMessage($customer_id);
    }

    /**
     * 物流信息
     * @return array
     */
    public function getExpressMessages() {
        $customer_id = get_customer_id();
        if(empty($customer_id) ||$customer_id <= 0){
            return ['errcode' => 99, 'message' => '请重新登陆'];
        }
        $page = (int)I("page");
        if($page <= 0){
            $page = 0;
        }
        $t = (int)I("t");
        if($t <= 0){
            $t = 0;
        }
        $res = D('message_model')->getExpressMessages($customer_id,$page,$t);
        return ['errcode' => 0, 'message' => '成功', 'content' => $res['content'], 'pageSize' => $res['pageSize']];
    }

    /**
     * 读取消息
     * @return array
     */
    public function readMessage() {
        $customer_id = get_customer_id();
        if(empty($customer_id) ||$customer_id <= 0){
            return ['errcode' => 99, 'message' => '请重新登陆'];
        }
        M('message_customer')->where(['customer_id' => $customer_id])->save(['is_read' => 1]);
        return ['errcode' => 0, 'message' => '成功'];
    }

    /**
     * 获取系统消息
     * @return array
     */
    public function getSysMessages() {
        $customer_id = get_customer_id();
        if(empty($customer_id) ||$customer_id <= 0){
            return ['errcode' => 99, 'message' => '请重新登陆'];
        }
        $page = (int)I("page");
        if($page <= 0){
            $page = 0;
        }
        $t = (int)I("t");
        if($t <= 0){
            $t = 0;
        }
        $res = D('message_model')->getSysMessages($customer_id,$page,$t);
        return ['errcode' => 0, 'message' => '成功', 'content' => $res];
    }
}