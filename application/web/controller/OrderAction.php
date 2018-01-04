<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/5/10
 * Time: 下午4:20
 */
namespace app\web\controller;
use \think\Cache;
class OrderAction {
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
        $data = serialize($data);
        $id = M('cart_goods')->add(['data' => $data]);
        if (empty($id)) {
            return ['errcode' => -200, 'message' => '添加失败'];
        }
        return ['errcode' => 0, 'message' => '添加成功', 'content' => $id];
    }

    public function buy_bal() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $id = I('id');
        if (empty($id)) {
            return ['errcode' => -101, 'message' => '请传入商品信息'];
        }
        $data = M('cart_goods')->where(['id' => $id])->getField("data");
        $data = unserialize($data);
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
        // [{"goods":[{"cart_id":"","goods_id":"","option_id":"","quantity":"","is_one":""}],"remark":"","seller_id":"","use_type":"","coupon":""}]
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
        if (empty($reason)) {
            return ['errcode' => -103, 'message' => '请传入退款原因'];
        }
        $images = I('images');
        $content = I('content');

        return D('order_model')->refund($customer_id,$order_sn,$goods_id,$option_id,$type,$price,$reason,$images,$content);
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

    public function refundGoods() {
        $customer_id = get_customer_id();
        if(empty($customer_id)){
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $refund_sn = I('refund_sn');
        $order_sn = I('order_sn');
        if (empty($refund_sn) && empty($order_sn)) {
            return ['errcode' => -100, 'message' => '请传入订单信息'];
        }
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
        if (!empty($refund_sn)) {
            $refund = M('order_return')
                ->alias("orr")
                ->where(['orr.refund_sn' => $refund_sn])
                ->join("order_goods og","og.order_id = orr.order_id AND og.goods_id = orr.goods_id AND og.option_id = orr.option_id")
                ->join("goods g","g.goods_id = og.goods_id")
                ->join("goods_option go","go.id = og.option_id","LEFT")
                ->join("order o","o.id = orr.order_id")
                ->join("customer_coupon cc","cc.customer_coupon_id = o.coupon_id","LEFT")
                ->join("coupon c","c.coupon_id = cc.coupon_id","LEFT")
                ->join("seller_shopinfo ss","ss.seller_id = g.seller_id")
                ->field("g.name as goods_name,concat('$host',g.cover,'$suffix') as cover,IFNULL(go.name,'') as option_name,".
                    "ss.shop_name,ss.kf_qq,og.price,og.quantity,og.full_amount,og.new_user_amount,og.max_integration as integration,og.max_shopping_coin as shopping_coin,og.coupon_amount as goods_coupon_amount,".
                    "o.order_sn,o.express_amount,IFNULL(c.amount,0) as coupon_amount,o.order_amount,o.date_add,o.max_integration,o.max_shopping_coin")
                ->find();
            $refund['total'] = $refund['price'] * $refund['quantity'];
            $refund['coupon_amount'] = $refund['coupon_amount'] + $refund['max_integration'] + $refund['max_shopping_coin'] + $refund['full_amount'] + $refund['new_user_amount'];
            $refund['order_amount'] = $refund['order_amount'] + $refund['express_amount'] - $refund['coupon_amount'];
            $refund['price'] = $refund['total'] - $refund['full_amount'] - $refund['new_user_amount'] - $refund['integration'] - $refund['shopping_coin'] - $refund['goods_coupon_amount'];
            if ($refund['price'] <= 0) {
                $refund['price'] = 0;
            }
            return ['errcode' => 0, 'message' => '成功', 'content' => $refund];
        }
        $goods_id = (int)I('goods_id');
        if ($goods_id <= 0) {
            return ['errcode' => -101, 'message' => '请传入退款商品信息'];
        }
        $option_id = I('option_id');
        if (empty($option_id)) {
            $option_id = 0;
        }
        $condition = [
            'o.order_sn' => $order_sn,
            'o.customer_id' => $customer_id,
            'og.goods_id' => $goods_id,
        ];
        if ($option_id > 0) {
            $condition['og.option_id'] = $option_id;
        }
        $refund = M('order')
            ->alias("o")
            ->where($condition)
            ->join("order_goods og","og.order_id = o.id")
            ->join("goods g","g.goods_id = og.goods_id")
            ->join("goods_option go","go.id = og.option_id","LEFT")
            ->join("customer_coupon cc","cc.customer_coupon_id = o.coupon_id","LEFT")
            ->join("coupon c","c.coupon_id = cc.coupon_id","LEFT")
            ->join("seller_shopinfo ss","ss.seller_id = g.seller_id")
            ->field("g.name as goods_name,concat('$host',g.cover,'$suffix') as cover,IFNULL(go.name,'') as option_name,".
                "ss.shop_name,ss.kf_qq,og.price,og.quantity,og.full_amount,og.new_user_amount,og.max_integration as integration,og.max_shopping_coin as shopping_coin,og.coupon_amount as goods_coupon_amount,".
                "o.order_sn,o.express_amount,IFNULL(c.amount,0) as coupon_amount,o.order_amount,o.date_add,o.max_integration,o.max_shopping_coin")
            ->find();
        $refund['total'] = $refund['price'] * $refund['quantity'];
        $refund['coupon_amount'] = $refund['coupon_amount'] + $refund['max_integration'] + $refund['max_shopping_coin'] + $refund['full_amount'] + $refund['new_user_amount'];
        $refund['order_amount'] = $refund['order_amount'] + $refund['express_amount'] - $refund['coupon_amount'];
        $refund['price'] = $refund['total'] - $refund['full_amount'] - $refund['new_user_amount'] - $refund['integration'] - $refund['shopping_coin'] - $refund['goods_coupon_amount'];
        if ($refund['price'] <= 0) {
            $refund['price'] = 0;
        }
        return ['errcode' => 0, 'message' => '成功', 'content' => $refund];
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

    public function getExpressFees() {
        $shops = I("shops");
        if (empty($shops)) {
            return ['errcode' => -101, 'message' => '请传入商品信息'];
        }

        $shops = json_decode($shops,true);
        $province_id = (int)I("province_id");
        $express_fees = [];
        foreach ($shops as $shop) {
            $express_fee = 0;
            foreach ($shop['goods'] as $goods_info){
                $express_fee += D("express_template")->calculateExpressWithoutAddressId($goods_info['goods_id'], $province_id, 1, $goods_info['option_id']) * (int)$goods_info['quantity'];
            }
            $content['seller_id'] = $shop['seller_id'];
            $content['express_fee'] = $express_fee;
            $express_fees[] = $content;
        }
        return ['errcode' => 0, 'message' => '请求成功', 'content' => $express_fees];
    }

    /**
     * 获取支付订单状态
     * @return array
     */
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
}