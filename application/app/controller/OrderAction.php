<?php

namespace app\app\controller;

use think\Cache;

class OrderAction
{
    /**
     * 立即购买/结算
     */
    public function buy() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        // [{"goods":{"cart_id":"","goods_id":"","option_id":"","quantity":""},"seller_id":""}]
        $data = json_decode(I('data'),true);
        if (empty($data)) {
            return ['errcode' => -101, 'message' => '请传入商品信息'];
        }
        return D('order_model')->buy($customer_id,$data);
    }

    /**
     * 获取可用优惠券
     * @return array
     */
    public function getCoupons() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        // [{"goods_id":"","quantity":"","option_id":""}]
        $goods = I('goods');
        if (empty($goods)) {
            return ['errcode' => -100, 'message' => "请传入商品信息"];
        }
        return D('order_model')->getCoupons($customer_id,$goods,1);
    }

    /**
     * 不可用优惠券
     * @return array
     */
    public function getUnableCoupons() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        // [{"goods_id":"","quantity":"","option_id":""}]
        $goods = I('goods');
        if (empty($goods)) {
            return ['errcode' => -100, 'message' => "请传入商品信息"];
        }
        return D('order_model')->getCoupons($customer_id,$goods,0);
    }

    /**
     * 操作优惠券
     * @return array
     */
    public function useCoupon() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $seller_id = I('seller_id');
        if (empty($seller_id)) {
            return ['errcode' => -101, 'message' => '请传入店铺信息'];
        }
        $use_type = (int)I('use_type');
        if ($use_type <= 0) {
            $use_type = 0;
        }
        $coupon_id = I('coupon_id');

        return D('order_model')->useCoupon($customer_id,$use_type,$coupon_id,$seller_id);
    }

    /**
     * 确认订单
     */
    public function generate() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $address_id = I('address_id');
        if (empty($address_id)) {
            return ['errcode' => -100, 'message' => '请传入地址信息'];
        }
        // [{"goods":{"cart_id":"","goods_id":"","option_id":"","quantity":""},"remark":"","seller_id":"","use_type":"","coupon":""}]
        $data = json_decode(I('data'),true);
        if (empty($data)) {
            return ['errcode' => -101, 'message' => '请传入商品信息'];
        }

        return D('order_model')->generate($customer_id,$address_id,$data);
    }

    /**
     * 支付
     */
    public function pay() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $orders = I("orders");

        $type = I("type");

        if (empty($orders)) {
            return ['errcode' => -101, 'message' => '请传入订单信息'];
        }

        return D('order_model')->pay($customer_id,$type,$orders);
    }

    /**
     * 订单列表
     */
    public function getOrders() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $state = (int)I('state');
        $type = (int)I("type");

        $page = I("page");
        if ($page <= 0) {
            $page = 1;
        }

        return D('order_model')->getOrders($customer_id,$state,$page,$type);
    }

    /**
     * 订单详情
     */
    public function getOrderDetail()
    {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $order_sn = I("order_sn");
        if(empty($order_sn)){
            return ['errcode' => -100, 'message' => "找不到相关订单"];
        }

        return D("order_model")->getOrderDetail($customer_id, $order_sn);
    }

    /**
     * 订单列表/详情 立即付款
     * @return array
     */
    public function payment() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $order_sn = I("order_sn");
        if(empty($order_sn)){
            return ['errcode' => -100, 'message' => "找不到相关订单"];
        }
        return D('order_model')->payment($customer_id,$order_sn);
    }

    /**
     *  取消订单
     */
    public function cancelOrder() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $order_sn = I("order_sn");
        if(empty($order_sn)){
            return ['errcode' => -100, 'message' => "找不到相关订单"];
        }
        return D("order_model")->cancelOrder($customer_id,$order_sn);
    }

    /**
     * 提醒发货
     */
    public function orderRemind(){
        $customer_id = get_customer_id();
        if(empty($customer_id)){
            return ['errcode' => 99, 'message' => '请重新登录'];
        }

        $order_sn = I("order_sn");
        if(empty($order_sn)){
            return ['errcode' => -101, 'message' => '请传入订单编号'];
        }
        $order = M("order")->where(['order_sn' => $order_sn])->field("id,order_state")->find();
        if(empty($order)){
            return ['errcode' => -101, 'message' => '请传入正确订单编号'];
        }
        if($order['order_state'] != 2){
            return ['errcode' => -103, 'message' => '订单未处于待发货状态'];
        }
        $data = array(
            'order_id' => $order['id'],
            'customer_id' => $customer_id,
            'date_add' => time()
        );
        M("order_remind")->add($data);
        return ['errcode' => 0, 'message' => '提醒成功'];
    }

    /**
     * 确认收货
     */
    public function delivery() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $order_sn = I("order_sn");
        if(empty($order_sn)){
            return ['errcode' => -100, 'message' => "找不到相关订单"];
        }
        return D("order_model")->receivedOrder($customer_id,$order_sn);
    }

    /**
     * 商品评论
     * @return array
     */
    public function commentGoods() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }

//       [{"goods_id":"","comment":"","score":"","images":""}]
        $comments = I("comments");
        if(empty($comments)){
            return ['errcode' => -100, 'message' => '请传入评论'];
        }
        $comments = json_decode($comments,true);
        $order_sn = I("order_sn");
        $service_score = (int)I('service_score');
        $logistics_score = (int)I('logistics_score');
        return D("order_model")->commentGoods($customer_id,$order_sn,$comments,$service_score,$logistics_score);
    }

    /**
     * 获取订单评论
     * @return array
     */
    public function getCommnets() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $order_sn = I('order_sn');
        if (empty($order_sn)) {
            return ['errcode' => -100, 'message' => '请传入订单信息'];
        }
        return D('order_model')->getComments($customer_id,$order_sn);
    }

    /**
     * 追加评论
     * @return array
     */
    public function addComment() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }

