<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Comment extends Admin
{
	public function index()
	{
		$score =  (int)I("score");
		!I("?score") && $score = -1;
		$map = " oc.is_deleted = 0 and oc.type = 1 and oc.seller_id = ".session("sellerid");
		I('get.min_date') && $map .= " AND oc.date_add >= ".strtotime(I('get.min_date')) ;
		I("get.max_date") && $map .=" AND oc.date_add <= ".(strtotime(I("get.max_date"))) ;
		I('get.order_number') && $map .=" AND o.order_sn = '".trim(I('get.order_number')) . "'";
		I('get.customer_name') && $map .=" AND c.nickname like '%".trim(I('get.customer_name'))."%'";
		I("get.phone") && $map .= " AND c.phone like '".trim(I("get.phone")). "%'";
		I("get.goods_id") && $map .=" AND oc.goods_id = ". I("get.goods_id") . " ";
		$score >= 0 && $map .= " AND oc.score = ".$score . " ";
		
		$count = $order_comment = M("order_comment")
		->alias("oc")
		->join("order o","o.id = oc.order_id")
		->join("customer c","c.customer_id =oc.customer_id")
		->join("goods g","g.goods_id = oc.goods_id")
		->where($map)
		->count();
		$page = new \com\Page($count , 20);
		
		$order_comment = M("order_comment")
		->alias("oc")
		->join("order o","o.id = oc.order_id")
		->join("customer c","c.customer_id =oc.customer_id")
		->join("goods g","g.goods_id = oc.goods_id")
            ->join("order_goods og","og.order_id = oc.order_id AND og.goods_id = oc.goods_id AND og.option_id = oc.option_id","LEFT")
            ->join("goods_option go","go.id = og.option_id","LEFT")
            ->join("seller_user su","su.id = oc.user_id", "LEFT")
		->field("o.order_sn ,oc.*,c.nickname,g.name as goods_name,su.username,IFNULL(go.name,'默认') as option_name")
		->where($map)
		->limit($page->firstRow . "," . $page->listRows)
		->order("oc.date_add desc")
		->select();
		
		$this->assign("comments", $order_comment);
		$this->assign("score", $score);
		$this->assign("page", $page->show());
		$this->display();
	}
	
	public function deleted(){
		(!$id = I("id")) && $this->error("请输入id");
		M("order_comment")->where(['id'=>$id])->save(['is_deleted' => 1 ,'user_id' => session("id")]);
		$this->log("删除评论：". $id);
		$this->success("操作成功");
	}
	
	public function reply(){
		(!$id = I("id")) && $this->error("请输入id");
		(!$reply = I("reply") ) && $this->error("请输入回复内容");
		
		M("order_comment")->where(['id' => $id])
		->save(['user_id' => session('id'),'date_reply' => time(),'reply_content' => $reply]);
		$this->log("回复评论：". $id . ", $reply");
		$this->success("操作成功");
	}
}