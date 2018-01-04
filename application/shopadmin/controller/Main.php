<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Main extends admin {
	
    public function index(){
    	
    	$mysql= M()->query("select VERSION() as version");
    	$mysql=$mysql[0]['version'];
    	$mysql=empty($mysql)?L('UNKNOWN'):$mysql;
    	
    	//server infomaions
    	$info = array(
    			L('OPERATING_SYSTEM') => PHP_OS,
    			L('OPERATING_ENVIRONMENT') => $_SERVER["SERVER_SOFTWARE"],
    			L('PHP_RUN_MODE') => php_sapi_name(),
    			L('MYSQL_VERSION') =>$mysql,
                L('PHP_VERSION')    => PHP_VERSION, 
    			L('PROGRAM_VERSION') => SIMPLE_VERSION . "&nbsp;&nbsp;&nbsp; [<a href='javascirpt:void(0)' target='_blank'>Simple</a>]",
    			L('UPLOAD_MAX_FILESIZE') => ini_get('upload_max_filesize'),
    			L('MAX_EXECUTION_TIME') => ini_get('max_execution_time') . "s",
    			L('DISK_FREE_SPACE') => round((@disk_free_space(".") / (1024 * 1024)), 2) . 'M',
    	);
    	
    	//店铺信息
    	$seller = [];
    	if (!$seller = S("shopadmin_shop")){
    		$seller = M("seller_shopinfo")->where(['seller_id' => session('sellerid')])->find();
    		S("shopadmin_shop",$seller);
    	}
    	$log = M("seller_action")->where(['seller_id' => session('sellerid')])->field("comment")->order("date_add desc")->limit(5)->select();
    	$receive_order = M("order")->where(['order_state' => 2,'seller_id' => session("sellerid")])->field("count(1)")->buildSql();
    	$return_order = M("order_return")->alias("orr")->join("order o",'o.id = orr.order_id')->where(['o.seller_id' => session('sellerid')])->field("count(1)")->buildSql();
    	$order = M("order")->field("ifnull($receive_order,0) as receive_order,ifnull($return_order,0) as return_order")->find();
    	
    	//最新公告
    	$count = M("recent_updates")->count();
    	$page = new \com\Page($count,10);
    	$recent_udaptes = M("recent_updates")->field("title,update_id,type")->order("date_upd desc")->limit($page->firstRow.",".$page->listRows)->select();
    	
    	$this->assign('server_info', $info);
    	$this->assign("log",$log);
    	$this->assign("order",$order);
    	$this->assign("shop",$seller);
    	$this->assign("updates",$recent_udaptes);
    	$this->assign("page",$page->show());
    	$this->display();
    }
}