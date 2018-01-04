<?php
namespace app\wap\controller;
use think\Controller;
use think\Cache;
class Error extends Controller
{
	public function index()
	{
		echo 'error1';
	}	
	
	public function test(){
		Cache::rm("jsapi_ticket");
		Cache::rm("js_access_token");
	}
}