<?php
namespace app\app\controller;
class Error
{
/**
 * 错误信息
 */
	public function index()
	{
		die('404 http code');
	}
	
	public function test(){
		\app\library\QrcodeHelper::generate_customer(127);
	}
}