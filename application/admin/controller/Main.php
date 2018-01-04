<?php
namespace app\admin\controller;
use app\admin\Admin;
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
    	
    	$begintime=date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d'),date('Y')));
    	$endtime=date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1);
    	$order = M("order")->where(['order_state' => ["in",'1,2,3,4,5,7'],'date_add' => [['egt',strtotime($begintime)],['elt',strtotime($endtime)]]])->count();
    	$new_people = M("customer")->where(['date_add' => [['egt',strtotime($begintime)],['elt',strtotime($endtime)]]])->count();
    	
    	//订单信息
    	$pay_order = M("order")->where(['order_state' => 1])->field("count(1)")->buildSql();
    	$send_order = M("order")->where(['order_state' => 2])->field("count(1)")->buildSql();
    	$confirm_order = M("order")->where(['order_state' => 3])->field("count(1)")->buildSql();
    	$finish_order = M("order")->where(['order_state' => 4])->field("count(1)")->buildSql();
    	$wait_comment = M("order_comment")->where("reply_content is null and type = 1")->field("count(1)")->buildSql();
    	 
    	$order_count = M("order")->field("ifnull($pay_order,0) as pay_order,ifnull($send_order,0) as send_order,
    			ifnull($confirm_order,0) as confirm_order,ifnull($finish_order,0) as finish_order,ifnull($wait_comment,0) as wait_comment")->find();
    	
    	
    	$this->assign('server_info', $info);
    	$this->assign("order",$order);
    	$this->assign("new_people",$new_people);
    	$this->assign("order_count",$order_count);
    	$this->display();
    }
}