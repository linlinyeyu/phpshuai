<?php
namespace app\wap\controller;
use think\Controller;
use think\Cache;
use org\oauth\driver\Weixin;
class Normal extends \app\wap\BaseController
{
	protected $skip_weixin = true;
	
}