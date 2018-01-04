<?php
return [
    'url_route_on' => true,
    'log'           => [
        'type' => 'file', // 支持 socket trace file
    ],
    'session'       => [
    		'type'	=> '',
    		'expire' => 36000,
    		'auto_start'     => true,
    		'name'  =>'gg',
    ],
	'HTML_CACHE_ON'=>true,//打开缓存
	'HTML_PATH' =>'__APP__/html',//静态页面存放的目录，这里会放在ROOT/Home/html/下
	'HTML_CACHE_TIME'=>'60',//静态页面存活的时间,单位为秒
	'HTML_FILE_SUFFIX' => '.html',//静态页面的后缀名，也可以改为其他的后缀名字
		
	'token_expire' => 7200,
	'refresh_expire' => 2592000,
    'parse_str'              => [
        '__PUBLIC__' => '/public/',
    ],
	
    // 默认模块名
    'default_module'         => 'admin',
    // 禁止访问模块
    'deny_module_list'       => ['runtime', 'conf', 'data', 'common'],
    // 默认控制器名
    'default_controller'     => 'Home',
    // 默认操作名
    'default_action'         => 'index',
    'lang_cookie_var'        => 'l',
    'lang_switch_on'         => true,
    // 支持的多语言列表
    'lang_list'              => ['zh-cn','en-us'],
    'exception_ignore_type'  => E_STRICT|E_ERROR,
    'show_error_msg'        =>false,
	'url_common_param' => true,
    'cache'                  => [
        'type'   => 'File',
        'path'   => CACHE_PATH,
        'prefix' => '',
        'expire' => 36000,
    ],
		
	'THINK_EMAIL' => array(

		'SMTP_HOST' => 'smtp.qq.com', //SMTP服务器
		
		'SMTP_PORT' => '465', //SMTP服务器端口
		
		'SMTP_USER' => '1211065934@qq.com', //SMTP服务器用户名
		
		'SMTP_PASS' => 'btatfctepujfbagc', //SMTP服务器密码
		
		'FROM_EMAIL' => '1211065934@qq.com',
		
		'FROM_NAME' => '帅柏', //发件人名称
		
		'REPLY_EMAIL' => '', //回复EMAIL（留空则为发件人EMAIL）
		
		'REPLY_NAME' => '', //回复名称（留空则为发件人名称）
		
		'SESSION_EXPIRE'=>'72',
		), 
		
	/*'redis'  =>[
			'host'       => '127.0.0.1',
			'port'       => 6841,
			'password'   => 'lingmei1212',
	],*/
	/*'redis'  =>[
			'host'       => 'r-wz91e56d85947e84.redis.rds.aliyuncs.com',
			'port'       => 6379,
			'password'   => 'LINGMEIyunhe1212',
	],*/
	'IGNORE_REFERER' => [
	    "h.hongsita.com",
        "vip.zhasita.com",
        "hy.nihenpi.com",
        "fivechess_progress",
        "gghzj.cc",
        "www.app2ez.net"
    ],

    'IGNORE_AGENT' => [
        'Android',
        'iOS',
        'iPad',
        'iPhone'
    ],
		
    'response_auto_output'   =>true,
    'extra_config_list'      => false,
    'default_return_type'    => 'html',
    'db_fields_strict'       => true,
    'db_field_time'           => 36000,
    'lazywrite_time'           => 36000, //延迟加载的时间
    'SHOP_AUTH_ON' => false,
	'USER_AUTH_ON' => true,
	'USER_AUTH_TYPE' => 1,
	'USER_AUTH_KEY' => 'authId',
	'ADMIN_AUTH_KEY' => 'administrator',
	'AUTH_PWD_ENCODER' => 'md5',
	'USER_AUTH_GATEWAY' => '/login.html',
	'NOT_AUTH_MODULE' => 'Login,Verify',
	'REQUIRE_AUTH_MODULE' => '',
	'NOT_AUTH_ACTION' => '',
	'REQUIRE_AUTH_ACTION' => '',
	'GUEST_AUTH_ON' => false,
	'GUEST_AUTH_ID' => 0,
	'SPECIAL_USER' => 'admin',
	'SINGLE_THREAD' => [
			'app.address.saveaddress',
			'app.address.delete',
			'app.address.editstatus',
			'app.commission.withdraw',
			'app.user.uploadavater',
			'app.user.editname',
			'app.user.changeaddress',
			'app.user.changebirthday',
			'app.user.changephone_first',
			'app.user.changephone_second',
			'app.user.changesex',
			'app.user.bindphone',
			'app.user.feedback',
			'app.order.generate',
			'app.order.payorder',
			'app.order.cancel',
			'app.order.delivery',
			'app.order.refund',
			'app.order.delay',
			'app.order.comment',
			'app.order.delivery',
	'app.commission.withdraw']
];