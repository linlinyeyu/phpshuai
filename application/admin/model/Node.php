<?php 
namespace app\admin\model;

use think\Model;

class Node extends Model
{
	//自动验证
	protected $_validate= [
		['title','require','菜单名称必须！',1,'',3],
		['name','require','节点名称必须！',1,'',3],
	];

	// 获取所有节点信息
	public function getAllNode($where = '' , $order = 'sort ASC') {
		return $this->where($where)->order($order)->select();
	}

	// 获取单个节点信息
	public function getNode($where = '',$field = '*') {
		return $this->field($field)->where($where)->find();
	}

	// 删除节点
	public function delNode($where) {
		if($where){
			return $this->where($where)->delete();
		}else{
			return false;
		}
	}

	// 更新节点
	public function upNode($data) {
		if($data){
			return $this->save($data);
		}else{
			return false;
		}
	}

	// 子节点
	public function childNode($id){
		return $this->where(array('pid'=>$id))->select();
	}
    // 更新成功后的回调方法
    protected function _after_update($data, $options = [])
    {
    		\think\Cache::clear(); // 清空缓存数据 
    }

    // 插入成功后的回调方法
    protected function _after_insert($data, $options = [])
    {
    		\think\Cache::clear(); // 清空缓存数据 
    }

    // 删除成功后的回调方法
    protected function _after_delete($data, $options = [])
    {
    		\think\Cache::clear(); // 清空缓存数据 
    }

}