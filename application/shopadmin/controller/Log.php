<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Log extends Admin
{
	public function index() 
	{
		$map = 'sa.seller_id = '.session("sellerid");
		I('get.min_date') && $map .= " AND sa.date_add >= ".strtotime(I('get.min_date')) ;
		I("get.max_date") && $map .=" AND sa.date_add <= ".(strtotime(I("get.max_date")) + 24 *60 *60) ;
		I("get.name") && $map .= " AND su.username like '%".I("get.name")."%'";
		I("get.comment") && $map .= " AND sa.comment like '%".I("get.comment")."%'";
		$count=M('seller_action')->alias('sa')
		->join("seller_user su","su.seller_id = sa.seller_id")
		->where($map)->count();
		$page = new \com\Page($count, 15);
		$lists = M('seller_action')
		->alias('sa')
		->join("seller_user su","su.seller_id = sa.seller_id")
		->field('sa.*')
		->where($map)
		->order("sa.date_add DESC")
		->limit($page->firstRow . ',' . $page->listRows)
		->select();
		$this->assign('page',$page->show());
		$this->assign('lists',$lists);
		$this->display();
	}
	public function user() 
	{
		$map = '1 = 1';
		I('get.min_date') && $map .= " AND cl.date_add >= ".strtotime(I('get.min_date')) ;
		I("get.max_date") && $map .=" AND cl.date_add <= ".(strtotime(I("get.max_date")) + 24 *60 *60) ;
		$name = I("name");
		I("get.name") && $map .=" AND (c.nickname like '%$name%' or c.realname like '%$name%')";
		I("get.uuid") && $map .=" AND c.uuid like '".I("get.uuid")."%'";
		I("get.phone") && $map .=" AND c.phone like '".I("get.phone")."%'";
		$count=M('customer_log')->alias('cl')
		->join("customer c","c.customer_id = cl.customer_id")
		->where($map)->count();
		$page = new \com\Page($count, 15);
		$lists = M('customer_log')
		->alias('cl')
		->join("customer c","c.customer_id = cl.customer_id")
		->field('cl.*')
		->where($map)
		->order("cl.date_add DESC")
		->limit($page->firstRow . ',' . $page->listRows)
		->select();
		$this->assign('page',$page->show());
		$this->assign('lists',$lists);
		$this->display();
	}
}