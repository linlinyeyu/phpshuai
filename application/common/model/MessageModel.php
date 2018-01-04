<?php
namespace app\common\model;
use think\Model;
use app\library\ActionTransferHelper;
class MessageModel extends Model{
/**
 * 发送信息
 * @param unknown $customer_id
 * @param unknown $title
 * @param unknown $content
 * @param number $type
 * @param string $id
 */

	public function send($customer_id,$title, $content, $type = 0, $id = null){
		 
		
	}
/**
 * 获取客户信息
 * @param unknown $customer_id
 * @param number $page
 */
	public function getCustomerMessage($customer_id,$page = 1 ,$page_size = 10 ,$t = 0 ){

		$this->update_customer_message($customer_id);

		$results = M("message_customer")->alias("mc")
			->join("message m","mc.message_id = m.message_id")
			->join("action a","a.action_id = m.action_id")
			->field("m.message_id ,m.date_add, m.title , m.content ,a.action_id,a.need_login,m.params,a.ios_param,a.android_param,a.web_param, a.wap_param")
			->where(["customer_id" => $customer_id , "mc.is_deleted" => 0 ,"m.is_deleted" => 0])
			->order("m.message_id desc")
			->limit($page_size)
			->page($page)
			->select();
		foreach($results as &$l){
			ActionTransferHelper::display($l);

		}

		return ActionTransferHelper::get_terminal_params($results, $t);

	}
/**
 * 读取客户信息
 * @param unknown $customer_id
 * @param unknown $message_id
 */
	public function readAllMessage($customer_id){
		$this->update_customer_message($customer_id);
		M("message_customer")
			->where(array("customer_id" => $customer_id,"is_deleted" => 0))
			->save(array("is_read" => 1));
	}
/**
 * 删除所有信息
 * @param unknown $customer_id
 */
	public function deleteAllMessage($customer_id){
		$this->update_customer_message($customer_id);
        M("message_customer")
        ->where(array("customer_id" => $customer_id))
        ->save(array("is_deleted" => 1,"is_read" => 1));
	}
/**
 * 获取未读信息数量
 * @param unknown $customer_id
 */
	public function getUnReadCount($customer_id,$type){
//		$this->update_customer_message($customer_id);
		$count = M("message_customer")
            ->alias("mc")
            ->join("message m","m.message_id = mc.message_id AND m.type = ".$type)
            ->where(["mc.customer_id" => $customer_id , "mc.is_read" => 0 ,"mc.is_deleted" => 0])
            ->count();
		return $count;
	}




	private function update_customer_message($customer_id){
		//将tag信息表中数据读入用户信息表


		$customer_id = intval($customer_id);

		$last_message_sql = "select Max(message_id) from vc_message_customer where customer_id = " . $customer_id;
		$customer_tag_sql = "select * from vc_customer_tag where customer_id = " . $customer_id ;
		$message_tag_sql = "select * from vc_message_tag where message_id > (".$last_message_sql.")";


		$sql = "select mt.message_id,count(ct.tag_id) as count,tags_count,and_or_or from"
			. "(" . $customer_tag_sql . ") ct "
			. "join (" . $message_tag_sql . ") mt " . 'ON (ct.tag_id = mt.tag_id or mt.tag_id = 0) and ct.date_subscribe < mt.message_id '
			. "left join vc_message "."ON mt.message_id = vc_message.message_id "
			. "group by(message_id)";


		$results = M()->query($sql);

		//var_dump($results);

		$dataList = [];
		foreach ($results as $l){
			if($l['and_or_or'] == 1){
				if($l['tags_count'] == $l['count']){   //全部满足
					$dataList[] = ["message_id" => $l["message_id"] , "customer_id" => $customer_id];
				}

			}else{
				$dataList[] = ["message_id" => $l["message_id"] , "customer_id" => $customer_id];
			}
		}
		if(!empty($dataList)){
			M("message_customer") -> addAll($dataList);
		}
	}

