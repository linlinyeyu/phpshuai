<?php
namespace app\common\model;

use think\Model;
class CollectionModel extends Model{
	public function getCollectCount($customer_id = 0){
		$count = $this
		->where(['customer_id' => $customer_id])
		->count();
		
		return $count;
	}

    /**
     * 收藏/取消收藏
     * @param $customer_id
     * @param $goods_id
     * @return array
     */
	public function collection($customer_id,$goods_id) {
        $collection = M('collection')->where(['customer_id' => $customer_id,'goods_id' => $goods_id])->find();
        if (empty($collection)) {
            M('collection')->add([
                'customer_id' => $customer_id,
                'goods_id' => $goods_id,
                'date_add' => time(),
                'date_upd' => time()
            ]);
            return ['errcode' => 100, 'message' => '收藏成功'];
        } else {
            M('collection')->where(['customer_id' => $customer_id,'goods_id' => $goods_id])->delete();
            return ['errcode' => 101, 'message' => '取消收藏成功'];
        }
    }
}