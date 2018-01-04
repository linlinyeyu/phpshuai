<?php
namespace app\common\model;
use app\library\ExpressHelper;
use think\Model;
use think\Cache;
class OrderModel extends Model{

    //新增
    public function myAdd($data) {
        return $this->add($data);
    }

    //更新
    public function mySave($condition,$data){
        return $this->where($condition)->save($data);
    }

    //查找
    public function myFind($condtion,$field="*"){
        return $this->field($field)->where($condtion)->find();
    }

    //删除
    public function myDel($condtion){
        return $this->where($condtion)->delete();
    }

    /**
     * 立即购买/结算
     * @param $customer_id
     * @param $data
     * @return array
     */
    public function buy($customer_id,$data) {
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);

        $total_order = 0;
        $content = [];
        foreach ($data as $order_info) {
            $total_fee = 0;
            $goods_amount = 0;
            $max_integration = 0;
            $max_shopping_coin = 0;
            $hongfu = 0;
            $in_goods = $order_info['goods'];
            $seller_id = $order_info['seller_id'];
            if (empty($seller_id)) {
                return ['errcode' => -101, 'message' => '请传入店铺信息'];
            }
            $shop = M('seller_shopinfo')->where(['seller_id' => $seller_id])->field("shop_name,seller_id")->find();
            $address = array();
            foreach ($in_goods as &$goods_info){
                if (empty($goods_info['is_one'])) {
                    $goods_info['is_one'] = 0;
                }
                $is_one = $goods_info['is_one'];
                if ($goods_info['quantity'] <= 0) {
                    $goods_info['quantity'] = 1;
                }

                $goods_id = $goods_info['goods_id'];
                $quantity = $goods_info['quantity'];
                $goods = D("goods_model")->getGoods($goods_id, ['cover', 'stock', 'goods_name', 'on_sale', 'price', "options"]);

                if (empty($goods)) {
                    return ['errcode' => -102, 'message' => '找不到相关产品'];
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
                    return ['errcode' => -103 ,'message' => '找不到相关样式'];
                }
                $option_id = $goods_info['option_id'];

                $goods = M("goods")
                    ->alias("g")
                    ->join("goods_option go", "go.goods_id = g.goods_id and go.id =".$option_id, "LEFT")
                    ->join("special_goods sg","sg.goods_id = g.goods_id AND sg.special_id = 1 AND sg.status = 1 AND sg.date_start <= ".time()." AND sg.date_end >=".time(),"LEFT")
                    ->where(['g.goods_id' => $goods_id])
                    ->field("concat('$host',g.cover,'$suffix') as cover,g.name as goods_name,ifnull(go.sale_price, g.shop_price) as price, ifnull(go.stock,g.quantity) as stock,go.name as option_name," .
                        "g.on_sale,g.goods_type,g.max_once_buy,g.max_type,g.max_buy," .
                        "g.is_delete,g.max_integration,g.max_shopping_coin,g.hongfu,g.address,".
                        "sg.special_goods_id")
                    ->find();

                if (empty($goods)) {
                    return ['errcode' => -104, 'message' => '找不到相关产品'];
                }
                if ($goods['is_delete'] == 1) {
                    return ['errcode' => -105, 'message' => '该产品已被删除'];
                }
                if ($goods['on_sale'] == 0) {
                    return ['errcode' => -106, 'message' => '该产品已下架'];
                }

                if ($goods['stock'] < $quantity) {
                    return ['errcode' => -107, 'message' => '产品库存不足'];
                }
                if ($goods['max_type'] == 1) {
                    $number = M("order")
                        ->alias("o")
                        ->join("order_goods og","og.order_id = o.id")
                        ->where(['o.customer_id' => $customer_id,'og.goods_id' => $goods_id])
                        ->field("SUM(quantity) as number")
                        ->find();
                    if ($goods['max_buy'] - $number['number'] < $quantity) {
                        return ['errcode' => -107, 'message' => '该商品剩余可购买数量'.($goods['max_buy'] - $number['number'])."件"];
                    }
                }
                //判断是否有线下提货商品
                if(empty($goods['address'])){
                    $address[] = 'null';
                }else{
                    $address[] = 'not null';
                }

                // 一元抢购
                if (!empty($goods['special_goods_id'])) {
                    $special_goods = M('special_goods')
                        ->where(['goods_id' => $goods_id])
                        ->field("special_goods_id,special_id,goods_id,quantity,status,date_start,date_end,max_buy")
                        ->find();
                    if (empty($special_goods)) {
                        return ['errcode' => -201, 'message' => '找不到相关产品'];
                    }
                    if ($special_goods['special_id'] != 1) {
                        return ['errcode' => -202, 'message' => '该商品不属于一元抢购商品'];
                    }
                    if ($special_goods['status'] != 1) {
                        return ['errcode' => -203, 'message' => '该产品已下架'];
                    }
                    if ($special_goods['date_start'] > time() || $special_goods['date_end'] < time()) {
                        return ['errcode' => -204, 'message' => '该产品不处于一元抢购活动期间'];
                    }
//                    if ($special_goods['quantity'] < $quantity) {
//                        return ['errcode' => -205, 'message' => '产品库存不足'];
//                    }
                    $count = M('order_goods')
                        ->alias("og")
                        ->join("order o","o.id = og.order_id")
                        ->where(['og.goods_id' => $goods_id,'og.special_type' => 1,'o.customer_id' => $customer_id])
                        ->field("IFNULL(SUM(og.quantity),0) as quantity")
                        ->find();
                    if ($special_goods['max_buy'] - $count['quantity'] < $quantity) {
                        return ['errcode' => -206, 'message' => '最多还能购买'.($special_goods['max_buy'] - $count['quantity'])."件商品"];
                    }
                    $goods['price'] = 1;
                    $goods['max_integration'] = 0;
                    $goods['max_shopping_coin'] = 0;
                    $goods['hongfu'] = 0;
                }

                $goods_info['stock'] = $goods['stock'];
                $goods_info['goods_name'] = $goods['goods_name'];
                $goods_info['cover'] = $goods['cover'];
                $goods_info['price'] = $goods['price'];
                $goods_info['option_name'] = $goods['option_name'];
                $amount = $goods['price'] * $quantity;
                $total_fee += $amount;
                $goods_amount += $goods['price'] * $quantity;
                unset($goods_info);
                $max_integration += $goods['max_integration'] * $quantity;
                $max_shopping_coin += $goods['max_shopping_coin'] * $quantity;
                $hongfu += $goods['hongfu'] * $quantity;
            }

            //判断线下提货和非线下提货混合
            if (in_array("null",$address)){
                if (in_array("not null",$address)){
                    return ['errcode' => -201,'message' => '线下提货商品不能和非线下提货商品一起购买'];
                }
            }

            $shop['goods'] = $in_goods;
            $shop['total_fee'] = $total_fee;
            $shop['max_integration'] = $max_integration;
            $shop['max_shopping_coin'] = $max_shopping_coin;
            $shop['hongfu'] = $hongfu;

            $total_order += $total_fee;

            $content['shop'][] = $shop;
        }

        $content['total_amount'] = $total_order;

//        TODO 新用户
        // 是否新用户 是新用户减去相应的钱
        $is_new = M('customer')->where(['customer_id' => $customer_id])->getField("is_new");
        $new_user = \app\library\SettingHelper::get("shuaibo_new_user",['limit' => 0, 'amount' => 10]);
        $is_new == 1 ? $content['newuser'] = 1 : $content['newuser'] = 0;
        if ($content['newuser'] == 1) {
            $total_order -= $new_user['amount'];
            $content['new_user'] = $new_user;
        }

        // 满减
        $content['full'] = M('fullcut')
            ->alias('f')
            ->where(['f.active' => 1,'f.limit' => ['elt',$total_order]])
            ->field("f.id as full_id,f.name,f.limit,f.amount")
            ->order('f.limit DESC')
            ->find();
        $total_order -= $content['full']['amount'];

        /*
        // 优惠券
        $content['coupon'] = M('customer_coupon')
            ->alias("cc")
            ->join('coupon c',"c.coupon_id = cc.coupon_id AND c.active = 1 AND c.limit <= ".$total_order)
            ->where(['cc.customer_id' => $customer_id, 'cc.state' => '0'])
            ->field("cc.customer_coupon_id as coupon_id,c.name,c.limit,c.amount")
            ->order('c.limit DESC')
            ->find();
        $total_order -= $content['coupon']['amount'];
        */

        // 用户信息
        $content['customer'] = M('customer')
            ->where(['customer_id' => $customer_id, 'active' => 1])
            ->field("integration,shopping_coin,hongfu")
            ->find();

        // 默认地址
        $content['address'] = M('address')
            ->where(['customer_id' => $customer_id,'status' => 1])
            ->field("address_id,name,phone,province,city,district,address,postcode,tel,status")
            ->find();