    /**
     * 消息
     * @param $customer_id
     * @return array
     */
	public function getMessage($customer_id) {
        $express = [
            'unread_count' => 0,
            'content' => '暂无最新物流信息'
        ];
        $sys = [
            'unread_count' => 0,
            'content' => '暂无最新系统信息'
        ];
        $seller_info = \app\library\SettingHelper::get("shuaibo_seller_info",['address' => '杭州市' ,'qq' => '123456789']);
        $ke_qq = $seller_info['qq'];
        // 系统消息
        $sys_message = M('message_customer')
            ->alias("mc")
            ->where(['mc.customer_id' => $customer_id,"mc.is_deleted" => 0])
            ->join("message m","m.message_id = mc.message_id AND m.type = 1")
            ->field("m.message_id,m.content,m.date_add")
            ->order("m.date_add DESC")
            ->find();
        if (!empty($sys_message)) {
            $sys['content'] = $sys_message['content'];
            $sys['date_add'] = $sys_message['date_add'];
            $sys['unread_count'] = $this->getUnReadCount($customer_id,1);
        }

        $message = M('message_customer')
            ->alias("mc")
            ->where(['mc.customer_id' => $customer_id,"mc.is_deleted" => 0])
            ->join("message m","m.message_id = mc.message_id AND m.type = 2")
            ->field("m.message_id,m.order_sn,m.date_add")
            ->order("m.date_add DESC")
            ->find();
        if (!empty($message)) {
            $goods = M('order')
                ->alias('o')
                ->where(['o.customer_id' => $customer_id,'o.order_sn' => $message['order_sn']])
                ->join("order_goods og","og.order_id = o.id")
                ->join("goods g","g.goods_id = og.goods_id")
                ->field("g.name")
                ->select();
            if (!empty($goods)) {
                $express['content'] = $goods[0]['name'];
                $express['date_add'] = $message['date_add'];
                $express['unread_count'] = $this->getUnReadCount($customer_id,2);
            }
        }

        return ['errcode' => 0, 'message' => '成功', 'content' => ['express' => $express, 'kf_qq' => $ke_qq, 'sys_message' => $sys]];
    }

    /**
     * 物流信息
     * @param $customer_id
     * @param $page
     * @param int $t
     * @return mixed
     */
    public function getExpressMessages($customer_id,$page,$t = 0) {
        // 消息置为已读
        M('message_customer')
            ->alias("mc")
            ->join("message m","m.message_id = mc.message_id AND m.type = 2")
            ->where(['mc.customer_id' => $customer_id])
            ->save(['is_read' => 1]);

        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);

        $sql_name = "(SELECT g.name FROM vc_order_goods og INNER JOIN vc_goods g ON g.goods_id = og.goods_id WHERE og.order_id = o.id LIMIT 1)";
        $sql_cover = "(SELECT concat('$host',g.cover,'$suffix') as cover FROM vc_order_goods og INNER JOIN vc_goods g ON g.goods_id = og.goods_id WHERE og.order_id = o.id LIMIT 1)";

        $api = \app\api\ExpressApi::getInstance();

        $messages = M('message_customer')
            ->alias("mc")
            ->where(['mc.customer_id' => $customer_id,"mc.is_deleted" => 0])
            ->join("message m","m.message_id = mc.message_id AND m.type = 2")
            ->join("action a","a.action_id = m.action_id")
            ->join("order o","o.order_sn = m.order_sn")
            ->field("mc.is_read,m.message_id,m.order_sn,m.date_add, m.title , m.content ,a.action_id,a.need_login,m.params,a.ios_param,a.android_param,a.web_param, a.wap_param,$sql_name as goods_name,$sql_cover as goods_cover,".
                "o.express_sn,o.express")
            ->order("m.date_add DESC")
            ->limit(10)
            ->page($page)
            ->select();
        $count = M('message_customer')
            ->alias("mc")
            ->where(['mc.customer_id' => $customer_id,"mc.is_deleted" => 0])
            ->join("message m","m.message_id = mc.message_id AND m.type = 2")
            ->count();
        $pageSize = $count % 10 > 0 ? intval($count / 10 + 1) : intval($count / 10);
        foreach($messages as &$m){
            ActionTransferHelper::display($m);
            $express = $api->expressState($m['express'],$m['express_sn']);
            $m = array_merge($m,$express);
        }

        return ['content' => ActionTransferHelper::get_terminal_params($messages, $t),'pageSize' => $pageSize];
    }

    /**
     * 获取系统消息
     * @param $customer_id
     * @param $page
     * @param int $t
     * @return mixed
     */
    public function getSysMessages($customer_id,$page,$t = 0) {
        // 消息置为已读
        M('message_customer')
            ->alias("mc")
            ->join("message m","m.message_id = mc.message_id AND m.type = 1")
            ->where(['mc.customer_id' => $customer_id])
            ->save(['is_read' => 1]);
        $messages = M('message_customer')
            ->alias("mc")
            ->where(['mc.customer_id' => $customer_id,"mc.is_deleted" => 0])
            ->join("message m","m.message_id = mc.message_id AND m.type = 1")
            ->join("action a","a.action_id = m.action_id")
            ->field("mc.is_read,m.message_id,m.order_sn,m.date_add, m.title , m.content ,a.action_id,a.need_login,m.params,a.ios_param,a.android_param,a.web_param, a.wap_param")
            ->order("m.date_add DESC")
            ->limit(10)
            ->page($page)
            ->select();
        foreach($messages as &$m){
            ActionTransferHelper::display($m);
        }

        return ActionTransferHelper::get_terminal_params($messages, $t);
    }

}