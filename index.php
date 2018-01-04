<?php
ob_start();
// [ 应用入口文件 ]
// 定义应用目录
define('APP_PATH', __DIR__ . '/application/');
// 开启调试模式
define('APP_DEBUG', true);
// 开启多模块设计
define('APP_MULTI_MODULE',true);

// 单独关闭当前入口的路由
define('APP_ROUTE_ON',false);
// 或者单独关闭当前入口的强制路由
define('APP_ROUTE_MUST',false);

// 关闭应用自动执行
define('APP_AUTO_RUN', false);

define('CONF_DEFINE',__DIR__. '/conf/');
define('STATICS',__DIR__. '/public/');
define('IS_API',false);
//define('APP_HOOK', true);
//更改默认的runtime目录位置
define('RUNTIME_PATH',__DIR__. '/data/');

// 加载框架引导文件
require __DIR__ . '/simple/engine/start.php';

\think\Config::load(CONF_DEFINE. 'config.php');	
\think\Config::load(CONF_DEFINE. 'db.php');
//\think\Hook::add('view_filter','behavior\\TokenBuild');
//\think\Hook::add('db_add','behavior\\AutoTokenValidate');
\think\App::run();