        $content['total_fee'] = $total_order;
        return ['errcode' => 0, 'message' => '成功', 'content' => $content];
    }

    /**
     * 获取优惠券
     * @param $customer_id
     * @param $goods
     * @return array
     */
    public function getCoupons($customer_id,$goods,$type) {
        $goods = json_decode($goods,true);
        $total_fee = 0;
        $goods_ids = [];
        foreach ($goods as $good) {
            $goods_ids[] = $good['goods_id'];
            $goods_info = M('goods')
                ->alias("g")
                ->join("goods_option go", "go.goods_id = g.goods_id and go.id =".$good['option_id'], "LEFT")
                ->where(['g.goods_id' => $good['goods_id']])
                ->field("ifnull(go.sale_price, g.shop_price) as price")
                ->find();
            $amount = $goods_info['price'] * $good['quantity'];
            $total_fee += $amount;
        }

        $coupons = M("customer_coupon")
            ->alias("cc")
            ->join("coupon c","c.coupon_id = cc.coupon_id AND c.active = 1")
            ->join("coupon_type ct","ct.type_id = c.type")
            ->join("coupon_goods cg","cg.coupon_id = c.coupon_id")
            ->where(['cc.customer_id' => $customer_id, 'cg.goods_id' => ['in',$goods_ids]])
            ->field("c.name,c.amount,c.limit,cc.customer_coupon_id as coupon_id,cc.date_start,cc.date_end,cc.state,ct.name as issuer,cg.goods_id")
            ->order("cc.date_add DESC")
            ->select();
        $content = [
            'usable_coupons' => [],
            'unusable_coupons' => []
        ];
        foreach ($coupons as &$coupon) {
            $coupon['unable'] = 1;
            if ($coupon['state'] == 0 && $coupon['limit'] <= $total_fee) {
                $coupon['unable'] = 0;
                $content['usable_coupons'][] = $coupon;
            } else {
                $content['unusable_coupons'][] = $coupon;
            }
        }
        if ($type == 1) {
            $coupons = $content['usable_coupons'];
        } else {
            $coupons = $content['unusable_coupons'];
        }

        return ['errcode' => 0, 'message' =>'成功', 'content' => $coupons];
    }

    /**
     * 使用优惠券
     * @param $customer_id
     * @param $coupon_id
     * @return array
     */
    public function useCoupon($customer_id,$use_type,$coupon_id,$seller_id) {
        if ($use_type == 0) {
            // 不使用优惠券
            M('customer_coupon_use')->where(['customer_id' => $customer_id, 'seller_id' => $seller_id])->delete();
        } else {
            // 使用优惠券
            $coupon = M('customer_coupon')->where(['customer_id' => $customer_id, 'coupon_id' => $coupon_id])->find();
            if (empty($coupon)) {
                return ['errcode' => -101, 'message' => '未找到对应的优惠券'];
            }
            $coupon_use = M('customer_coupon_use')->where(['customer_id' => $customer_id, 'seller_id' => $seller_id])->find();
            if (empty($coupon_use)) {
                // 插入
                M('customer_coupon_use')->add([
                    'coupon_id' => $coupon_id,
                    'customer_id' => $customer_id,
                    'seller_id' => $seller_id
                ]);
            } else {
                // 修改
                M('customer_coupon_use')
                    ->where(['customer_id' => $customer_id, 'seller_id' => $seller_id])
                    ->save(['coupon_id' => $coupon_id]);
            }
        }
        return ['errcode' => 0, 'message' => '成功'];
    }

    /**
     * 生成订单
     */
    public function generate($customer_id,$address_id,$in_orders) {
        $times = (int)Cache::get("shuaibo_generate_goods_order_". $customer_id);
        if($times > 0){
            return ['errcode' => -101, 'message' => '不要重复点击'];
        }
        Cache::set("shuaibo_generate_goods_order_". $customer_id , ++$times, 3);

        $customer = M('customer')->where(['customer_id' => $customer_id, 'active' => 1])->field("integration,shopping_coin,hongfu")->find();
        if (empty($customer)) {
            return ['errcode' => -200, '未找到该用户信息'];
        }
        $user_integration = $customer['integration'];
        $user_shopping_coin = $customer['shopping_coin'];
        $user_hongfu = $customer['hongfu'];
        $total_order = 0;
        $total_org = 0;
        $order_sns = [];

        foreach ($in_orders as $order_info) {
            $total_fee = 0;
            $express_fee = 0;
            $goods_amount = 0;
            $max_integration = 0;
            $max_shopping_coin = 0;
            $max_hongfu = 0;
            $in_goods = $order_info['goods'];
            $remark = $order_info['remark'];
            $seller_id = $order_info['seller_id'];
            $use_type = $order_info['use_type'];
            $coupon_id = (int)$order_info['coupon'];
            if (empty($seller_id)) {
                return ['errcode' => -101, 'message' => '请传入店铺信息'];
            }
            $offline_address = "";
            foreach ($in_goods as &$goods_info){
                if (empty($goods_info['is_one'])) {
                    $goods_info['is_one'] = 0;
                }
                $is_one = $goods_info['is_one'];

                if ($goods_info['quantity'] <= 0) {
                    $goods_info['quantity'] = 1;
                }

                $goods_id = $goods_info['goods_id'];
                $quantity = $goods_info['quantity'];
                $goods = D("goods_model")->getGoods($goods_id, ['cover', 'stock', 'goods_name', 'on_sale', 'price', "options"]);

                if (empty($goods)) {
                    return ['errcode' => -102, 'message' => '找不到相关产品'];
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
                    return ['errcode' => -103 ,'message' => '找不到相关样式'];
                }
                $option_id = $goods_info['option_id'];

                $goods = M("goods")
                    ->alias("g")
                    ->join("special_goods sg","sg.goods_id = g.goods_id AND sg.special_id = 1 AND sg.status = 1 AND sg.date_start <= ".time()." AND sg.date_end >=".time(),"LEFT")
                    ->join("goods_option go", "go.goods_id = g.goods_id and go.id =".$option_id, "LEFT")
                    ->where(['g.goods_id' => $goods_id])
                    ->field("g.name as goods_name,ifnull(go.sale_price, g.shop_price) as price, ifnull(go.stock,g.quantity) as stock,go.name as option_name," .
                        "g.on_sale,g.stock_type,g.goods_type,g.max_type,g.max_buy," .
                        "g.is_delete,g.max_integration,g.max_shopping_coin,g.hongfu,g.address,".
                        "sg.special_goods_id")
                    ->find();

                if (empty($goods)) {
                    return ['errcode' => -104, 'message' => '找不到相关产品'];
                }
                if ($goods['is_delete'] == 1) {
                    return ['errcode' => -105, 'message' => '该产品已被删除'];
                }
                if ($goods['on_sale'] == 0) {
                    return ['errcode' => -106, 'message' => '该产品已下架'];
                }

                if ($goods['stock'] < $quantity) {
                    return ['errcode' => -107, 'message' => '产品库存不足'];
                }
                if ($goods['max_type'] == 1) {
                    $number = M("order")
                        ->alias("o")
                        ->join("order_goods og","og.order_id = o.id")
                        ->where(['o.customer_id' => $customer_id,'og.goods_id' => $goods_id])
                        ->field("SUM(quantity) as number")
                        ->find();
                    if ($goods['max_buy'] - $number['number'] < $quantity) {
                        return ['errcode' => -107, 'message' => '该商品剩余可购买数量'.($goods['max_buy'] - $number['number'])."件"];
                    }
                }

                // 一元抢购
                if (!empty($goods['special_goods_id'])) {
                    $special_goods = M('special_goods')
                        ->where(['goods_id' => $goods_id])
                        ->field("special_goods_id,special_id,goods_id,quantity,status,date_start,date_end,max_buy")
                        ->find();
                    if (empty($special_goods)) {
                        return ['errcode' => -201, 'message' => '找不到相关产品'];
                    }
                    if ($special_goods['special_id'] != 1) {
                        return ['errcode' => -202, 'message' => '该商品不属于一元抢购商品'];
                    }
                    if ($special_goods['status'] != 1) {
                        return ['errcode' => -203, 'message' => '该产品已下架'];
                    }
                    if ($special_goods['date_start'] > time() || $special_goods['date_end'] < time()) {
                        return ['errcode' => -204, 'message' => '该产品不处于一元抢购活动期间'];
                    }
//                    if ($special_goods['quantity'] < $quantity) {
//                        return ['errcode' => -205, 'message' => '产品库存不足'];
//                    }
                    $count = M('order_goods')
                        ->alias("og")
                        ->join("order o","o.id = og.order_id")
                        ->where(['og.goods_id' => $goods_id,'og.special_type' => 1,'o.customer_id' => $customer_id])
                        ->field("IFNULL(SUM(og.quantity),0) as quantity")
                        ->find();
                    if ($special_goods['max_buy'] - $count['quantity'] < $quantity) {
                        return ['errcode' => -206, 'message' => '最多还能购买'.($special_goods['max_buy'] - $count['quantity'])."件商品"];
                    }
                    $goods['price'] = 1;
                    $goods['max_integration'] = 0;
                    $goods['max_shopping_coin'] = 0;
                    $goods['hongfu'] = 0;
                }

                $goods_info['price'] = $goods['price'];
                $goods_info['option_name'] = $goods['option_name'];
                $amount = $goods['price'] * $quantity;
                $total_fee += $amount;
                $goods_amount += $goods['price'] * $quantity;
                $max_integration += $goods['max_integration'] * $quantity;
                $max_shopping_coin += $goods['max_shopping_coin'] * $quantity;
                $max_hongfu += $goods['hongfu'] * $quantity;
                $goods_info['special_goods_id'] = $goods['special_goods_id'];
                $goods_info['stock_type'] = $goods['stock_type'];
                if ($use_type == 2 && $max_shopping_coin < $amount) {
                    return ['errcode' => -200, 'message' => '会员积分购买不能使用现金'];
                }
                if ($use_type == 1) {
                    $goods_info['max_integration'] = $goods['max_integration'] * $quantity;
                    $goods_info['max_shopping_coin'] = 0;
                    $goods_info['hongfu'] = 0;
                } elseif ($use_type == 2) {
                    $goods_info['max_shopping_coin'] = $goods['max_shopping_coin'] * $quantity;
                    $goods_info['max_integration'] = 0;
                    $goods_info['hongfu'] = 0;
                } elseif ($use_type == 3){
                    $goods_info['hongfu'] = $goods['hongfu'] * $quantity;
                    $goods_info['max_integration'] = 0;
                    $goods_info['max_shopping_coin'] = 0;
                } else {
                    $goods_info['max_shopping_coin'] = 0;
                    $goods_info['max_integration'] = 0;
                    $goods_info['hongfu'] = 0;
                }

                $goods_info['address'] = $goods['address'];
                unset($goods_info);

                $express_fee += D("express_template")->calculateExpress($goods_id, $address_id, 1, $option_id) * $quantity;
                $offline_address .= $goods['address'];
            }

            // 优惠券 积分 会员积分
            if ($use_type == 1) {
                $temp_integration = $user_integration;
                $user_integration -= $max_integration;
                if ($user_integration < 0) {
                    $max_integration = $temp_integration;
                    $user_integration = 0;
                }
                $max_shopping_coin = 0;
                $max_hongfu = 0;
            } elseif ($use_type == 2) {
                $temp_shopping_coin = $user_shopping_coin;
                $user_shopping_coin -= $max_shopping_coin;
                if ($user_shopping_coin < 0) {
                    $max_shopping_coin = $temp_shopping_coin;
                    $user_shopping_coin = 0;
                }
                $max_integration = 0;
                $max_hongfu = 0;
            } elseif ($use_type == 3){
                $temp_hongfu = $user_hongfu;
                $user_hongfu -= $max_hongfu;
                if ($user_hongfu < 0){
                    $max_hongfu = $temp_hongfu;
                    $user_hongfu = 0;
                }
                $max_shopping_coin = 0;
                $max_integration = 0;
            } else {
                $max_shopping_coin = 0;
                $max_integration = 0;
                $max_hongfu = 0;
            }
            M('customer')->where(['customer_id' => $customer_id])->save(['integration' => $user_integration,'shopping_coin' => $user_shopping_coin,'hongfu' => $user_hongfu]);

            $coupon_amount = 0;
            if ($coupon_id > 0) {
                $coupon = M('customer_coupon')
                    ->alias("cc")
                    ->join("coupon c","c.coupon_id = cc.coupon_id")
                    ->where(['cc.customer_coupon_id' => $coupon_id])
                    ->field("cc.customer_coupon_id,cc.state,c.limit,c.active,c.amount")
                    ->find();
                if (empty($coupon)) {
                    return ['errcode' => -400, 'message' => '未找到对应的优惠券'];
                }
                if ($coupon['state'] != 0) {
                    return ['errcode' => -401, 'message' => '优惠券已使用或已过期'];
                }
                if ($coupon['limit'] > $goods_amount) {
                    return ['errcode' => -402, 'message' => '未满足使用优惠券的条件'];
                }
                if ($coupon['active'] != 1) {
                    return ['errcode' => -403, 'message' => '该优惠券已被下架'];
                }
                $coupon_amount = $coupon['amount'];
                M('customer_coupon')->where(['customer_coupon_id' => $coupon['customer_coupon_id']])->save(['state' => 1]);
            }

            $address = D("address_model")->getAddress($address_id, ["province_id", "city_id", "district_id"
                , "name", "address", "province", "city", "district", "phone", "customer_id"]);

            if (empty($address) || $address['customer_id'] != $customer_id) {
                return ['errcode' => -108, 'message' => '地址信息有误'];
            }
            unset($address['customer_id']);
            $order_address_id = M("order_address")->add($address);

            if ($total_fee <= 0) {
                return ['errcode' => -109, 'message' => '订单金额为0不允许提交订单'];
            }
            $expire = \app\library\SettingHelper::get("shuaibo_order_settings", ['pay_expire' => 30]);
            $expire = $expire['pay_expire'];

            $order_sn = createNo("order", "order_sn", "SH");
            if($offline_address == ""){
                $offline_address = null;
            }
            $data = [
                'order_sn' => $order_sn,
                'order_state' => 1,
                'address_id' => $order_address_id,
                'seller_id' => $seller_id,
                'customer_id' => $customer_id,
                'goods_amount' => $goods_amount,
                'order_amount' => $total_fee,
                'express_amount' => $express_fee,
                'org_amount' => $total_fee,
                'date_add' => time(),
                'date_end' => time() + $expire * 60,
                'comment' => $remark,
                'max_integration' => $max_integration,
                'max_shopping_coin' => $max_shopping_coin,
                'max_hongfu' => $max_hongfu,
                'coupon_id' => $coupon_id,
                'address' => $offline_address,
            ];

            $order_id = M("order")->add($data);

            $order_sns[] = $order_sn;
            $total_org += $total_fee;
            $total_order += $total_fee + $express_fee - $coupon_amount - $max_integration - $max_shopping_coin - $max_hongfu;

            $datas = [];
            foreach ($in_goods as $goods_info){
                $order_goods = [
                    'order_id' => $order_id,
                    'goods_id' => $goods_info['goods_id'],
                    'price' => $goods_info['price'],
                    'quantity' => $goods_info['quantity'],
                    'option_id' => $goods_info['option_id'],
                    'option_name' => $goods_info['option_name'],
                    'special_type' => $goods_info['is_one'] == 1 ? 1 : 0,
                    'max_integration' => $goods_info['max_integration'],
                    'max_shopping_coin' => $goods_info['max_shopping_coin'],
                    'max_hongfu' => $goods_info['hongfu'],
                    'address' => $goods_info['address'],
                ];
                $datas[] = $order_goods;
                // 库存 销量
                if ($goods_info['stock_type'] == 1) {
//                    if ($goods_info['special_goods_id'] > 0) {
//                        $temp = ['quantity' => ['exp', 'quantity - '.$goods_info['quantity']],'sale_count' => ['exp', 'sale_count + '.$goods_info['quantity']]];
//                        M("special_goods")->where(['special_goods_id' => $goods_info['special_goods_id']])->save($temp);
//                        M("goods")->where(['goods_id' => $goods_info['goods_id']])->setInc('sale_count',$goods_info['quantity']);
//                    } else
                    if ($goods_info['option_id'] > 0) {
                        M("goods_option")->where(['id' => $goods_info['option_id']])->setDec("stock", $goods_info['quantity']);
                        M("goods")->where(['goods_id' => $goods_info['goods_id']])->setInc('sale_count',$goods_info['quantity']);
                    } else {
                        $temp = ['quantity' => ['exp', 'quantity - '.$goods_info['quantity']],'sale_count' => ['exp', 'sale_count + '.$goods_info['quantity']]];
                        M("goods")->where(['goods_id' => $goods_info['goods_id']])->save($temp);
                    }
                }

            }
            M("order_goods")->addAll($datas);

            /*$client = \app\library\message\MessageClient::getInstance();
             $message = new \app\library\message\Message();
             $message->setTargetIds($customer_id)
             ->setExtras(['order_sn' => $order_sns[0], 'title'=>'订单通知', 'content' => "您的订单已经提交成功"])
             ->setWeixinExtra(['goods_name' => $goods['goods_name'] . "×" . $quantity, 'order_amount' => $order_amount * $quantity])
             ->setTemplate("submit")
             ->setAction(\app\library\message\Message::ACTION_ORDER_LIST)
             ->setPlatform([\app\library\message\Message::PLATFORM_PUSH]);

            $client->pushCache($message);*/

            /*if($customer['agent_id'] > 0 && $goods['has_commission'] == 1){
             $message
             ->setAction(\app\library\message\Message::ACTION_NONE)
             ->setCustomer($customer['agent_id'])
             ->setWeixinExtra(['ignore_url' => 1,'goods_name' => $goods['goods_name'] . "×" . $quantity, 'order_amount' => $order_amount * $quantity])
             ->setExtras(['order_sn' => $order_sns[0], 'title'=>'订单通知', 'content' => "您的朋友{$customer['nickname']}已提交订单"]);
             $client->pushCache($message);
            }*/
            if(!empty($in_goods[0]['cart_id'])){
                $cart_ids = [];
                foreach ($in_goods as $cart){
                    $cart_ids[] = $cart['cart_id'];
                }
                M("cart")->where(['cart_id' => ["in",$cart_ids]])->delete();
            }
        }

        // 是否新用户 是新用户减去相应的钱
        $is_new = M('customer')->where(['customer_id' => $customer_id])->getField("is_new");
        $new_user = \app\library\SettingHelper::get("shuaibo_new_user",['limit' => 0, 'amount' => 10]);
        if ($is_new == 1) {
            $new_user_amount = $new_user['amount'];
        } else {
            $new_user_amount = 0;
        }
        $total_order = $total_order - $new_user_amount;
        if($total_order <= 0){
            $total_order = 0;
        }

        // 满减
        $full = M('fullcut')
            ->alias('f')
            ->where(['f.active' => 1,'f.limit' => ['elt',$total_order]])
            ->field("f.id as full_id,f.name,f.limit,f.amount")
            ->order('f.limit DESC')
            ->find();
        if (!empty($full)) {
            $total_order = $total_order - $full['amount'];
            if($total_order <= 0){
                $total_order = 0;
            }
        }

        if($total_order <= 0){
            $total_order = 0;
        }

        $balance = M('customer')->where(['customer_id' => $customer_id])->field("account,reward_amount,transfer_amount")->find();
        return ['errcode' => 0, 'message' => '成功', 'content' => ['order_sn' => $order_sns, 'order_amount' => $total_order, 'balance' => $balance['account'],
            'reward_amount' => $balance['reward_amount'],'transfer_amount' => $balance['transfer_amount']]];
    }

    /**
     * 支付
     */
    public function pay($customer_id,$type,$orders) {

        $times = (int)Cache::get("shuaibo_pay_goods_order_". $orders);
        if($times > 0){
            return ['errcode' => -101, 'message' => '不要重复点击'];
        }
        Cache::set("shuaibo_pay_goods_order_". $orders , ++$times, 3);

        $orders = explode(",", $orders);

        $order_goods = M("order")
            ->alias("o")
            ->join("order_goods og", "og.order_id = o.id")
            ->join("customer c", "c.customer_id = o.customer_id")
            ->join("goods g", "g.goods_id = og.goods_id", "LEFT")
            ->join("goods_option go", "go.id = og.option_id", "LEFT")
            ->field("g.goods_type,o.order_sn,o.order_state,o.order_amount,o.goods_amount,o.express_amount,o.coupon_id,o.max_integration,o.max_shopping_coin,o.max_hongfu," .
                "g.goods_id,ifnull(go.stock, g.quantity) as stock,og.quantity," .
                "g.is_delete,g.mini_name, g.on_sale, " .
                "g.sku,c.active,c.phone,g.sku")
            ->where(['order_sn' => ['in', $orders], 'o.customer_id' => $customer_id])
            ->select();

        if (empty($order_goods)) {
            return ['errcode' => -103, 'message' => '订单错误'];
        }
        $order_amount = 0;
        foreach ($order_goods as $goods) {
            $order_amount += $goods['order_amount'];
        }
        if ($order_amount <= 0) {
            return ['errcode' => -103, 'message' => '订单金额为0不允许支付'];
        }
        if ($order_goods[0]['active'] == 0) {
            $seller_info = \app\library\SettingHelper::get("shuaibo_seller_info", ['qq' => '123456789', 'address' => '杭州市']);
            return ['errcode' => -106, 'message' => '抱歉，您无法执行此操作，如有疑问，请拨打客服电话:' . $seller_info['qq']];
        }

        $sum = 0;
        $goods_amount = 0;
        $order_sns = [];
        $coupon_ids = [];
        $integration = 0;
        $shopping_coin = 0;
        $hongfu = 0;

        $body = [];
        foreach ($order_goods as $goods) {
            if (!in_array($goods['order_sn'], $order_sns)) {
                $order_sns[] = $goods['order_sn'];
                $sum += $goods['order_amount'] + $goods['express_amount'];
                $goods_amount += $goods['goods_amount'];
                $coupon_ids[] = $goods['coupon_id'];
                $integration += $goods['max_integration'];
                $shopping_coin += $goods['max_shopping_coin'];
                $hongfu += $goods['max_hongfu'];
            }

            $quantity = $goods['quantity'];

            $body[] = $goods['mini_name'];
            if (empty($goods['goods_id'])) {
                return ['errcode' => -103, 'message' => '找不到相关商品'];
            }
            if ($goods['is_delete'] == 1) {
                return ['errcode' => -102, 'message' => $goods['mini_name'] . '已被删除'];
            }
            if ($goods['on_sale'] == 0) {
                return ['errcode' => -103, 'message' => $goods['mini_name'] . '已下架'];
            }

            if ($goods['stock'] < $quantity) {
                return ['errcode' => -104, 'message' => $goods['mini_name'] . '库存不足'];
            }

            if ($goods['order_state'] != 1) {
                if ($goods['order_state'] == 2) {
                    return ['errcode' => -109, 'message' => "订单已支付"];
                }
                return ['errcode' => -110, 'message' => "订单未处于待支付状态"];
            }
        }

        $helper = \app\library\order\OrderHelper::getInstance($type);

        if (empty($helper)) {
            return ['errcode' => -101, 'message' => '没有对应的支付方式'];
        }
        if (is_string($helper)) {
            return ['errcode' => -102, 'message' => $helper];
        }

        $pay_order_sn = createNo();

        $subject = "订单号：" . $pay_order_sn;

        $helper->setOrderNumber($pay_order_sn)
            ->setOrderType(1)
            ->setGoodsAmount($goods_amount)
            ->setBody($body[0] . "等")
            ->setSubject($subject)
            ->setCoupon($coupon_ids)
            ->setIntegartion($integration)
            ->setShoppingCoin($shopping_coin)
            ->setHongfu($hongfu);
        $res = $helper->get_init_order($customer_id, $sum);

        if ($res['errcode'] < 0) {
            return $res;
        }
        $order = $helper->getOrder();

        $order['foregin_infos'] = join(",", $order_sns);

        M("order_info")->add($order);

        M("order")->where(['order_sn' => ['in', $order_sns]])->save(['out_order_sn' => $order['order_sn']]);

        $return = $helper->getReturn();

        if (in_array($order['pay_id'],[1,6,7])) {
            $notify = new \app\library\order\OrderNotify($order['order_sn'], $return['count'], $order['pay_id']);
            $res = $notify->notify();
            if($res['errcode'] < 0){
                return $res;
            }
        }

        $return['order_sn'] = $orders[0];
        return ['errcode' => 0, 'message' => '请求成功', 'content' => $return];
    }

    /**
     * 获取订单列表
     */
    public function getOrders($customer_id,$state,$page = 1,$type = 1) {
        // 设置条件
        $condition = ['o.is_delete' => 0, 'o.customer_id' => $customer_id];
        if($type == 1){
            $condition["_string"] = "o.address is null";
        }elseif ($type == 2){
            $condition["_string"] = "o.address is not null";
        }

        if ($state == 0) {
//            $condition['o.order_state'] = ['neq', 9];
        } elseif ($state == 1){
            $condition['o.order_state'] = "1";
        } elseif ($state == 2) {
            $condition['o.order_state'] = "2";
        } elseif ($state == 3) {
            $condition['o.order_state'] = "3";
        } elseif ($state == 4) {
            $condition['o.order_state'] = "4";
        }

        // 订单总数
        $count = M('order')
            ->alias("o")
            ->where($condition)->count();
        $pageSize = $count % 10 > 0 ? intval($count / 10 + 1) : intval($count / 10);

        $host = \app\library\SettingHelper::get("shuaibo_image_url");

        $orders = M("order")
            ->alias("o")
            ->join("order_state os", "os.order_state_id = o.order_state")
            ->join("customer c","c.customer_id = o.customer_id")
            ->join("customer_coupon cc","cc.customer_coupon_id = o.coupon_id","LEFT")
            ->join("coupon co","co.coupon_id = cc.coupon_id","LEFT")
            ->field("o.id,o.date_add,o.order_sn,o.order_amount,o.express_amount, o.order_state,os.name as state,c.account,IFNULL(co.amount,0) as coupon_amount,o.express,o.express_sn,".
                "o.max_integration,o.max_shopping_coin")
            ->where($condition)
            ->order("o.date_add desc")
            ->limit(10)
            ->page($page)
            ->select();
        $order_ids = [];
        $order_tmp = [];
        foreach ($orders as &$o) {
            $order_ids[] = $o['id'];
            $o['goods'] = [];
            $o['goods_count'] = 0;
            $o['order_amount'] = $o['order_amount'] + $o['express_amount'] - $o['coupon_amount'] - $o['max_integration'] - $o['max_shopping_coin'];
            if ($o['order_amount'] <= 0) {
                $o['order_amount'] = 0;
            }
            $order_tmp[$o['id']] = $o;
        }
        if (count($orders) > 0) {
            $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);

            $order_goods = M("order_goods")
                ->alias("og")
                ->join("goods g", "g.goods_id = og.goods_id")
                ->join("seller_shopinfo ss","ss.seller_id = g.seller_id")
                ->join("goods_option go", "go.goods_id = g.goods_id and og.option_id = go.id", "LEFT")
                ->field("g.goods_id,g.goods_type,g.name, og.order_id , og.quantity,og.price,og.full_amount,og.new_user_amount, ifnull(go.name,'默认') as option_name,go.id as option_id, " .
                    "concat('$host', g.cover, '$suffix') as image,ss.shop_name,ss.seller_id,ss.kf_qq")
                ->where(['og.order_id' => ['in', join(",", $order_ids)]])
                ->select();

            foreach ($order_goods as &$g) {
                if ($g['goods_type'] == 2) {
                    $order_tmp[$g['order_id']]['goods_type'] = 2;
                    $order_tmp[$g['order_id']]['date_end'] = $g['date_end'] - time();
                }
                $order_tmp[$g['order_id']]['goods_count'] += $g['quantity'];
                $order_tmp[$g['order_id']]['shop_name'] = $g['shop_name'];
                $order_tmp[$g['order_id']]['seller_id'] = $g['seller_id'];
                $order_tmp[$g['order_id']]['kf_qq'] = $g['kf_qq'];
                $order_tmp[$g['order_id']]['order_amount'] = $order_tmp[$g['order_id']]['order_amount'] - $g['full_amount']- $g['new_user_amount'];
                if ($order_tmp[$g['order_id']]['order_amount'] <= 0) {
                    $order_tmp[$g['order_id']]['order_amount'] = 0;
                }
                $order_tmp[$g['order_id']]['order_amount'] = round($order_tmp[$g['order_id']]['order_amount'],2);
                unset($g['shop_name']);
                unset($g['seller_id']);
                unset($g['kf_qq']);
                $order_tmp[$g['order_id']]['goods'][] = $g;
            }
            $orders = array_values($order_tmp);
        }

        $content['orders'] = $orders;
        $content['total_count'] = $count;
        $content['pageSize'] = $pageSize;

        return ['errcode' => 0, 'message' => '请求成功', 'content' => $content];
    }
    
    /**
     * 获取充值订单
     */
    public function getMobileOrders($customer_id,$page){
        $orders = M("mobile_recharge")
        ->where(['customer_id' => $customer_id])
        ->field("mobile,order_sn,amount,total_fee,type,is_success,date_add,CONCAT((CASE type WHEN 1 THEN '话费充值' WHEN 2 THEN '流量充值' ELSE '其他' END),order_sn) as name")
        ->limit(10)
        ->order("date_add DESC")
        ->page($page)
        ->select();
        
        $count = M("mobile_recharge")
        ->where(['customer_id' => $customer_id])
        ->count();
        
        $pageSize = ceil($count/10);
        
        return ['errcode' => 0,'message' => '请求成功','content' => ['total_count' => $count,'orders' => $orders,'pageSize' => $pageSize]];
    }

    /**
     * 订单详情
     * @param $customer_id
     * @param $order_sn
     * @return array
     */
    public function getOrderDetail($customer_id , $order_sn){
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);

        $data = ['o.order_sn' =>$order_sn, 'o.customer_id' => $customer_id, 'o.is_delete' => 0 ];

        $order = M("order")
            ->alias("o")
            ->join("order_state os","os.order_state_id = o.order_state")
            ->join("order_address oa", "o.address_id = oa.id")
            ->join('express e','e.express_id=o.express_id','LEFT')
            ->join("customer c","c.customer_id = o.customer_id")
            ->join("customer_coupon cc","cc.customer_coupon_id = o.coupon_id","LEFT")
            ->join("coupon co","co.coupon_id = cc.coupon_id","LEFT")
            ->field("o.date_received,o.comment,o.is_delay,o.express_sn,o.address as offline_address,e.express_name,o.id,o.order_sn,o.order_amount,o.goods_amount,IFNULL(o.express_amount,0) as express_amount,o.express_id,o.express,oa.name as address_name, oa.phone as address_phone, oa.province, oa.city, oa.district, oa.address, o.order_state,o.date_add,o.date_end,os.name as state,".
                "o.date_pay,o.date_send,o.date_cancel,o.date_refund,o.date_finish,o.date_finish_add,o.express_type,c.account,IFNULL(co.amount,0) as coupon_amount,".
                "IFNULL(o.max_integration,0) as max_integration,IFNULL(o.max_shopping_coin,0) as max_shopping_coin")
            ->where($data)
            ->find();
        if(empty($order)){
            return ['errcode' => -101, 'message' => "找不到相关订单"];
        }
        $order['order_amount'] = $order['order_amount'] + $order['express_amount'] - $order['coupon_amount'] - $order['max_integration'] - $order['max_shopping_coin'];
        if ($order['order_amount'] <= 0) {
            $order['order_amount'] = 0;
        }

        $order['date_end'] = $order['date_end'] - time();
        if ($order['date_end'] <= 0) {
            $order['date_end'] = 0;
        }