//       [{"comment_id":"","comment":"","images":""}]
        $comments = I("comments");
        if(empty($comments)){
            return ['errcode' => -100, 'message' => '请传入评论'];
        }
        $comments = json_decode($comments,true);
        $order_sn = I("order_sn");
        return D("order_model")->addComment($customer_id,$order_sn,$comments);
    }

    /**
     * 申请退款
     * @return array
     */
    public function refund() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $order_sn = I('order_sn');
        if (empty($order_sn)) {
            return ['errcode' => -100, 'message' => '请传入退款订单信息'];
        }
        $goods_id = (int)I('goods_id');
        if ($goods_id <= 0) {
            return ['errcode' => -101, 'message' => '请传入退款商品信息'];
        }
        $option_id = I('option_id');
        if (empty($option_id)) {
            $option_id = 0;
        }
        $type = (int)I('type');
        if ($type <= 0) {
            return ['errcode' => -102, 'message' => '请传入退款方式'];
        }
        $price = round(I('money'), 2);
        $reason = I('reason');
        $images = I('images');

        return D('order_model')->refund($customer_id,$order_sn,$goods_id,$option_id,$type,$price,$reason,$images);
    }

    /**
     * 获取申请售后页面数据
     * @return array
     */
    public function getRefundInfo() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $order_sn = I('order_sn');
        if (empty($order_sn)) {
            return ['errcode' => -100, 'message' => '请传入退款订单信息'];
        }
        $goods_id = (int)I('goods_id');
        if ($goods_id <= 0) {
            return ['errcode' => -101, 'message' => '请传入退款商品信息'];
        }
        $option_id = I('option_id');
        if (empty($option_id)) {
            $option_id = 0;
        }
        return D('order_model')->getRefundInfo($customer_id,$order_sn,$goods_id,$option_id);
    }

    /**
     * 获取退货单信息
     * @return array
     */
    public function getReturnNote() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $refund_sn = I('refund_sn');
        if (empty($refund_sn)) {
            return ['errcode' => -100, 'message' => '请传入退款信息'];
        }
        return D('order_model')->getReturnNote($customer_id,$refund_sn);
    }

    /**
     * 填写/修改退货单
     * @return array
     */
    public function writeReturnNote() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $refund_sn = I('refund_sn');
        if (empty($refund_sn)) {
            return ['errcode' => -100, 'message' => '请传入退款信息'];
        }
        $express = I('express');
        if (empty($express)) {
            return ['errcode' => -101, 'message' => '请传入物流公司'];
        }
        $express_sn = I('express_sn');
        if (empty($express_sn)) {
            return ['errcode' => -102, 'message' => '请传入物流单号'];
        }
        return D('order_model')->writeReturnNote($customer_id,$refund_sn,$express,$express_sn);
    }

    /**
     * 获取物流信息
     * @return array
     */
    public function getExpress() {
        $express = D('express_model')->getAddOrderShow();
        return ['errcode' => 0, 'message' => '成功', 'content' => $express];
    }

    /**
     * 获取退款列表
     * @return array
     */
    public function getRefunds() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $page = (int)I('page');
        $type = (int)I("type");
        if ($page <= 0) {
            $page = 1;
        }
        return D('order_model')->getRefunds($customer_id,$page,$type);
    }

    /**
     * 退款留言
     * @return array
     */
    public function refundMessage() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $refund_sn = I('refund_sn');
        if (empty($refund_sn)) {
            return ['errcode' => -100, 'message' => '请传入退款信息'];
        }
        $content = I('content');
        if (empty($content)) {
            return ['errcode' => -101, 'message' => '请输入留言内容'];
        }
        return D('order_model')->refundMessage($customer_id,$refund_sn,$content);
    }

    /**
     * 售后详情
     * @return array
     */
    public function supportDetail() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $refund_sn = I('refund_sn');
        if (empty($refund_sn)) {
            return ['errcode' => -100, 'message' => '请传入退款信息'];
        }
        return D('order_model')->supportDetail($customer_id,$refund_sn);
    }

    /**
     * 获取退款申请详情
     * @return array
     */
    public function refundDetail() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $refund_sn = I('refund_sn');
        if (empty($refund_sn)) {
            return ['errcode' => -100, 'message' => '请传入退款信息'];
        }
        return D('order_model')->refundDetail($customer_id,$refund_sn);
    }

    /**
     * 修改申请
     * @return array
     */
    public function changeRefund() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $refund_sn = I('refund_sn');
        if (empty($refund_sn)) {
            return ['errcode' => -100, 'message' => '请传入退款信息'];
        }
        $type = (int)I('type');
        if ($type <= 0) {
            return ['errcode' => -102, 'message' => '请传入退款方式'];
        }
        $price = round(I('money'), 2);
        $reason = I('reason');
        $images = I('images');
        return D('order_model')->changeRefund($customer_id,$refund_sn,$type,$price,$reason,$images);
    }

    /**
     * 撤销申请
     * @return array
     */
    public function revokerRefund() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $refund_sn = I('refund_sn');
        if (empty($refund_sn)) {
            return ['errcode' => -100, 'message' => '请传入退款信息'];
        }
        return D('order_model')->revokerRefund($customer_id,$refund_sn);
    }

    /**
     * 催一催
     * @return array
     */
    public function refundRemind() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $refund_sn = I('refund_sn');
        if (empty($refund_sn)) {
            return ['errcode' => -100, 'message' => '请传入退款信息'];
        }
        return D('order_model')->refundRemind($customer_id,$refund_sn);
    }

    /**
     * 删除订单
     */
    public function delOrder(){
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $order_sn = I("order_sn");
        if (empty($order_sn)) {
            return ['errcode' => -100, 'message' => '请传入订单信息'];
        }
        return D('order_model')->delOrder($customer_id,$order_sn);
    }

    /**
     * 删除售后记录
     * @return array
     */
    public function delRefund() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $refund_sn = I("refund_sn");
        if (empty($refund_sn)) {
            return ['errcode' => -100, 'message' => '请传入退款信息'];
        }
        return D('order_model')->delRefund($customer_id,$refund_sn);
    }

    /**
     * 物流信息
     * @return array
     */
    public function express() {
        $customer_id = get_customer_id();
        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $type = I('type');
        if (empty($type)) {
            $type = 'auto';
        }
        $number = I('number');
        if (empty($number)) {
            return ['errcode' => -100, 'message' => '请传入物流单号'];
        }

        $api = \app\api\ExpressApi::getInstance();
        return $api->express($type,$number);
    }

    /**
     * 获取运费
     * @return array
     */
    public function getExpressFee()
    {
        $shops = I("shops");
        if (empty($shops)) {
            return ['errcode' => -101, 'message' => '请传入商品信息'];
        }

        $shops = json_decode($shops,true);
        $address_id = (int)I("address_id");
        $express_fees = [];
        foreach ($shops as $shop) {
            $express_fee = 0;
            foreach ($shop['goods'] as $goods_info){
                $express_fee += D("express_template")->calculateExpress($goods_info['goods_id'], $address_id, 1, $goods_info['option_id']) * (int)$goods_info['quantity'];
            }
            $content['seller_id'] = $shop['seller_id'];
            $content['express_fee'] = $express_fee;
            $express_fees[] = $content;
        }
        return ['errcode' => 0, 'message' => '请求成功', 'content' => $express_fees];
    }














    /**
     * 提交退款申请
     */
    /*
    public function refund()
    {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }

        $order_sn = I("order_sn");
        if (empty($order_sn)) {
            return ['errcode' => -100, 'message' => '找不到相关订单'];
        }

        $return_reason_id = I('return_reason_id');
        if (empty($return_reason_id)) {
            return ['errcode' => -101, 'message' => '请选择退款原因'];
        }

        $return_method = I("return_method");
        if(empty($return_method)){
            return ['errcode' => -101, 'message' => '请选择退款方式'];
        }

        $other_reason = I("other_reason");
        if(empty($other_reason)){
            return ['errcode' => -101, 'message'];
        }

        $price = round(I('money'), 2);
        if ($price <= 0) {
            return ['errcode' => -102, 'message' => '请输入金额'];
        }


        $remark = I('remark');

        $order = M('order')
            ->alias("o")
            ->where(['o.order_sn' => $order_sn, 'o.customer_id' => $customer_id])
            ->join("order_return orr", "orr.order_id = o.id and orr.state in (1,2)", "LEFT")
            ->join("customer c", "c.customer_id = o.customer_id")
            ->join("customer_extend_record cer", "cer.order_id = o.id", "LEFT")
            ->field("c.nickname,o.id,c.active, o.order_amount,o.order_state, orr.order_return_id, c.customer_id,c.agent_id,cer.customer_extend_id")
            ->find();
        if (empty($order)) {
            return ['errcode' => -105, 'message' => '找不到相关订单'];
        }
        if ($order['active'] == 0) {
            $seller_info = \app\library\SettingHelper::get("shuaibo_seller_info", ["service_phone" => '15906716507', 'address' => '杭州市']);
            return ['errcode' => -106, 'message' => '抱歉，您无法执行此操作，如有疑问，请拨打客服电话:' . $seller_info['service_phone']];
        }

        if (!in_array($order['order_state'], [2, 3])) {
            return ['errcode' => -107, 'message' => '订单状态必须为待收货或待发货状态'];
        }

        if ($order['order_amount'] < $price) {
            return ['errcode' => -106, 'message' => '退款金额不得大于订单金额'];
        }

        if (!empty($order['order_return_id'])) {
            return ['errcode' => -108, 'message' => '您已申请过退款，无法再次申请'];
        }

        $refund_sn = createNo("order_return", "refund_sn", "SR");
        $data = [
            'order_id' => $order['id'],
            'customer_id' => $customer_id,
            'return_reason_id' => $return_reason_id,
            'other_reason' => $other_reason,
            'state' => '1',
            'price' => $price,
            'remark' => $remark,
            'date_add' => time(),
            'refund_sn' => $refund_sn,
            'order_state' => $order['order_state']
        ];

        $return_id = M('order_return')->add($data);
        if (empty($return_id)) {
            return ['errcode' => -103, 'message' => '添加失败'];
        }

        M('order')->where(['order_sn' => $order_sn, 'customer_id' => $customer_id])->save(['order_state' => '7', 'date_refund' => time()]);

        $client = \app\library\message\MessageClient::getInstance();

//        $message = new \app\library\message\Message();
//        $message->setTargetIds($customer_id)
//            ->setExtras(['order_sn' => $order_sn, 'title' => '退款申请通知', 'content' => '您已提交退款申请，可在订单中心查看退款进度'])
//            ->setTemplate("refund")
//            ->setAction(\app\library\message\Message::ACTION_ORDER)
//            ->setPlatform([\app\library\message\Message::PLATFORM_ALL]);
//
//        $client->pushCache($message);
//
//        if ($order['agent_id'] > 0 && $order['customer_extend_id']) {
//            $message
//                ->setAction(\app\library\message\Message::ACTION_NONE)
//                ->setWeixinExtra(['ignore_url' => 1])
//                ->setTargetIds($order['agent_id'])
//                ->setExtras(['order_sn' => $order_sn, 'title' => '退款申请通知', 'content' => "您的朋友{$order['nickname']}已提交退款申请"]);
//
//            $client->pushCache($message);
//        }
        return ['errcode' => 0, 'message' => '请求成功'];
    }*/





    /**
     * 确认收货
     */
    /*
    public function delivery()
    {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }

        $order_sn = I("order_sn");
        if (empty($order_sn)) {
            return ['errcode' => -100, 'message' => '找不到相关订单'];
        }

        $result = D("order")->received($customer_id, $order_sn);

        return $result;
    }
    */



    
    /**
     * 退款售后
     */
    public function refundList(){
    	$customer_id = get_customer_id();
    	
    	if (empty($customer_id)) {
    		return ['errcode' => 99, 'message' => '请重新登录'];
    	}
    	
    	$host = \app\library\SettingHelper::get("shuaibo_image_url");
    	$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
    	
    	$orders = M("order_return")
    	->alias("ore")
    	->join("order o",'o.id = ore.order_id')
    	->join("order_goods og","og.order_id = o.id")
    	->join("goods g","g.goods_id = og.goods_id")
    	->where(['ore.customer_id' => $customer_id,'o.order_state' => ['neq',9]])
    	->field("1 as is_refund,ore.refund_sn,ore.date_add,ore.state as return_state,g.name as goods_name,concat('$host',g.cover,'$suffix') as cover,og.option_name,o.order_sn,o.order_amount,(o.order_amount-o.goods_amount) as express_fee,og.price,og.quantity")
    	->order("ore.date_add desc")
    	->select();
    	
    	$temps = [];
    	$res = [];
    	if(!empty($orders)){
    		foreach ($orders as $order){
    			$temps[$order['refund_sn']][] = $order;
    		}
    		
    		foreach ($temps as $temp){
    			$torder = [];
    			foreach ($temp as $goods){
    				$torder['order_amount'] = $goods['order_amount'];
    				$torder['refund_sn'] = $goods['refund_sn'];
    				$torder['date_add'] = $goods['date_add'];
    				$torder['order_sn'] = $goods['order_sn'];
    				$torder['return_state'] = $goods['return_state'];
    				$torder['express_fee'] = $goods['express_fee'];
    				$torder['is_refund'] = $goods['is_refund'];
    				unset($goods['refund_sn']);
    				unset($goods['date_add']);
    				unset($goods['state']);
    				unset($goods['order_amount']);
    				unset($goods['express_fee']);
    				unset($goods['is_refund']);
    				unset($goods['order_sn']);
    				$torder['goods'][] = $goods;
    			}
    			$res[] = $torder;
    		}
    	}
    	
    	$orders = M("order")
    	->alias("o")
    	->join("order_goods og","o.id = og.order_id")
    	->join("goods g","g.goods_id = og.goods_id")
    	->join("order_cancel oc","oc.order_id = o.id")
    	->join("order_cancel_reason ocr","ocr.order_reason_id = oc.order_reason_id")
    	->where(['o.customer_id' => $customer_id,'o.order_state' => ['neq',9]])
    	->field("0 as is_refund,(o.order_amount - o.goods_amount) as express_fee,o.order_sn,oc.status,oc.date_add,o.order_amount,g.name as goods_name,concat('$host',g.cover,'$suffix') as cover,og.price,og.option_name,og.quantity")
    	->order("oc.date_add desc")
    	->select();
    	 
    	$temps = [];
    	if(!empty($orders)){
    		foreach ($orders as $order){
    			$temps[$order['order_sn']][] = $order;
    		}
    		foreach ($temps as $temp){
    			$torder = [];
    			foreach ($temp as $goods){
    				$torder["order_sn"] = $goods['order_sn'];
    				$torder['date_add'] = $goods['date_add'];
    				$torder['order_amount'] = $goods['order_amount'];
    				$torder['is_refund'] = $goods['is_refund'];
    				$torder['status'] = $goods['status'];
    				$torder['express_fee'] = $goods['express_fee'];
    				unset($goods['order_sn']);
    				unset($goods['date_add']);
    				unset($goods['order_amount']);
    				unset($goods['is_refund']);
    				unset($goods['status']);
    				unset($goods['express_fee']);
    				$torder['goods'][] = $goods;
    			}
    			$res[] = $torder;
    		}
    	}
    	
    	array_multisort(array_column($res, "date_add"),SORT_DESC,$res);
    	return ['errcode' => 0, 'message' => '请求成功', 'content' => $res];
    }
    
    /**
     * 退款详情
     */
   	public function refundOrderDetail(){
   		$customer_id = get_customer_id();
   		
   		if (!$customer_id) {
   			return ['errcode' => 99, 'message' => '请重新登录'];
   		}
   		
   		$refund_sn = I("refund_sn");
   		if(empty($refund_sn)){
   			return ['errcode' => -101, 'message' => '请传入退款订单号'];
   		}
   		
   		$host = \app\library\SettingHelper::get("shuaibo_image_url");
   		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
   		
   		$orders = M("order_return")
   		->alias("ore")
   		->join("order_return_reason orr",'orr.order_return_reason_id = ore.return_reason_id')
   		->join("order_return_method orm",'orm.order_return_method_id = ore.return_method')
   		->join("order o","o.id = ore.order_id")
   		->join("order_goods og","og.order_id = o.id")
   		->join("goods g",'g.goods_id = og.goods_id')
   		->where(['ore.customer_id' => $customer_id, 'ore.refund_sn' => $refund_sn, 'orr.status' => 1,'orm.status' => 1])
   		->field("ore.refund_sn,ore.state,ore.date_add,ore.return_reason_id,ore.other_reason,ore.return_method,orm.content as return_method_content,ore.price as money,orr.content,g.name as goods_name,concat('$host',g.cover,'$suffix') as cover,og.price,og.quantity,og.option_name,o.order_amount,(o.order_amount-o.goods_amount) as express_fee")
   		->select();
   		
   		if(empty($orders)){
   			return ['errcode' => -101, 'message' => '未找到该退款订单'];
   		}
   		$data = [];
   		$goods = [];
   		$reason = [];
   		foreach ($orders as $order){
   			$data['refund_sn'] = $order['refund_sn'];
   			$data['date_add'] = $order['date_add'];
   			$data['order_amount'] = $order['order_amount'];
   			$data['express_fee'] = $order['express_fee'];
   			$data['state'] = $order['state'];
   			$reason = array(
   					'return_reason_id' => $order['return_reason_id'],
   					'return_reason_content' => $order['content'],
   					'other_reason' => $order['other_reason'],
   					'return_method' => $order['return_method'],
   					'return_method_content' => $order['return_method_content'],
   					'money' => $order['money']
   			);
   			unset($order['refund_sn']);
   			unset($order['date_add']);
   			unset($order['order_amount']);
   			unset($order['express_fee']);
   			unset($order['return_reason_id']);
   			unset($order['content']);
   			unset($order['return_method']);
   			unset($order['other_reason']);
   			unset($order['money']);
   			unset($order['state']);
   			$goods[] = $order;
   		}
   		$data['goods'] = $goods;
   		
   		return ['errcode' => 0, 'message' => '请求成功' ,'content' => ['order' => $data,'reason' => $reason]];
   	}

    public function delay()
    {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }

        $order_sn = I("order_sn");
        if (empty($order_sn)) {
            return ['errcode' => -100, 'message' => '找不到相关订单'];
        }

        M("order")
            ->where(['customer_id' => $customer_id, 'order_sn' => $order_sn, 'is_delay' => 0])
            ->save(['date_received' => ['exp', 'date_received + ' . 3 * 24 * 60 * 60], 'is_delay' => 1]);
        return ['errcode' => 0, 'message' => '操作成功'];
    }

    /**
     * 充值订单
     */
    public function mobileOrders(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }

        $page = I("page");
        if ($page <= 0) {
            $page = 1;
        }

        return D('order_model')->getMobileOrders($customer_id,$page);
    }

    

    



    //取消订单
    public function cancel()
    {
        $order_sn = I("order_sn");
        
        $order_reason_id = I("order_reason_id");
        
        $other_reason = I("other_reason");

        if (empty($order_sn)) {
            return ['errcode' => -101, 'message' => '请传入订单信息'];
        }

        $customer_id = get_customer_id();

        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
		
        $content = "";
        if(!empty($order_reason_id)){
        	$content = M("order_cancel_reason")->where(['order_reason_id' => $order_reason_id])->field("content")->find();
        }
        
        $order = M("order")
            ->where(["customer_id" => $customer_id, 'order_sn' => $order_sn])
            ->field("order_state,id")
        	->find();
        if(empty($order)){
        	return ['errcode' => -101,"message" => '订单不存在'];
        }
        
        $order_cancel = M("order_cancel")->where(["order_id" => $order['id']])->find();
        if(!empty($order_cancel)){
        	return ['errcode' => -101, 'message' => '不能重复取消订单'];
        }
        
        if(!in_array($order['order_state'], [1,2,3])){
        	return ['errcode' => -101,"message" => '该订单不能取消'];
        }
        
        if($order['order_state'] == 1){
        	M("order")->where(["order_sn" => $order_sn])->save(["order_state" => 6]);
        	return ['errcode' => 0, 'message' => '操作成功'];
        }
        
        $data = array(
        		"order_reason_id" => $order_reason_id,
        		"order_id" => $order['id'],
        		"status" => 1,
        		"other_reason" => $other_reason,
        		"date_add" => time()
        );
        M("order_cancel")->add($data);
		
        return ['errcode' => 0, 'message' => '操作成功'];
    }
    
    //取消订单列表
   	public function cancelOrderList(){
   		$customer_id = get_customer_id();
   		
   		if (empty($customer_id)) {
   			return ['errcode' => 99, 'message' => '请重新登录'];
   		}
   		
   		$host = \app\library\SettingHelper::get("shuaibo_image_url");
   		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
   		
   		$orders = M("order")
   		->alias("o")
   		->join("order_goods og","o.id = og.order_id")
   		->join("goods g","g.goods_id = og.goods_id")
   		->join("order_cancel oc","oc.order_id = o.id")
   		->join("order_cancel_reason ocr","ocr.order_reason_id = oc.order_cancel_id")
   		->where(['o.customer_id' => $customer_id])
   		->field("o.order_sn,o.date_add,o.order_amount,g.name as goods_name,concat('$host',g.cover,'$suffix') as cover,og.price,og.option_name,og.quantity")
   		->select();
   		
   		$temps = [];
   		$res = [];
   		if(!empty($orders)){
   			foreach ($orders as $order){
   				$temps[$order['order_sn']][] = $order;
   			}
   			foreach ($temps as $temp){
   				$torder = [];
   				foreach ($temp as $goods){
   					$torder["order_sn"] = $goods['order_sn'];
   					$torder['date_add'] = $goods['date_add'];
   					$torder['order_amount'] = $goods['order_amount'];
   					unset($goods['order_sn']);
   					unset($goods['date_add']);
   					unset($goods['order_amount']);
   					$torder['goods'][] = $goods;
   				}
   				$res[] = $torder;
   			}
   		}
   		
   		return ['errcode' => 0, 'message' => '请求成功', 'content' => $res];
   	}
   	
   	//取消订单详情
   	public function cancelOrderInfo(){
   		$order_sn = I("order_sn");
   		if(empty($order_sn)){
   			return ['errcode' => -101,'message' => '请传入订单号'];
   		}
   		
   		$customer_id = get_customer_id();
   		
   		if (empty($customer_id)) {
   			return ['errcode' => 99, 'message' => '请重新登录'];
   		}
   		
   		$host = \app\library\SettingHelper::get("shuaibo_image_url");
   		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
   		
   		$orders = M("order")
   		->alias("o")
   		->join("order_goods og","o.id = og.order_id")
   		->join("goods g","g.goods_id = og.goods_id")
		->where(["o.order_sn" => $order_sn,"customer_id" => $customer_id])
		->field("o.order_sn,o.date_add,o.order_amount,(o.order_amount - o.goods_amount) as express_fee,g.name as goods_name,concat('$host',g.cover,'$suffix') as cover,og.price,og.option_name,og.quantity")
		->select();
   		
   		if(empty($orders)){
   			return ['errcode' => -101, 'message' => '商品为空'];
   		}
   		
   		$data = array();
   		
   		$goods = [];
   		foreach ($orders as $key => $order){
   			$data['order_sn'] = $order['order_sn'];
   			$data['date_add'] = $order['date_add'];
   			$data['order_amount'] = $order['order_amount'];
   			$data['express_fee'] = $order['express_fee'];
   			$goods[$key]['goods_name'] = $order['goods_name'];
   			$goods[$key]['price'] = $order['price'];
   			$goods[$key]['option_name'] = $order['option_name'];
   			$goods[$key]['quantity'] = $order['quantity'];
   			$goods[$key]['cover'] = $order['cover'];
   		}
   		
   		$data['goods'] = $goods;
   		
   		$order_cancel = M("order_cancel")
   		->alias("oc")
   		->join("order_cancel_reason ocr","ocr.order_reason_id = oc.order_reason_id")
   		->join("order o","o.id = oc.order_id")
   		->where(["o.order_sn" => $order_sn])
   		->field("oc.other_reason,ocr.content,oc.status")
   		->find();
   		
   		return ['errcode' => 0, 'message' => '请求成功', 'content' => ['order' => $data,'order_cancel_reason' => $order_cancel]];
   	}

    public function get_comments()
    {
        $score = (int)I("score");

        $goods_id = (int)I("goods_id");

        $condition = ['oc.is_deleted' => 0, 'oc.goods_id' => $goods_id];

        $page = (int)I("page");
        if ($page <= 0) {
            $page = 1;
        }
        if (!empty($score) && $score >= 0) {
            $condition['oc.score'] = $score;
        }

        $host = \app\library\SettingHelper::get("shuaibo_image_url");

        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);


        $comments = M("order_comment")
            ->alias("oc")
            ->join("customer c", "c.customer_id = oc.customer_id")
            ->join("order_goods og", "og.order_id = oc.order_id and og.goods_id = oc.goods_id")
            ->where($condition)
            ->limit(20)
            ->page($page)
            ->order("oc.date_add desc")
            ->field("oc.is_anony,c.nickname,c.avater as image,oc.content,oc.images,oc.date_add, oc.score,oc.reply_content,IFNULL(og.option_name , '默认') as option_name")
        	->group("oc.id")    
        	->select();
        
        $count = count($comments);
        foreach ($comments as &$c) {
            if ($c['image'] && strpos($c['image'], "http") !== 0) {
                $c['image'] = $host . $c['image'] . $suffix;
            }

            if ($c['is_anony']) {
                $c['image'] = '';
                $c['nickname'] = "匿名";
            }
            if(!empty($c['images'])){
            	$c['images'] = explode(",", $c['images']);
            	foreach ($c['images'] as &$image){
            		$image = $host.$image.$suffix;
            	}
            }
        }
        return ['errcode' => 0, 'message' => '请求成功', 'content' => ['comments' => $comments,'count' => $count]];
    }



    /*
    public function confirmOrder()
    {
        $customer_id = get_customer_id();

        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
		
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        
        $carts = I("carts");
        $goods = array();
        $total_fee = 0;
        if(!empty($carts)){
        	$total_fee = I("total_fee");
        	$carts = json_decode($carts,true);
        	$goods_info = null;
        	foreach ($carts as $cart){
        		$cart_id = $cart['cart_id'];
        		$quantity = $cart['quantity'];
        		$option_id = empty($cart['option_id'])?0:$cart['option_id'];
        		$goods_info = M("goods")
        		->alias("g")
        		->join("goods_option gp","g.goods_id = gp.goods_id and gp.id = $option_id","LEFT")
        		->where(['g.goods_id' => $cart['goods_id']])
        		->field("$cart_id as cart_id,$quantity as quantity,g.goods_id,ifnull(0,gp.id) as option_id,gp.name as option_name,g.mini_name,concat('$host',cover,'$suffix') as cover,g.price")
        		->find();
        		$goods[] = $goods_info;
        	}
        }else{
        	$goods_id = I("goods_id");
        	$option_id = empty(I("option_id"))?0:I("option_id");
        	$quantity = I("quantity");
        	$goods_info = M("goods")
        	->alias("g")
        	->where(['g.goods_id' => $goods_id])
        	->join("goods_option gp","g.goods_id = gp.goods_id and gp.id = $option_id","LEFT")
        	->field("$quantity as quantity,g.goods_id,ifnull(0,gp.id) as option_id,gp.name as option_name,g.mini_name,concat('$host',cover,'$suffix') as cover,g.price")
        	->find();
        	$goods[] = $goods_info;
        	$total_fee = $goods_info['price']*$quantity;
        }
        
        $address = M("address")->where(['customer_id' => $customer_id, 'active' => 1, 'status' => 1])->field("address_id,name,phone,province,city,district,address")->find();
        $address_id = 0;
        if ($address) {
            $address_id = $address['address_id'];
        }
        
        $express_fee = 0;
        foreach ($goods as $goods_info){
        	$express_fee += D("express_template")->calculateExpress($goods_info['goods_id'], $address_id, $goods_info['quantity'], $goods_info['option_id']);
        }
        $total_fee = $total_fee+$express_fee;
        return ['errcode' => 0, 'message' => '请求成功', 'content' => ['express_fee' => $express_fee, 'address' => $address, 'total_fee' => $total_fee, 'goods' => $goods]];
    }*/

    public function paybal()
    {

        $customer_id = get_customer_id();

        if (!$customer_id) {
            return ['errcode' => 99, 'message' => '请传入用户信息'];
        }
        
        $account = M("customer")->where(["customer_id" => $customer_id])->field("account")->find();

        $order_sns = I("orders");
        if (empty($order_sns)) {
            return ['errcode' => -101, 'message' => '请传入订单信息'];
        }
        $order_sns = explode(",", $order_sns);

        $expire = \app\library\SettingHelper::get("shuaibo_order_settings", ['pay_expire' => 30]);
		
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        $orders = M("order")
        ->alias("o")
        ->join("order_goods og","og.order_id = o.id")
        ->join("goods g",'g.goods_id = og.goods_id')
        ->where(['o.order_sn' => ['in', $order_sns]])
        ->field("o.order_sn,o.order_amount,o.date_end,(o.order_amount - o.goods_amount) as express_fee,og.quantity,og.option_name,og.price,g.name as goods_name,concat('$host',g.cover,'$suffix') as cover")
        ->select();
		
        $temps = [];
        $res = [];
        foreach ($orders as &$o) {
            $o['date_end'] = $o['date_end'] - time();
            $temps[$o['order_sn']][] = $o;
        }
        foreach ($temps as $temp){
        	$torder = [];
        	foreach ($temp as $goods){
        		$torder['order_sn'] = $goods['order_sn'];
        		$torder['order_amount'] = $goods['order_amount'];
        		$torder['date_end'] = $goods['date_end'];
        		$torder['express_fee'] = $goods['express_fee'];
        		unset($goods['order_sn']);
        		unset($goods['order_amount']);
        		unset($goods['date_add']);
        		unset($goods['express_fee']);
        		$torder['goods'][] = $goods;
        	}
        	$res[] = $torder;
        }
        return ['errcode' => 0, 'message' => '请求成功', 'content' => ['orders' => $res, 'expire' => $expire['pay_expire'], 'pay_ids' => [1,2,3], 'account' => $account]];
    }

    public function refundReason()
    {
        $reason = M('order_return_reason')->where(['status' => 1])->field("order_return_reason_id,content")->select();
        return ['errcode' => 0, 'message' => '请求成功', 'content' => $reason];
    }
    
    public function refundMethod(){
    	$method = M('order_return_method')->where(['status' => 1])->field("order_return_method_id,content")->select();
    	return ['errcode' => 0, 'message' => '请求成功', 'content' => $method];
    }
    
    public function cancelReason(){
    	$reason = M("order_cancel_reason")->where(['status' => 1])->field("order_reason_id,content")->select();
    	return ['errcode' => 0, 'message' => '请求成功', 'content' => $reason];
    }


    public function getOrderPayStatus()
    {
        $order_sn = I("order_sn");

        $is_pay = S("shuaibo_pay_order_" . $order_sn);

        $state = 0;
        if (!empty($is_pay)) {
            $state = 1;
        }

        return ['errcode' => 0, 'message' => '请求成功', 'content' => $state];
    }

    /**
     * 生成订单
     */
    /*
    public function Generate(){
        $res = array(
            'message' => '错误信息',
            'errcode' => 0,
        );

        $address_id = I("address_id");

        $express_id = (int)I("express_id");

        $comment = I("comment");

        $carts = I("carts");

        if (empty($carts)){
            return ['errcode' => -101, 'message' => '请传入商品'];
        }

        $in_goods = [];
        $carts = json_decode($carts,true);
        foreach ($carts as $cart){
            if(empty($cart['cart_id'])){
                $goods_info['goods_id'] = $cart['goods_id'];
                $goods_info['option_id'] = empty($cart["option_id"])?0:$cart["option_id"];
                $goods_info['quantity'] = $cart["quantity"];
                if(empty($goods_info['goods_id'])){
                    return ['errcode' => -101, 'message' => '请传入商品'];
                }
                $in_goods[] = $goods_info;
                break;
            }else{
                $quantity = $cart['quantity'];
                $scart = M("cart")->where(["cart_id" => $cart["cart_id"]])->field("goods_id,ifnull(0,option_id) as option_id,$quantity as quantity")->find();
                if(empty($scart)){
                    return ['errcode' => -101, 'message' => '购物车不存在'];
                }
                $in_goods[] = $scart;
            }
        }

        if (empty($address_id)) {
            return ['errcode' => -102, 'message' => '请传入地址'];
        }

        if (!empty($express_id)) {
            $express_info = M("express")->alias('e')->where(['express_id' => $express_id])->field('e.express_id,e.code')->find();
            if (!$express_info) {
                return ['errcode' => -103, 'message' => '请选择物流'];
            }
        }

        $customer_id = get_customer_id();
        if (empty($customer_id) || $customer_id <= 0) {
            return ['errcode' => 99, 'message' => '请重新登陆'];
        }

        $order_sql = M("order")->alias("o")->where(['o.order_state' => 7, 'o.customer_id = c.customer_id'])
            ->field("count(o.id)")->buildSql();

        $customer = M("customer")
            ->alias("c")
            ->where(array("customer_id" => $customer_id))
            ->field("customer_id, agent_id, nickname,active, ifnull($order_sql , 0) as refund_count")
            ->find();
        if (empty($customer)) {
            $res['message'] = '请重新登陆';
            $res['errcode'] = 99;
            return $res;
        }

        if ($customer['refund_count'] > 0) {
            return ['errcode' => -101, 'message' => '您有未处理的退款订单，需处理完毕后方可购买产品'];
        }

        if ($customer['active'] == 0) {
            $seller_info = \app\library\SettingHelper::get("shuaibo_seller_info", ["service_phone" => '15906716507', 'address' => '杭州市']);
            return ['errcode' => -106, 'message' => '抱歉，您无法执行此操作，如有疑问，请拨打客服电话:' . $seller_info['service_phone']];
        }

        $phone = M("customer")->where(['customer_id' => $customer_id])->getField("phone");

        if (empty($phone)) {
            return ['errcode' => -101, 'message' => '购买前请先绑定手机号'];
        }

        $total_fee = 0;
        $goods_amount = 0;
        foreach ($in_goods as &$goods_info){
            if ($goods_info['quantity'] <= 0) {
                $goods_info['quantity'] = 1;
            }

            $goods_id = $goods_info['goods_id'];
            $quantity = $goods_info['quantity'];
            $goods = D("goods")->getGoods($goods_id, ['cover', 'stock', 'goods_name', 'on_sale', 'price', "options"]);

            if (empty($goods)) {
                return ['errcode' => -101, 'message' => '找不到相关产品'];
            }

            $has_option = false;

            if (isset($goods['options']) && !empty($goods['options'])) {
                $options = $goods['options'];
                foreach ($options as $o) {
                    if ($o['id'] == $goods_info['option_id'] || $goods_info['option_id'] == 0) {
                        $goods_info['option_id'] = $o['id'];
                        $has_option = true;
                        break;
                    }
                }
            } else {
                $goods_info['option_id'] = 0;
                $has_option = true;
            }
            if(!$has_option){
                return ['errcode' => -101 ,'message' => '找不到相关样式'];
            }
            $option_id = $goods_info['option_id'];

            $goods = M("goods")->alias("g")
                ->join("goods_option go", "go.goods_id = g.goods_id and go.id =".$option_id, "LEFT")
                ->where(['g.goods_id' => $goods_id])
                ->field("g.name as goods_name,g.bonus,ifnull(go.sale_price, g.price) as price, ifnull(go.stock,g.quantity) as stock,go.name as option_name," .
                    "g.on_sale,g.goods_type,g.has_commission,g.unique_commission,g.commission1_rate,g.commission1_pay," .
                    "g.time_limit,g.time_unit,g.time_number,g.date_end,g.date_start,g.is_deleted,g.max_type,g.max_buy,g.max_once_buy,g.wx_goods_tag")
                ->find();

            if (empty($goods)) {
                return ['errcode' => -101, 'message' => '找不到相关产品'];
            }
            if ($goods['is_deleted'] == 1) {
                return ['errcode' => -102, 'message' => '该产品已被删除'];
            }
            if ($goods['on_sale'] == 0) {
                return ['errcode' => -103, 'message' => '该产品已下架'];
            }

            if ($goods['stock'] < $quantity) {
                return ['errcode' => -104, 'message' => '产品库存不足'];
            }
            if (!empty($goods['date_start']) && $goods['date_start'] > time()) {
                return ['errcode' => -105, 'message' => '该产品暂未开启'];
            }

            if ($quantity > $goods['max_once_buy']) {
                return ['errcode' => -107, 'message' => "产品一次性最多购买{$goods['max_once_buy']}件"];
            }
            if ($goods['max_type'] == 1) {
                $count = M("order_goods")->alias("og")
                    ->join("order o", "o.id = og.order_id")
                    ->where(['og.goods_id' => $goods_id, 'o.customer_id' => $customer_id, 'o.order_state' => ['in', '1,2,3,4,5,7']])
                    ->count();
                if ($count + $quantity > $goods['max_buy']) {
                    return ['errcode' => -108, 'message' => "每人最多购买{$goods['max_buy']}件"];
                }
            }
            $goods_info['price'] = $goods['price'];
            $goods_info['option_name'] = $goods['option_name'];
            $goods_info['bonus'] = $goods['bonus'];
            $express_fee = D("express_template")->calculateExpress($goods_id, $address_id, $quantity, $option_id);
            $amount = $goods['price']*$quantity+$express_fee;
            $total_fee += $amount;
            $goods_amount += $goods['price']*$quantity;
            unset($goods_info);
        }

        $address = D("address")->getAddress($address_id, ["province_id", "city_id", "district_id"
            , "name", "address", "province", "city", "district", "phone", "customer_id"]);

        if (empty($address) || $address['customer_id'] != $customer_id) {
            return ['errcode' => -107, 'message' => '地址信息有误'];
        }
        unset($address['customer_id']);
        $address_id = M("order_address")->add($address);

        if ($total_fee <= 0) {
            return ['errcode' => -103, 'message' => '订单金额为0不允许提交订单'];
        }
        $order_sns = [];
        $expire = \app\library\SettingHelper::get("shuaibo_order_settings", ['pay_expire' => 30]);
        $expire = $expire['pay_expire'];

        $orders = [];
        $order_sn = createNo("order", "order_sn", "SH");
        $data = ['order_sn' => $order_sn,
            'order_state' => 1,
            'address_id' => $address_id,
            'customer_id' => $customer_id,
            'goods_amount' => $goods_amount,
            'express_amount' => $express_fee,
            'order_amount' => $total_fee,
            'org_amount' => $total_fee,
            'date_add' => time(),
            'date_end' => time() + $expire * 60,
            'comment' => $comment,
            // 'express_id' => $express_id,
            //'express' => $express_info['code'],
            'wx_goods_tag' => $goods['wx_goods_tag']
        ];

        $order_id = M("order")->add($data);

        $order_sns[] = $order_sn;

        $datas = [];
        foreach ($in_goods as $goods_info){
            $order_goods = ['order_id' => $order_id,
                'goods_id' => $goods_info['goods_id'],
                'price' => $goods_info['price'],
                'quantity' => $goods_info['quantity'],
                'option_id' => $goods_info['option_id'],
                'option_name' => $goods_info['option_name'],
                'bonus' => $goods_info['bonus']
            ];
            $datas[] = $order_goods;
        }
        M("order_goods")->addAll($datas);

//        $client = \app\library\message\MessageClient::getInstance();
//         $message = new \app\library\message\Message();
//         $message->setTargetIds($customer_id)
//         ->setExtras(['order_sn' => $order_sns[0], 'title'=>'订单通知', 'content' => "您的订单已经提交成功"])
//         ->setWeixinExtra(['goods_name' => $goods['goods_name'] . "×" . $quantity, 'order_amount' => $order_amount * $quantity])
//         ->setTemplate("submit")
//         ->setAction(\app\library\message\Message::ACTION_ORDER_LIST)
//         ->setPlatform([\app\library\message\Message::PLATFORM_ALL]);
//
//        $client->pushCache($message);
//
//        if($customer['agent_id'] > 0 && $goods['has_commission'] == 1){
//         $message
//         ->setAction(\app\library\message\Message::ACTION_NONE)
//         ->setCustomer($customer['agent_id'])
//         ->setWeixinExtra(['ignore_url' => 1,'goods_name' => $goods['goods_name'] . "×" . $quantity, 'order_amount' => $order_amount * $quantity])
//         ->setExtras(['order_sn' => $order_sns[0], 'title'=>'订单通知', 'content' => "您的朋友{$customer['nickname']}已提交订单"]);
//         $client->pushCache($message);
//        }
        if(!empty($carts[0]['cart_id'])){
            $cart_ids = [];
            foreach ($carts as $cart){
                $cart_ids[] = $cart['cart_id'];
            }
            M("cart")->where(['cart_id' => ["in",$cart_ids]])->delete();
        }
        return ['errcode' => 0, 'message' => '请求成功', 'content' => ['order_sn' => $order_sns, 'order_amount' => $total_fee]];

    }
    */
}