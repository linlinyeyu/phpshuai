<?php
namespace app\app\controller;

class CartAction{

	/**
	 * 加入购物车
	 */
	public function addCart(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $goods_id = I('goods_id');
        if (empty($goods_id)) {
            return ['errcode' => -100, 'message' => '请传入商品信息'];
        }
        $option_id = I('option_id');
        if (empty($option_id)) {
            $option_id = 0;
        }
        $quantity = (int)I('quantity');
        if ($quantity <= 0) {
            $quantity = 1;
        }

        return D('cart_model')->addCart($customer_id,$goods_id,$option_id,$quantity);
	}
	
	/**
	 * 获取购物车
	 */
	public function getCarts(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $carts = D('cart_model')->getCarts($customer_id);

        return ['errcode' => 0, 'message' => '成功', 'content' => $carts];
	}
	
	/**
	 * 移除购物车
	 */
	public function removeCart(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $cart_ids = I('cart_ids');
        if (empty($cart_ids)) {
            return ['errcode' => -100, 'message' => '请传入商品信息'];
        }
        $cart_ids = explode(",",$cart_ids);
        $condition = [
            'customer_id' => $customer_id,
            'cart_id' => ['in',$cart_ids]
        ];
        D('cart_model')->myDel($condition);
        return ['errcode' => 0, 'message' => '删除成功'];
	}
	/**
	 * 编辑购物车数量
	 */
	public function editCart(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        // [{"cart_id":"","quantity":""}]
        $data = json_decode(I('data'),true);
        if (empty($data)) {
            return ['errcode' => -100, 'message' => '请传入商品信息'];
        }

        return D('cart_model')->editCart($customer_id,$data);
	}
}