//        $order['date_received'] = $order['date_received'] - time();
//        $order['date_finish'] = $order['date_finish'] - time();


        $refund_info = M('order_return')
            ->alias('r')
            ->where(['r.order_id' => $order['id'], 'r.customer_id' => $customer_id])
            ->order(['r.date_add desc'])
            ->find();
        $order['goods'] = [];
        $order['goods_count'] = 0;

        $order_goods = M("order_goods")
            ->alias("og")
            ->join("goods g","g.goods_id = og.goods_id")
            ->join("seller_shopinfo ss","ss.seller_id = g.seller_id")
            ->join("goods_option go","go.goods_id = g.goods_id and og.option_id = go.id","LEFT")
            ->join("order_return orr","orr.order_id = og.order_id AND orr.goods_id = og.goods_id AND orr.option_id = og.option_id","LEFT")
            ->join("order_return_state ors","ors.order_return_state_id = orr.state","LEFT")
            ->field("g.goods_id,g.goods_type,g.name, og.order_id , og.quantity,og.price,og.full_amount,og.new_user_amount, ifnull(go.name,'') as option_name,IFNULL(go.id,0) as option_id, ".
                "concat('{$host}', g.cover, '$suffix') as image,ss.shop_name,ss.seller_id,ss.kf_qq,".
                "IFNULL(ors.name,'') as refund_state")
            ->where(['og.order_id' =>$order['id']])
            ->select();

        $full_amount = 0;
        $new_user_amount = 0;
        foreach ($order_goods as &$g){
            $full_amount = $full_amount + $g['full_amount'];
            $new_user_amount = $new_user_amount + $g['new_user_amount'];
            $order['goods_type'] = $g['goods_type'];
            if($g['goods_type'] == 2){
                $order['goods_type'] = 2;
                $order['date_end'] = $g['date_end'] - time();
            }
            $order['goods_count'] += $g['quantity'];
            $order['shop_name'] = $g['shop_name'];
            $order['seller_id'] = $g['seller_id'];
            $order['kf_qq'] = $g['kf_qq'];
            unset($g['shop_name']);
            unset($g['seller_id']);
            unset($g['kf_qq']);
            $order['goods'][] = $g;
        }
        $order['full_amount'] = $full_amount;
        $order['newuser_amount'] = $new_user_amount;
        $order['order_amount'] = $order['order_amount'] - $full_amount - $new_user_amount;
        if ($order['order_amount'] <= 0) {
            $order['order_amount'] = 0;
        }
        $order['order_amount'] = round($order['order_amount'],2);
        $express = [];
        if ($order['express_type'] == 1){
            if($order['express_sn']){
                $api = \app\api\ExpressApi::getInstance();
                $res = $api->orderExpress($order['express'],$order['express_sn']);
                if ($res['errcode'] == 0) {
                    $express[] = $res['content'];
                }
                //echo $order['express'];
//            $express  = ExpressHelper::getInstance($order['express'])->get_express($order['express_sn']);
//            $express = array_reverse($express);
            }
        }

        $content = ['order' => $order, 'express' => $express, 'refund_info' => $refund_info];

        return ['errcode' => 0, 'message' => '成功', 'content' => $content];
    }

    /**
     * 订单列表/详情 立即付款
     * @param $customer_id
     * @param $order_sn
     * @return array
     */
    public function payment($customer_id,$order_sn) {
        $order = M('order')
            ->alias("o")
            ->join("customer_coupon cc","cc.customer_coupon_id = o.coupon_id","LEFT")
            ->join("coupon c","c.coupon_id = cc.coupon_id","LEFT")
            ->where(['o.customer_id' => $customer_id, 'order_sn' => $order_sn])
            ->field("o.order_amount,o.max_integration,o.max_shopping_coin,c.amount as coupon_amount")
            ->find();

        $total_order = $order['order_amount'] - $order['coupon_amount'] - $order['max_integration'] - $order['max_shopping_coin'];

        // 是否新用户 是新用户减去相应的钱
        $is_new = M('customer')->where(['customer_id' => $customer_id])->getField("is_new");
        $new_user = \app\library\SettingHelper::get("shuaibo_new_user",['limit' => 0, 'amount' => 10]);
        if ($is_new == 1) {
            $new_user_amount = $new_user['amount'];
        } else {
            $new_user_amount = 0;
        }
        $total_order = $total_order - $new_user_amount;
        if($total_order <= 0){
            $total_order = 0;
        }

        // 满减
        $full = M('fullcut')
            ->alias('f')
            ->where(['f.active' => 1,'f.limit' => ['elt',$total_order]])
            ->field("f.id as full_id,f.name,f.limit,f.amount")
            ->order('f.limit DESC')
            ->find();
        if (!empty($full)) {
            $total_order = $total_order - $full['amount'];
            if($total_order <= 0){
                $total_order = 0;
            }
        }
        if($total_order <= 0){
            $total_order = 0;
        }

        $balance = M('customer')->where(['customer_id' => $customer_id])->field("account,reward_amount,transfer_amount")->find();
        return ['errcode' => 0, 'message' => '成功', 'content' => ['order_sn' => [$order_sn], 'order_amount' => $total_order, 'balance' => $balance['account'],
            'reward_amount' => $balance['reward_amount'],'transfer_amount' => $balance['transfer_amount']]];
    }

    /**
     * 取消订单
     * @param $customer_id
     * @param $order_sn
     * @return array
     */
    public function cancelOrder($customer_id,$order_sn) {
        $order = M('order')
            ->where(['order_sn' => $order_sn,'customer_id' => $customer_id])
            ->field("order_state,order_sn,date_add,date_cancel,date_end,max_integration,max_shopping_coin")
            ->find();
        if (empty($order)) {
            return ['errcode' => -101, 'message' => '订单信息有误'];
        }
        if ($order['order_state'] != 1) {
            return ['errcode' => -102, 'message' => '订单必须处于待付款状态'];
        }
        M('order')->where(['order_sn' => $order['order_sn']])->save(['order_state' => 6,'date_cancel' => time()]);
        $data['integration'] = ['exp', 'integration + '. $order['max_integration'] ];
        $data['shopping_coin'] = ['exp', 'shopping_coin + '. $order['max_shopping_coin'] ];
        M('customer')->where(['customer_id' => $customer_id])->save($data);
        return ['errcode' => 0, 'message' => "取消订单成功"];
    }

    /**
     * 确认发货
     * @param $customer_id
     * @param $order_sn
     * @return array
     */
	public function receivedOrder($customer_id, $order_sn){
		$orders = M("order")
		    ->field("order_state,order_sn,date_add,date_received,date_send")
		    ->where(['order_sn' => $order_sn,'customer_id' => $customer_id])
		    ->find();
		
		if(empty($orders)){
			return ['errcode' => -101 , 'message' => '订单信息有误'];
		}
		
		if($orders['order_state'] != 3 ){
			return ['errcode' => -102 ,'message' => '订单必须处于待收货状态'];
		}

        $expire = \app\library\SettingHelper::get("shuaibo_order_settings", ['receive_expire' => 7]);
        $expire = $expire['receive_expire'];
		
		M("order")->where(['order_sn' => $orders['order_sn']])->save([
		    'order_state' => 4,
            'date_received' => time(),
            'date_finish' => time() + $expire * 24 * 60 * 60,
            'date_end' => time() + $expire * 24 * 60 * 60,
        ]);

		M('customer_withdraw_order')
            ->where(['order_sn' => $orders['order_sn']])
            ->save(['state' => 1]);
			
		/*$client = \app\library\message\MessageClient::getInstance();
		$message = new \app\library\message\Message();
		$message
		->setTemplate("finish")
		->setAction(\app\library\message\Message::ACTION_ORDER)
		->setTargetIds($customer_id)
		->setExtras(['order_sn' => $order_sn])
		->setWeixinExtra(['order'=>$torder])
		->setPlatform([\app\library\message\Message::PLATFORM_ALL])
		->setPushExtra(['title' => '订单收货通知', 'content' => '您购买的'.$torder['mini_names'] ."已经确认收货，感谢您的支持"]);
		$client->pushCache($message);*/
		return ['errcode' => 0 ,'message' => '确认收货成功', 'content' => $order_sn];
	}

    /**
     * 商品评论
     * @param string $customer_id
     * @param $order_sn
     * @param $comments
     * @param $service_score
     * @param $logistics_score
     * @return array
     */
	public function commentGoods($customer_id,$order_sn,$comments,$service_score,$logistics_score) {
        if ($service_score <= 0 || $logistics_score <= 0) {
            return ['errcode' => -101, 'message' => '评论星级不能低于一星'];
        }
        if($service_score > 5 || $logistics_score > 5){
            return ['errcode' => -102, 'message' => '评论星级不能超过五星'];
        }
        $order_id = 0;
        $seller_id = 0;
        foreach ($comments as $params){
            $goods_id = (int)$params["goods_id"];
            $option_id = (int)$params["option_id"];
            if ($option_id <= 0) {
                $option_id = 0;
            }
            $comment = $params["comment"];
            $score = (int)$params["score"];

            if (empty($order_sn) || $goods_id <= 0) {
                return ['errcode' => -104, 'message' => '订单信息有误'];
            }

            if ($score <= 0) {
                return ['errcode' => -101, 'message' => '评论星级不能低于一星'];
            }
            if($score > 5){
                return ['errcode' => -102, 'message' => '评论星级不能超过五星'];
            }

            $length = (strlen($comment) + mb_strlen($comment, 'UTF8')) / 2;
            if ($length > 200) {
                $res['message'] = "评论内容长度不能超过200个字符";
                $res['errcode'] = -103;
                return $res;
            }
            $order = M("order")
                ->alias("o")
                ->join("order_goods og", "og.order_id = o.id")
                ->join("customer c", "c.customer_id = o.customer_id")
                ->where(['o.order_sn' => $order_sn, 'o.customer_id' => $customer_id, 'og.goods_id' => $goods_id, 'og.option_id' => $option_id])
                ->field("o.order_state,o.id,o.seller_id,c.active")
                ->find();
            if (empty($order)) {
                return ['errcode' => -105, 'message' => '找不到相关订单'];
            }

            if ($order['active'] == 0) {
                $seller_info = \app\library\SettingHelper::get("shuaibo_seller_info", ['qq' => '123456789', 'address' => '杭州市']);
                return ['errcode' => -106, 'message' => '抱歉，您无法执行此操作，如有疑问，请拨打客服电话:' . $seller_info['qq']];
            }

            if ($order['order_state'] != 4) {
                return ['errcode' => -107, 'message' => '订单未处于待评价状态'];
            }
            $order_id = $order['id'];
            $seller_id = $order['seller_id'];

            $images = isset($params["images"])?$params['images']:null;
            $data = [
                'order_id' => $order_id,
                'goods_id' => $goods_id,
                'option_id' => $option_id,
                'customer_id' => $customer_id,
                'seller_id' => $seller_id,
                'score' => $score,
                'content' => $comment,
                'date_add' => time(),
                'images' => $images,
                'type' => 1
            ];

//            $goods = D("goods")->getGoods($goods_id);
//
//            $goods['comment']['total'] = $goods['comment']['total'] + 1;
//            $goods['comment']['score_' . $score] = $goods['comment']['score_' . $score] + 1;
//            D("goods")->updateGoods($goods_id, ['comment' => $goods['comment']]);
//
//            $goods['comment'] = ['total' => 0, 'score_5' => 0,'score_4' => 0,'score_3' => 0, 'score_2' => 0, 'score_1' => 0];
            $id = M("order_comment")->add($data);
            if (empty($id)) {
                return ['errcode' => -108, 'message' => '添加失败'];
            }
        }
        $data = [
            'order_id' => $order_id,
            'customer_id' => $customer_id,
            'seller_id' => $seller_id,
            'service_score' => $service_score,
            'logistics_score' => $logistics_score,
            'date_add' => time(),
            'type' => 2
        ];
        $id = M("order_comment")->add($data);

        M("order")->where(['order_sn' => $order_sn])->save(['date_finish' => time(), 'order_state' => 5]);
        return ['errcode' => 0, 'message' => '发表成功'];
    }

    /**
     * 获取评论
     * @param $customer_id
     * @param $order_sn
     * @return array
     */
    public function getComments($customer_id,$order_sn) {
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
	    $order = M('order')
            ->where(['order_sn' => $order_sn, 'customer_id' => $customer_id])
            ->field("id as order_id,order_state")
            ->find();
        if ($order['order_state'] == 4) {
            // 待评价
            $comments = M('order_goods')
                ->alias("og")
                ->join("goods g","g.goods_id = og.goods_id")
                ->where(["og.order_id" => $order['order_id']])
                ->field("g.goods_id,concat('$host',g.cover,'$suffix') as cover,g.name,".
                    "og.option_name,og.price,og.quantity,og.option_id")
                ->select();
            return ['errcode' => 0, 'message' => '成功', 'content' => $comments];
        }
        $comments = M('order')
            ->alias("o")
            ->where(['o.order_sn' => $order_sn, 'o.customer_id' => $customer_id])
            ->join("order_goods og","og.order_id = o.id")
            ->join("goods g","g.goods_id = og.goods_id")
            ->join("order_comment oc","oc.goods_id = og.goods_id AND oc.order_id = o.id")
            ->field("g.goods_id,concat('$host',g.cover,'$suffix') as cover,g.name,".
                "og.option_name,og.price,og.quantity,og.option_id,".
                "oc.content,oc.images,oc.id as comment_id")
            ->group("oc.id")
            ->select();
        if (empty($comments)) {
            return ['errcode' => -101, 'message' => '未找到对应的评论'];
        }
        foreach ($comments as &$commnet) {
            $commnet['content'] = trim($commnet['content']);
            if (empty($commnet['images'])) {
                $commnet['images'] = [];
            } else {
                $images = explode(",",$commnet['images']);
                foreach ($images as &$image) {
                    $image = $host.$image.$suffix;
                }
                $commnet['images'] = $images;
            }
        }
        return ["errcode" => 0, 'message' => '成功', 'content' => $comments];
    }

    /**
     * 追加评论
     * @return array
     */
    public function addComment($customer_id,$order_sn,$comments) {
        foreach ($comments as $params){
            $comment_id = (int)$params["comment_id"];
            $comment = $params["comment"];

            if (empty($order_sn) || $comment_id <= 0) {
                return ['errcode' => -104, 'message' => '订单信息有误'];
            }

            $length = (strlen($comment) + mb_strlen($comment, 'UTF8')) / 2;
            if ($length > 200) {
                $res['message'] = "追加评论内容长度不能超过200个字符";
                $res['errcode'] = -103;
                return $res;
            }
            $order = M("order")
                ->alias("o")
                ->join("order_comment oc", "oc.order_id = o.id")
                ->join("customer c", "c.customer_id = o.customer_id")
                ->where(['o.order_sn' => $order_sn, 'o.customer_id' => $customer_id, 'oc.id' => $comment_id])
                ->field("o.order_state,o.id,c.active")
                ->find();
            if (empty($order)) {
                return ['errcode' => -105, 'message' => '找不到相关订单'];
            }

            if ($order['active'] == 0) {
                $seller_info = \app\library\SettingHelper::get("shuaibo_seller_info", ['qq' => '123456789', 'address' => '杭州市']);
                return ['errcode' => -106, 'message' => '抱歉，您无法执行此操作，如有疑问，请拨打客服电话:' . $seller_info['qq']];
            }

            if ($order['order_state'] != 5) {
                return ['errcode' => -107, 'message' => '订单未处于追加评价状态'];
            }

            $images = isset($params["images"])?$params['images']:null;
            $data = [
                'add_content' => $comment,
                'add_date' => time(),
                'add_images' => $images,
            ];
            M('order_comment')->where(['id' => $comment_id])->save($data);

//            $goods = D("goods")->getGoods($goods_id);
//
//            $goods['comment']['total'] = $goods['comment']['total'] + 1;
//            $goods['comment']['score_' . $score] = $goods['comment']['score_' . $score] + 1;
//            D("goods")->updateGoods($goods_id, ['comment' => $goods['comment']]);
//
//            $goods['comment'] = ['total' => 0, 'score_5' => 0,'score_4' => 0,'score_3' => 0, 'score_2' => 0, 'score_1' => 0];
        }
        M("order")->where(['order_sn' => $order_sn])->save(['date_finish_add' => time(), 'order_state' => 7]);
        return ['errcode' => 0, 'message' => '发表成功'];
    }

    /**
     * 申请退款
     * @param $customer_id
     * @param $order_sn
     * @param $goods_id
     * @param $price
     * @param $reason
     * @param $images
     * @return array
     */
    public function refund($customer_id,$order_sn,$goods_id,$option_id = 0,$type,$price,$reason,$images,$content = "") {
        $condition = [
            'o.order_sn' => $order_sn,
            'o.customer_id' => $customer_id,
            'og.goods_id' => $goods_id,
        ];
        if ($option_id > 0) {
            $condition['go.id'] = $option_id;
        }
        $order = M('order')
            ->alias("o")
            ->join("order_goods og","og.order_id = o.id")
            ->join("goods g",'g.goods_id = og.goods_id')
            ->join("goods_option go","go.goods_id = og.goods_id","LEFT")
            ->join("order_return orr", "orr.order_id = o.id and orr.state in (1,3,4,5)", "LEFT")
            ->join("customer c", "c.customer_id = o.customer_id")
            ->where($condition)
            ->field("o.id,o.order_amount,o.order_state,o.max_integration,o.max_shopping_coin,o.max_hongfu,o.date_received,g.address,".
                "orr.order_return_id,".
                "c.customer_id,c.nickname,c.active,".
                "og.price,og.quantity,og.goods_id,og.full_amount,og.new_user_amount,".
                "IFNULL(go.id,0) as option_id")
            ->find();
        if (strtotime("-7 days") > $order['date_received']){
            return ['errcode' => -107,'message' => '收货超过7天，不能申请退款'];
        }
        if (empty($order)) {
            return ['errcode' => -103, 'message' => '找不到相关订单'];
        }
        if ($order['active'] == 0) {
            $seller_info = \app\library\SettingHelper::get("shuaibo_seller_info", ['qq' => '123456789', 'address' => '杭州市']);
            return ['errcode' => -104, 'message' => '抱歉，您无法执行此操作，如有疑问，请拨打客服电话:' . $seller_info['qq']];
        }

        if (in_array($order['order_state'], [1, 6])) {
            return ['errcode' => -105, 'message' => '该订单状态不能申请售后'];
        }

        if ($order['order_state'] == 2 && $type != 1) {
            return ['errcode' => -105, 'message' => '待发货订单只能申请退款'];
        }

        if ($type == 3) {
            $price = 0;
        }
        if ($order['price'] * $order['quantity'] - $order['full_amount'] - $order['new_user_amount'] - $order['max_integration'] - $order['max_shopping_coin'] - $order['max_hongfu'] < $price) {
            return ['errcode' => -106, 'message' => '退款金额不得大于商品总金额'];
        }

        if (!empty($order['order_return_id'])) {
            return ['errcode' => -108, 'message' => '您已申请过退款，无法再次申请'];
        }

        $refund_sn = createNo("order_return", "refund_sn", "SR");
        if ($type != 1 && !empty($order['address'])){
            $type = 4;
        }
        $data = [
            'order_id' => $order['id'],
            'goods_id' => $order['goods_id'],
            'option_id' => $order['option_id'],
            'customer_id' => $customer_id,
            'reason' => $reason,
            'images' => $images,
            'content' => $content,
            'state' => '1',
            'type' => $type,
            'price' => $price,
            'date_add' => time(),
            'refund_sn' => $refund_sn,
            'order_state' => $order['order_state']
        ];

        $return_id = M('order_return')->add($data);
        if (empty($return_id)) {
            return ['errcode' => -103, 'message' => '添加失败'];
        }

        // 插入退款详情记录
        $data = [
            'order_return_id' => $return_id,
            'title' => "我提交了售后申请",
            'content' => "退款 ¥".$price."\n".$reason,
            'date_add' => time(),
            'type' => 1
        ];
        M('order_return_message')->add($data);

        $data = [
            'order_return_id' => $return_id,
            'title' => "等待卖家处理",
            'content' => "",
            'date_add' => time(),
            'type' => 2
        ];
        M('order_return_message')->add($data);

        return ['errcode' => 0, 'message' => '申请退款成功'];

//        M('order')->where(['order_sn' => $order_sn, 'customer_id' => $customer_id])->save(['order_state' => '7', 'date_refund' => time()]);

//        $client = \app\library\message\MessageClient::getInstance();

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
    }

    /**
     * 获取申请售后页面数据
     * @param $customer_id
     * @param $order_sn
     * @param $goods_id
     * @return array
     */
    public function getRefundInfo($customer_id,$order_sn,$goods_id,$option_id = 0) {
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        $condition = [
            'o.order_sn' => $order_sn,
            'o.customer_id' => $customer_id,
            'og.goods_id' => $goods_id,
        ];
        if ($option_id > 0) {
            $condition['og.option_id'] = $option_id;
        }
        $order = M('order')
            ->alias("o")
            ->join("order_goods og","og.order_id = o.id")
            ->join("goods g","g.goods_id = og.goods_id")
            ->join("seller_shopinfo ss","ss.seller_id = o.seller_id")
            ->where($condition)
            ->field("o.order_sn,o.date_add,".
                "og.price,og.quantity,og.goods_id,og.option_name,og.option_id,og.full_amount,og.new_user_amount,og.max_integration,og.max_shopping_coin,og.coupon_amount,".
                "concat('$host',g.cover,'$suffix') as cover,g.name,".
                "ss.shop_name,ss.kf_qq")
            ->find();
        if (empty($order)) {
            return ['errcode' => -103, 'message' => '找不到相关订单'];
        }
        $order['price'] = $order['price'] * $order['quantity'] - $order['full_amount'] - $order['new_user_amount'] - $order['max_integration'] - $order['max_shopping_coin'] - $order['coupon_amount'];
        if ($order['price'] <= 0) {
            $order['price'] = 0;
        }
        $order['type'] = M('order_return_type')->select();
        return ['errcode' => 0, 'message' => '成功', 'content' => $order];
    }

    /**
     * 获取退货单信息
     * @param $customer_id
     * @param $refund_sn
     * @return array
     */
    public function getReturnNote($customer_id,$refund_sn) {
        $refund = M('order_return')
            ->where(['customer_id' => $customer_id, 'refund_sn' => $refund_sn])
            ->field("refund_sn,express,express_sn")
            ->find();
        if (empty($refund)) {
            return ['errcode' => -103, 'message' => '未找到对应的售后信息'];
        }
        return ['errcode' => 0, 'message' => '成功', 'content' => $refund];
    }

    /**
     * 填写退货单
     * @param $customer_id
     * @param $refund_sn
     * @param $express
     * @param $express_sn
     * @return array
     */
    public function writeReturnNote($customer_id,$refund_sn,$express,$express_sn) {
        $refund = M('order_return')
            ->where(['customer_id' => $customer_id, 'refund_sn' => $refund_sn])
            ->field("order_return_id,express,express_sn,state")
            ->find();

        if (empty($refund)) {
            return ['errcode' => -103, 'message' => '未找到对应的退款信息'];
        }
        $data = [];
        if (empty($refund['express'])) {
            if ($refund['state'] != 3) {
                return ['errcode' => -104, 'message' => "当前状态不能填写退货单"];
            }
            M('order_return')
                ->where(['order_return_id' => $refund['order_return_id']])
                ->save(["express" => $express, 'express_sn' => $express_sn,'state' => 4]);
            $data[] = [
                'order_return_id' => $refund['order_return_id'],
                'title' => "我提交了退货单号",
                'content' => "物流公司：".$express."\n物流单号：".$express_sn,
                'date_add' => time(),
                'type' => 1
            ];
        } else {
            if ($refund['state'] != 4) {
                return ['errcode' => -104, 'message' => "当前状态不能修改退货单"];
            }
            M('order_return')
                ->where(['order_return_id' => $refund['order_return_id']])
                ->save(["express" => $express, 'express_sn' => $express_sn]);
            $data[] = [
                'order_return_id' => $refund['order_return_id'],
                'title' => "我修改了退货单号",
                'content' => "物流公司：".$express."\n物流单号：".$express_sn,
                'date_add' => time(),
                'type' => 1
            ];
        }
        $data[] = [
            'order_return_id' => $refund['order_return_id'],
            'title' => "等待卖家确认",
            'content' => "",
            'date_add' => time(),
            'type' => 2
        ];
        M('order_return_message')->addAll($data);
        return ['errcode' => 0, 'message' => '成功'];
    }

    /**
     * 获取退款列表
     * @param $customer_id
     * @param $page
     * @return array
     */
    public function getRefunds($customer_id,$page,$type = 1) {
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        $condition = array();
        if ($type == 1){
            $condition = array('orr.customer_id' => $customer_id,'orr.is_delete' => 0,'orr.type' => ["in",[1,2,3]]);
        }elseif ($type == 2){
            $condition = array('orr.customer_id' => $customer_id,'orr.is_delete' => 0,'orr.type' => 4);
        }

        $refunds = M('order_return')
            ->alias("orr")
            ->where($condition)
            ->join("order_return_state ors","ors.order_return_state_id = orr.state")
            ->join("order_return_type ort","ort.order_return_type_id = orr.type")
            ->join("order o","o.id = orr.order_id")
            ->join("goods g","g.goods_id = orr.goods_id")
            ->join("order_goods og","og.order_id = orr.order_id AND og.goods_id = orr.goods_id AND og.option_id = orr.option_id")
            ->join("seller_shopinfo ss","ss.seller_id = g.seller_id")
            ->field("orr.refund_sn,orr.state as refund_state,ors.name as state,orr.type as refund_type,ort.name as type,orr.date_add,".
                "o.order_amount,o.express_amount,".
                "ss.seller_id,ss.shop_name,".
                "g.goods_id,g.name,concat('$host',g.cover,'$suffix') as cover,".
                "ifnull(og.option_name,'默认') as option_name,og.price,og.quantity")
            ->order("orr.date_add desc")
            ->limit(10)
            ->page($page)
            ->select();
        foreach ($refunds as &$r) {
            $amount = 0;
            $goods = [];
            $goods['goods_id'] = $r['goods_id'];
            $goods['name'] = $r['name'];
            $goods['cover'] = $r['cover'];
            $goods['option_name'] = $r['option_name'];
            $goods['price'] = $r['price'];
            $goods['quantity'] = $r['quantity'];
            unset($r['goods_id']);
            unset($r['name']);
            unset($r['cover']);
            unset($r['option_name']);
            unset($r['price']);
            unset($r['quantity']);
            $amount += $goods['quantity'] * $goods['price'];
            $r['goods'][] = $goods;
            $r['goods_count'] = $goods['quantity'];
            $r['order_amount'] = $amount;
        }
        return ['errcode' => 0, 'message' => '成功', 'content' => $refunds];
    }

    /**
     * 退款留言
     * @param $customer_id
     * @param $return_id
     * @param $content
     * @return array
     */
    public function refundMessage($customer_id,$refund_sn,$content) {
        $refund = M('order_return')
            ->where(['customer_id' => $customer_id, 'refund_sn' => $refund_sn])
            ->field("order_return_id")
            ->find();
        if (empty($refund)) {
            return ['errcode' => -102, 'message' => '未找到对应的退款信息'];
        }
        M('order_return_message')->add([
            'order_return_id' => $refund['order_return_id'],
            'title' => '我给卖家留言',
            'content' => $content,
            'date_add' => time(),
            'type' => 1
        ]);
        return ['errcode' => 0, 'message' => '成功'];
    }

    /**
     * 售后详情
     * @param $customer_id
     * @param $refund_sn
     * @return array
     */
    public function supportDetail($customer_id,$refund_sn) {
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        $refund['goods'] = M('order_return')
            ->alias("orr")
            ->where(['orr.customer_id' => $customer_id,'orr.refund_sn' => $refund_sn])
            ->join("order_goods og","og.goods_id = orr.goods_id AND og.order_id = orr.order_id")
            ->join("goods g","g.goods_id = og.goods_id")
            ->join("order_return_state ors",'ors.order_return_state_id = orr.state')
            ->field("g.goods_id,g.name,concat('$host',g.cover,'$suffix') as cover,og.option_name,og.price,og.quantity,".
                "orr.refund_sn,orr.state as refund_state,ors.name as state,orr.date_add,orr.reason,orr.price as refund_price,orr.content,orr.images")
            ->find();
        if (empty($refund['goods'])) {
            return ['errcode' => -101, 'message' => '未找到对应的信息'];
        }
        $refund['refund_sn'] = $refund['goods']['refund_sn'];
        $refund['refund_state'] = $refund['goods']['refund_state'];
        $refund['state'] = $refund['goods']['state'];
        $refund['date_add'] = $refund['goods']['date_add'];
        $refund['reason'] = $refund['goods']['reason'];
        $refund['price'] = $refund['goods']['refund_price'];
        $refund['content'] = $refund['goods']['content'];
        $refund['images'] = $refund['goods']['images'];
        unset($refund['goods']['refund_sn']);
        unset($refund['goods']['refund_state']);
        unset($refund['goods']['state']);
        unset($refund['goods']['date_add']);
        unset($refund['goods']['reason']);
        unset($refund['goods']['refund_price']);
        unset($refund['goods']['content']);
        unset($refund['goods']['images']);
        if (!empty($refund['images'])) {
            $images = explode(",",$refund['images']);
            foreach ($images as &$image) {
                $image = $host.$image.$suffix;
            }
            $refund['images'] = $images;
        } else {
            $refund['images'] = [];
        }

        $refund['messages'] = M('order_return')
            ->alias("orr")
            ->where(['orr.customer_id' => $customer_id,'orr.refund_sn' => $refund_sn])
            ->join("order_return_message orm","orm.order_return_id = orr.order_return_id")
            ->field("orm.title,orm.content,orm.type,orm.date_add")
            ->order('orm.date_add ASC')
            ->select();

        return ['errcode' => 0, 'message' => '成功', 'content' => $refund];
    }

    /**
     * 获取退款申请详情
     * @param $customer_id
     * @param $refund_sn
     * @return array
     */
    public function refundDetail($customer_id,$refund_sn) {
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        $refund = M('order_return')
            ->alias("orr")
            ->where(['orr.customer_id' => $customer_id,'orr.refund_sn' => $refund_sn])
            ->join("order_goods og","og.goods_id = orr.goods_id AND og.order_id = orr.order_id")
            ->join("goods g","g.goods_id = og.goods_id")
            ->join("order_return_type ort","ort.order_return_type_id = orr.type")
            ->field("orr.refund_sn,orr.price,orr.reason,orr.images,orr.type as refund_type,ort.name as type,".
                "g.goods_id,g.name,concat('$host',g.cover,'$suffix') as cover,og.option_name,og.price as goods_price,og.quantity")
            ->find();
        if (empty($refund)) {
            return ['errcode' => -101, 'message' => '未找到对应信息'];
        }

        $suffix_h = \app\library\UploadHelper::getInstance()->getThumbSuffix(800, 800);
        if (!empty($refund['images'])) {
            $images = explode(',',$refund['images']);
            $imgs = [];
            $imgs_h = [];
            foreach ($images as $image) {
                $imgs[] = $host.$image.$suffix;
                $imgs_h[] = $host.$image.$suffix_h;
            }
            $refund['images_url'] = $imgs;
            $refund['images_h_url'] = $imgs_h;
            $refund['images'] = $images;
        } else {
            $refund['images_url'] = [];
            $refund['images_h_url'] = [];
            $refund['images'] = [];
        }

        $goods = [
            'goods_id' => $refund['goods_id'],
            'name' => $refund['name'],
            'cover' => $refund['cover'],
            'option_name' => $refund['option_name'],
            'price' => $refund['goods_price'],
            'quantity' => $refund['quantity'],
        ];
        unset($refund['goods_id']);
        unset($refund['name']);
        unset($refund['cover']);
        unset($refund['option_name']);
        unset($refund['goods_price']);
        unset($refund['quantity']);
        $refund['goods'] = $goods;
        $refund['types'] = M('order_return_type')->select();
        return ['errcode' => 0, 'message' => '成功', 'content' => $refund];
    }

    /**
     * 修改申请
     * @param $customer_id
     * @param $refund_sn
     * @param $type
     * @param $price
     * @param $reason
     * @param $images
     * @return array
     */
    public function changeRefund($customer_id,$refund_sn,$type,$price,$reason,$images) {
        $refund = M('order_return')
            ->alias("orr")
            ->join("order_goods og","og.goods_id = orr.goods_id AND og.order_id = orr.order_id")
            ->where(['orr.refund_sn' => $refund_sn, 'orr.customer_id' => $customer_id])
            ->field("orr.order_return_id,orr.state,".
                "og.price,og.quantity,og.goods_id")
            ->find();
        if (empty($refund)) {
            return ['errcode' => -103, 'message' => '找不到相关信息'];
        }
        if ($refund['state'] != 1) {
            return ['errcode' => -104, 'message' => '售后状态只能是等待卖家处理状态'];
        }

        if ($type == 3) {
            $price = 0;
        }
        if ($refund['price'] * $refund['quantity'] < $price) {
            return ['errcode' => -106, 'message' => '退款金额不得大于商品总金额'];
        }

        $data = [
            'reason' => $reason,
            'images' => $images,
            'type' => $type,
            'price' => $price,
        ];

        M('order_return')->where(['order_return_id' => $refund['order_return_id']])->save($data);

        // 插入退款详情记录
        $data = [
            'order_return_id' => $refund['order_return_id'],
            'title' => "我修改了售后申请",
            'content' => "退款 ¥".$price."\n".$reason,
            'date_add' => time(),
            'type' => 1
        ];
        M('order_return_message')->add($data);

        return ['errcode' => 0, 'message' => '修改售后申请成功'];
    }

    /**
     * 撤销申请
     * @param $customer_id
     * @param $refund_sn
     * @return array
     */
    public function revokerRefund($customer_id,$refund_sn) {
        $refund = M('order_return')
            ->alias("orr")
            ->where(['orr.refund_sn' => $refund_sn, 'orr.customer_id' => $customer_id])
            ->field("orr.order_return_id,orr.price,orr.state")
            ->find();
        if (empty($refund)) {
            return ['errcode' => -103, 'message' => '找不到相关信息'];
        }
        if ($refund['state'] != 1) {
            return ['errcode' => -104, 'message' => '售后状态只能是等待卖家处理状态'];
        }
        M('order_return')->where(['order_return_id' => $refund['order_return_id']])->save(['state' => 6]);

        // 插入退款详情记录
        $data = [
            'order_return_id' => $refund['order_return_id'],
            'title' => "我撤销了售后申请",
            'date_add' => time(),
            'type' => 1
        ];
        M('order_return_message')->add($data);

        return ['errcode' => 0, 'message' => '撤销售后申请成功'];
    }

    /**
     * 催一催
     * @param $customer_id
     * @param $refund_sn
     * @return array
     */
    public function refundRemind($customer_id,$refund_sn) {
        $remind = M('order_return')
            ->alias("orr")
            ->join("order_return_remind orre","orre.order_return_id = orr.order_return_id","LEFT")
            ->where(['orr.refund_sn' => $refund_sn,'orr.customer_id' => $customer_id])
            ->field("orr.order_return_id,orre.status")
            ->find();
        if (!empty($remind) && $remind['status'] == 1) {
            return ['errcode' => -101, 'message' => "您已经催过了"];
        }
        $data = [
            "order_return_id" => $remind['order_return_id'],
            "customer_id" => $customer_id,
            "date_add" => time(),
            "status" => 1
        ];
        $id = M('order_return_remind')->add($data);
        if (empty($id)) {
            return ['errcode' => -102, 'message' => '失败'];
        }
        // 插入退款详情记录
        $data = [
            'order_return_id' => $remind['order_return_id'],
            'title' => "我提醒卖家及时处理",
            'content' => "",
            'date_add' => time(),
            'type' => 1
        ];
        M('order_return_message')->add($data);
        return ['errcode' => 0, 'message' => '成功'];
    }

    /**
     * 删除订单
     * @param $customer_id
     * @param $order_sn
     * @return array
     */
    public function delOrder($customer_id,$order_sn) {
        $order = M('order')->where(['customer_id' => $customer_id, 'order_sn' => $order_sn])->find();
        if (empty($order)) {
            return ['errcode' => -101, 'message' => '未找到对应订单'];
        }
        if ($order['is_delete'] == 1) {
            return ['errcode' => -102, 'message' => '该订单已被删除'];
        }
        M('order')->where(['customer_id' => $customer_id, 'order_sn' => $order_sn])->save(['is_delete' => 1]);
        return ['errcode' => 0, 'message' => '删除成功'];
    }

    /**
     * 删除售后记录
     * @param $customer_id
     * @param $refund_sn
     * @return array
     */
    public function delRefund($customer_id,$refund_sn) {
        $refund = M('order_return')->where(['customer_id' => $customer_id, 'refund_sn' => $refund_sn])->find();
        if (empty($refund)) {
            return ['errcode' => -101, 'message' => '未找到对应售后信息'];
        }
        if ($refund['is_delete'] == 1) {
            return ['errcode' => -102, 'message' => '该售后信息已被删除'];
        }
        M('order_return')->where(['customer_id' => $customer_id, 'refund_sn' => $refund_sn])->save(['is_delete' => 1]);
        return ['errcode' => 0, 'message' => '删除成功'];
    }






	
	public function batch_receive(){
		$orders = M("order")
		->alias("o")
		->join("order_goods og","og.order_id = o.id")
		->join("goods g","g.goods_id = og.goods_id")
		->group("o.id")
		->field("o.id,o.customer_id,o.order_sn,GROUP_CONCAT(g.name) as goods_name, GROUP_CONCAT(g.mini_name) as mini_names, o.date_add , o.date_received, o.date_send")
		->where(['order_state' => 3,'date_received' => ['lt', time()]])
		->select();
		if(empty($orders)){
			return;
		}

        $expire = \app\library\SettingHelper::get("shuaibo_order_settings", ['receive_expire' => 7]);
        $expire = $expire['receive_expire'];

		$str = "";
		$order_sns = [];
		foreach($orders as $k => $o){
            $order_sns[] = $o['order_sn'];
			if($k % 500 == 0){
				$prefix = C("database.prefix");
				if(!empty($str)){
					$str = substr($str,0, -1);
					$str .= " on duplicate key update order_state=values(order_state) , ".
							"date_received = values(date_received) , ".
                            "date_finish = values(date_finish) , ".
                            "date_end = values(date_end)";
					M()->execute($str);
				}
		
				$str = "insert into {$prefix}order (id,order_state,date_received,date_finish,date_end) values ";
			}

            $str .= "( {$o['id']},  4, " . time() . "," . (time() + $expire * 24 * 60 * 60) . "," . (time() + $expire * 24 * 60 * 60) . " ),";
        }
		if(!empty($str)){
			$str = substr($str,0, -1);
			$str .= " on duplicate key update order_state=values(order_state) , ".
                        "date_received = values(date_received) , ".
                        "date_finish = values(date_finish) , ".
                        "date_end = values(date_end)";
			M()->execute($str);
		}

		M("customer_withdraw_order")
            ->where(['order_sn' => ['in',$order_sns]])
            ->save(['state' => 1]);

		/*
		$client = \app\library\message\MessageClient::getInstance();
		foreach ($orders as $o){
			$message = new \app\library\message\Message();
			$message->setTemplate("finish")
			->setAction(\app\library\message\Message::ACTION_ORDER)
			->setPlatform([\app\library\message\Message::PLATFORM_ALL])
			->setTargetIds($o['customer_id'])
			->setExtras(['order_sn' => $o['order_sn']])
			->setWeixinExtra(['order' => $o])
			->setPushExtra(['title' => '订单收货通知', 'content' => '您购买的'.$o['mini_names'] ."已经确认收货，感谢您的支持"]);
			$client->pushCache($message);
		}*/
	}
	

	
	public function apply_refund($order_sn){
		
		if(empty($order_sn)){
			return "找不到相关订单";
		}
		
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		
		$order = M("order")
		->alias("o")
		->join("order_state os","os.order_state_id = o.order_state")
		->join("order_address oa", "o.address_id = oa.id")
		->join('pay_type pt','pt.pay_id=o.pay_id','LEFT')
		->join('express e','e.express_id=o.express_id','LEFT')
		->field("e.express_name,pt.name as pay_name,o.id,o.order_sn,o.order_amount,o.express_amount,o.comment,o.express_id,o.express,oa.name as address_name, oa.phone as address_phone, oa.province, oa.city, oa.district, oa.address, o.order_state,o.date_add,o.date_end,os.name as state")
		->where(['o.order_sn' => $order_sn])
		->find();
		if(empty($order)){
			return "找不到相关订单";
		}
		
		$order['goods'] = [];
		$order['goods_count'] = 0;
		
		$order_goods = M("order_goods")
		->alias("og")
		->join("goods g","g.goods_id = og.goods_id")
		->join("goods_option go","go.goods_id = g.goods_id and og.option_id = go.id","LEFT")
		->field("g.goods_type,g.name,g.date_end, og.order_id , og.quantity,og.price, ifnull(go.name,'默认') as option_name, ".
				"concat('{$host}', g.cover, '$suffix') as image")
				->where(['og.order_id' =>$order['id']])
				->select();
		
		foreach ($order_goods as $g){
			if($g['goods_type'] == 2){
				$order['goods_type'] = 2;
				$order['date_end'] = $g['date_end'] - time();
			}
			$order['goods_count'] += $g['quantity'];
			$order['goods'][] = $g;
		}
		
		return $order;
	}

	public function payorder($customer_id,$orders,$type){
		
		

		if(empty($orders)){
			return ['errcode' => -101,'message' => '请传入订单信息'];
		}
		

		if(empty($customer_id)){
			return ['errcode' => 99 ,'message' => '请重新登录'];
		}
		

		$order_goods = M("order")
			->alias("o")
			->join("order_goods og","og.order_id = o.id")
			->join("customer c","c.customer_id = o.customer_id")
			->join("goods g","g.goods_id = og.goods_id","LEFT")
			->join("goods_option go","go.id = og.option_id","LEFT")
			->field("g.goods_type,o.order_sn,o.order_state,o.order_amount,o.goods_amount,o.express_amount,".
				"g.goods_id,ifnull(go.stock, g.quantity) as stock,og.quantity,".
				"g.is_deleted,g.time_limit,g.time_unit,g.time_number,g.mini_name, g.on_sale, ".
				"g.date_start,g.date_end,g.max_once_buy,g.max_type,g.max_buy,c.active,c.phone")
			->where(['order_sn' => ['in', $orders],'o.customer_id' => $customer_id])
			->select();


		if(empty($order_goods)){
			return ['errcode' => -103 ,'message' => '订单错误'];
		}
		if($order_goods[0]['active'] == 0){
			$seller_info = \app\library\SettingHelper::get("shuaibo_seller_info",['qq' => '123456789','address' => '杭州市']);
			return ['errcode' => -106, 'message' => '抱歉，您无法执行此操作，如有疑问，请拨打客服电话:' . $seller_info['qq']];
		}
		if(!$order_goods[0]['phone']){
			return ['errcode' => -107 ,'message' => '抱歉，您需先进行手机绑定'];
		}

		$sum = 0;
		$goods_amount = 0;
		$order_sns= [];

		$body = [];
		foreach ($order_goods as $goods){
			if(!in_array($goods['order_sn'], $order_sns)){
				$order_sns[] = $goods['order_sn'];
				$sum += $goods['order_amount'];
				$goods_amount += $goods['goods_amount'];
			}

			$quantity = $goods['quantity'];

			$body[] = $goods['mini_name'] ;
			if(empty($goods['goods_id'])){
				return ['errcode' => -103 ,'message' =>'找不到相关商品'];
			}
			if($goods['is_deleted'] == 1){
				return ['errcode' => -102 , 'message' => $goods['mini_name'].'已被删除'];
			}
			if($goods['on_sale'] == 0){
				return ['errcode' => -103, 'message' => $goods['mini_name'] .'已下架' ];
			}

			if($goods['stock'] < $quantity){
				return ['errcode' => -104, 'message' => $goods['mini_name'].'库存不足'];
			}
			if(!empty($goods['date_start']) && $goods['date_start'] > time()){
				return ['errcode' => -105 , 'message' => $goods['mini_name'].'暂未开启'];
			}

			if(!empty($goods['date_end']) && $goods['date_end'] < time() ){
				return ['errcode' => -106 , 'message' => $goods['mini_name'].'已结束'];
			}
			if($quantity > $goods['max_once_buy']){
				return ['errcode' => -107 , 'message' => $goods['mini_name']."一次性最多购买{$goods['max_once_buy']}件"];
			}
			if($goods['max_type'] == 1){
				$count = M("order_goods")->alias("og")
					->join("order o","o.id = og.order_id")
					->where(['og.goods_id' => $goods['goods_id'], 'customer_id' => $customer_id,'order_state' => ['in','1,2,3,4,5,7']])
					->count();
				if($count > $goods['max_buy']){
					return ['errcode' => -108 , 'message' => $goods['mini_name']."每人最多购买{$goods['max_buy']}件"];
				}
			}
			if($goods['goods_type'] == 3){
				$ogs = M("order_goods")->alias("og")
					->join("order o","o.id = og.order_id")
					->order("date_add desc")
					->where([ 'og.goods_id' => $goods['goods_id'], 'customer_id' => $customer_id,'order_state' => ['in','1,2,3,4,5,7'] , 'o.order_sn' => ['neq' , $goods['order_sn']]])
					->field("date_add")
					->find();
				if(!empty($ogs) && $ogs['date_add'] + $goods['time_limit'] > time()){
					return ['errcode' => -109 ,
						'message' => "该商品在{$goods["time_number"]}".($goods['time_unit'] == 1 ? "天":"小时") ."内仅可购买一次"];
				}

			}

			if($goods['order_state'] != 1){
				if($goods['order_state'] == 2){
					return ['errcode' => -109, 'message' => "订单{$goods['order_sn']}已支付"];
				}
				return ['errcode' => -110 , 'message' => "订单{$goods['order_sn']}未处于待支付状态"];
			}
		}
		$helper = \app\library\order\OrderHelper::getInstance($type);

		if(empty($helper)){
			return ['errcode' => -101 , 'message' => '没有对应的支付方式'];
		}
		if(is_string($helper)){
			return ['errcode' => -102 , 'message' => $helper];
		}


		$pay_order_sn = createNo();

		$subject = "订单号：" . $pay_order_sn;

		$helper->setOrderNumber($pay_order_sn)
			->setOrderType(1)
			->setGoodsAmount($goods_amount)
			->setBody($body[0] . "等")
			->setSubject($subject);

		$res = $helper->get_init_order($customer_id,$sum);

		if($res['errcode'] < 0){
			return $res;
		}
		$order = $helper->getOrder();

		$order['foregin_infos'] = join(",", $order_sns);

		M("order_info")->add($order);

		M("order")->where(['order_sn' => ['in', $order_sns]])->save(['out_order_sn' => $order['order_sn']]);

		$return = $helper->getReturn();

		if($order['pay_id'] == 1){
			$notify = new \app\library\order\OrderNotify($order['order_sn'], $return['count'], $order['pay_id']);

			$notify->notify();
		}

		$return['order_sn'] = $orders[0];
		return ['errcode' => 0, 'order_sum'=> $sum,'message'=>'请求成功', 'content' => $return];
	}
	
	public function getOrderCount($customer_id){
		$prefix = C("database.prefix");
		$count[0] = $this
        ->alias("o")
        ->join("order_goods og",'og.order_id = o.id')
        ->join('goods g','g.goods_id = og.goods_id')
		->where(['o.order_state' => 1,'o.customer_id' => $customer_id,'o.is_delete' => 0,'_string' => "g.address is null"])
		->count();
		
		$count[1] = $this
        ->alias("o")
        ->join("order_goods og",'og.order_id = o.id')
        ->join('goods g','g.goods_id = og.goods_id')
		->where(['o.order_state' => 2,'o.customer_id' => $customer_id,'o.is_delete' => 0,'_string' => "g.address is null"])
		->count();
		
		$count[2] = $this
        ->alias("o")
        ->join("order_goods og",'og.order_id = o.id')
        ->join('goods g','g.goods_id = og.goods_id')
		->where(['o.order_state' => 3,'o.customer_id' => $customer_id,'o.is_delete' => 0,'_string' => "g.address is null"])
		->count();
		
		$count[3] = $this
        ->alias("o")
        ->join("order_goods og",'og.order_id = o.id')
        ->join('goods g','g.goods_id = og.goods_id')
		->where(['o.order_state' => 4,'o.customer_id' => $customer_id,'o.is_delete' => 0,'_string' => "g.address is null"])
		->count();

		$count[4] = M('order_return')
        ->where(['state' => 1,'customer_id' => $customer_id])
        ->count();
		
		return $count;
	}
	
}