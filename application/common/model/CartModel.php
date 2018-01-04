<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/5/9
 * Time: 下午5:30
 */
namespace app\common\model;
use think\Model;
class CartModel extends Model{
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
     * 获取购物车商品
     */
    public function getCarts($customer_id) {
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
        $carts = M('cart')
            ->alias("c")
            ->where(['c.customer_id' => $customer_id])
            ->join("goods g","g.goods_id = c.goods_id")
            ->join("goods_option go","go.id = c.option_id","LEFT")
            ->join("collection co","co.goods_id = c.goods_id AND co.customer_id = c.customer_id","LEFT")
            ->join("special_goods sg","sg.goods_id = g.goods_id AND sg.special_id = 1 AND sg.status = 1 AND sg.date_start <= ".time()." AND sg.date_end >=".time(),"LEFT")
            ->join("seller_shopinfo ss","ss.seller_id = g.seller_id")
            ->field("concat('$host',g.cover,'$suffix') as cover,g.name,g.on_sale,g.shop_price,g.market_price,g.seller_id,g.goods_id,".
                "IFNULL(go.sale_price,g.shop_price) as sale_price,IFNULL(go.id,0) as option_id,ifnull(go.stock,g.quantity) as stock,g.max_once_buy,".
                "c.cart_id,c.quantity,IFNULL(c.option_name,'') as option_name,".
                "ss.shop_name,ss.kf_qq,".
                "sg.special_goods_id,".
                "IFNULL(collection_id,0) as collection_id")
            ->order("c.date_add DESC")
            ->group("g.goods_id")
            ->select();
        $sellers = [];
        foreach ($carts as $cart) {
            $data = $cart;
            if (!empty($data['special_goods_id'])) {
                $data['sale_price'] = 1;
            }
            $seller_id = $data['seller_id'];
            $sellers["$seller_id"]['seller_id'] = $data['seller_id'];
            $sellers["$seller_id"]['shop_name'] = $data['shop_name'];
            $sellers["$seller_id"]['kf_qq'] = $data['kf_qq'];
            unset($data['kf_qq']);
            unset($data['seller_id']);
            unset($data['shop_name']);
            if ($data['stock'] < $data['max_once_buy']) {
                $data['max_once_buy'] = $data['stock'];
            }
            $sellers["$seller_id"]['goods'][] = $data;
        }
        return array_values($sellers);
    }

    /**
     * 编辑购物车商品数量
     */
    public function editCart($customer_id, $data = []) {
        $cart_ids = [];
        for ($i = 0; $i < count($data); $i++) {
            $key = $data["$i"]['cart_id'];
            $value = $data["$i"]['quantity'];
            if ($value <= 0) {
                $value == 1;
            }
            $cart_ids["$key"] = $value;
        }
        $ids = implode(',',array_keys($cart_ids));
        $sql = "UPDATE vc_cart SET quantity = CASE cart_id ";
        foreach ($cart_ids as $id => $myvalue) {
            $sql .= sprintf("WHEN %d THEN %d ", $id, $myvalue);
        }
        $sql .= "END WHERE cart_id IN ($ids) AND customer_id = ".$customer_id;
        $res = M()->execute($sql);
        if ($res !== false) {
            return ['errcode' => 0, 'message' => '编辑成功'];
        }
        return ['errcode' => -101, 'message' => '编辑失败'];
    }

    /**
     * 加入购物车
     */
    public function addCart($customer_id,$goods_id,$option_id,$quantity = 1) {
        $option_name = null;
        if(!empty($option_id)){
            $option_name = M("goods_option")->where(['goods_id' => $goods_id, 'id' => $option_id])->getField("name");
        }else{
            $option_id = 0;
        }
        $customer = M("customer")->where(array("customer_id" => $customer_id))->field("customer_id")->find();
        if (empty($customer)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $cart = M('cart')
            ->alias("c")
            ->where(['c.customer_id' => $customer_id,'c.goods_id' => $goods_id,'c.option_id' => $option_id])
            ->join("goods g","g.goods_id = c.goods_id")
            ->field("c.*,g.max_once_buy")
            ->find();
        if (empty($cart)) {
            if (!empty($cart['max_once_buy']) && $quantity > $cart['max_once_buy']) {
                $quantity = $cart['max_once_buy'];
            }
            // 新增
            $data = [
                'goods_id' => $goods_id,
                'customer_id' => $customer_id,
                'quantity' => $quantity,
                'option_id' => $option_id,
                'option_name' => $option_name,
                'date_add' => time(),
                'date_upd' => time()
            ];
            $id = M('cart')->add($data);
            if (empty($id)) {
                return ['errcode' => -101, 'message' => '失败'];
            }
        } else {
            // 添加
            $data = [
                'quantity' => $cart['quantity'] + $quantity,
                'date_upd' => time()
            ];
            if ($quantity + $cart['quantity'] > $cart['max_once_buy']) {
                $data['quantity'] = $cart['max_once_buy'];
            }
            M('cart')->where(['cart_id' => $cart['cart_id']])->save($data);
        }
        return ['errcode' => 0, 'message' => '成功'];
    }
}