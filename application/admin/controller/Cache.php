<?php
namespace app\admin\controller;
use app\admin\Admin;
class Cache extends Admin
{
	public function clean()
	{
		$dir = new \com\Dir;
		if(is_dir(RUNTIME_PATH. 'cache')){$dir->delDir(RUNTIME_PATH. 'cache');}
		if(is_dir(RUNTIME_PATH. 'temp')){$dir->delDir(RUNTIME_PATH. 'temp');}
		if(is_dir(RUNTIME_PATH. 'logs')){$dir->delDir(RUNTIME_PATH. 'logs');}
		\think\Cache::clear(); // 清空缓存数据 
		die(L('_CACHE_CLEAN_'));
	}
}