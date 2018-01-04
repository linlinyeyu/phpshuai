<?php
namespace app\wap\model;
use think\Model;
class Cart extends Model{
	
	public function deleteCart($carts){
		if(empty($carts)){
			return;
		}
		if(!is_array($carts)){
			$carts = array($carts);
		}
        $this->where(array("cart_id" => array("in", join(",", $carts))))->delete();
	}
    
    public function addCart($item_id, $customer_id, $quantity = null){
       	$res = array(
                     'message' => '添加成功',
                     'errcode' => 0
                     );
//        $item_id = (int)I("item_id");
//        $customer_id = get_customer_id();
        
        if($item_id <= 0){
            $res['message'] = "商品信息有误";
            $res['errcode'] = -101;
            return $res;
        }
        if($customer_id <= 0){
            $res['message'] = "请传入用户信息";
            $res['errcode'] = -102;
            return $res;
        }
        $items = M("items")->where(array('item_id'=> $item_id))->field("is_ten,price,quantity")->find();
        
        if($items['price'] <= $items['quantity']){
            $res['message'] = "此期号已经结束，请刷新";
            $res['errcode'] = -103;
            return $res;
        }
//        $quantity = (int)I("quantity");
        if($quantity < 0){
            $res['message'] = '数量错误';
            $res['errcode'] = -104;
            return $res;
        }
        $addCount = $items['is_ten'] ? 10 : 1;
        if($quantity > 0){
            if($items['is_ten'] == 1 && $quantity % 10 != 0){
                $res['message'] = '十元商品必须为十元为整数';
                $res['errcode'] = -105;
                return $res;
            }
            if($items['price'] - $items['quantity'] < $quantity){
                $addCount = $items['price'] - $items['quantity'];
            }else{
                $addCount = $quantity;
            }
            
        }
        
        $oldCart = M("cart")->where(array('item_id'=> $item_id, 'customer_id'=> $customer_id))->find();
        if(empty($oldCart)){
            $cart = array(
                          'item_id'=> $item_id,
                          'customer_id' => $customer_id,
                          'date_add' => microtime_float(),
                          'quantity' => $addCount,
                          'date_upd' => microtime_float(),
                          );
            M("cart")->add($cart);
        }else{
            if($oldCart['quantity'] + $addCount > $items['price'] - $items['quantity']){
                $addCount = $items['price'] - $items['quantity'];
            }else{
                $addCount += $oldCart['quantity'];
            }
            M("cart")->where(array("cart_id" => $oldCart['cart_id']))->save(array('quantity' => $addCount));
        }
        return $res;
    }
    
    public function getCartCount($customer_id){
    	if(empty($customer_id)){
    		return 0;
    	}
    	return M("cart")
		->alias("c")
		->where("c.customer_id = $customer_id ")
		->count();
    }
}