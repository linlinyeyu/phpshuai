<?php
namespace app\app\controller;
class CollectionAction
{
	/**
	 * 新增收藏
	 */
	public function addCollection(){
		$res = array(
				'message' => '添加收藏成功',
				'errcode' => 0
		);
		$goods_id =(int)I('goods_id');
		
		if(empty($goods_id)){
			return ['errcode' => -102,'message' => '请传入商品'];
		}
		
		$customer_id = get_customer_id();
		if (empty($customer_id) || $customer_id <= 0) {
			return ['errcode' => 99, 'message' => '请重新登陆'];
		}
		
		$collection = M("collection")->where(['customer_id' => $customer_id,'goods_id' => $goods_id])->find();
		if(!empty($collection)){
			return ['errcode' => -101, 'message' => '该商品已收藏'];
		}
		
		$data = array(
				'goods_id' => $goods_id,
				'date_add' => time(),
				'date_upd' => time(),
				'customer_id' => $customer_id
		);
		$collection_id = M('collection')->add($data);
		if(empty($collection_id)){
			return ['errcode' => -102,'message' => '添加失败'];
		}
		
		$collection = S("shuaibo_collection".$customer_id);
		if(!empty($collection)){
			$collection[] = $goods_id;
			S("shuaibo_collection".$customer_id,$collection);
		}else{
			$collection = array($goods_id);
			S("shuaibo_collection".$customer_id,$collection);
		}
		
		return $res;
	}
	
	/**
	 * 取消收藏
	 */
	public function cancelCollection(){
		$res = array(
				'message' => '添加收藏成功',
				'errcode' => 0
		);
		$goods_id = (int)I('goods_id');
		
		if(empty($goods_id)){
			return ['errcode' => -102,'message' => '请传入商品'];
		}
		
		$customer_id = get_customer_id();
		if (empty($customer_id) || $customer_id <= 0) {
			return ['errcode' => 99, 'message' => '请重新登陆'];
		}
		
		$data = array(
				'goods_id' => $goods_id,
				'customer_id' => $customer_id
		);
		
		$result = M('collection')->where($data)->delete();
		if(!$result){
			return ['errcode' => -102, 'message' => '删除失败'];
		}
		$collection = S("shuaibo_collection".$customer_id);
		if(!empty($collection)){
			foreach ($collection as $key => $value){
				if($value == $goods_id){
					array_splice($collection, $key,1);
				}
			}
			S("shuaibo_collection".$customer_id,$collection);
		}
		
		return $res;
	}
	
	/**
	 * 获取收藏
	 */
	public function getCollection(){
		$res = array(
				'message' => '成功',
				'errcode' => 0
		);
		$customer_id = get_customer_id();
		if (empty($customer_id) || $customer_id <= 0) {
			return ['errcode' => 99, 'message' => '请重新登陆'];
		}
		
		$host = \app\library\SettingHelper::get("bear_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
		
		$collection = S("shuaibo_collection".$customer_id);
		$goods = [];
		if(!empty($collection)){
			$goods = M("goods")
			->where(['goods_id' => ["in",$collection]])
			->field("goods_id,name as goods_name,price,market_price,concat('$host',cover,'$suffix') as cover")
			->select();
		}else{
			$goods = M("collection")
			->alias("c")
			->join("goods g","g.goods_id = c.goods_id")
			->where(["c.customer_id" => $customer_id])
			->field("g.goods_id,g.name as goods_name,g.shop_price,g.market_price,concat('$host',g.cover,'$suffix') as cover")
			->select();
		}
		
		return ['errcode' => 0, 'message' => '请求成功', 'content' => $goods];
	}
}