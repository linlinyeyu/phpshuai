<?php
namespace app\validate;
use \think\Validate;
class UserValidate extends Validate{
// 验证规则
	protected $rule =   [
        'phone'  => 'require|length:10',
        'password'   => 'require|alphaNum|length:4,16',
        'nickname' => 'require|length:4,14',    
    ];
// 验证信息
    protected $message  =   [
        'name.require' => '请填写手机号',
        'name.length'     => '手机格式有误',
        'password.alphaNum'   => '密码只能为数字或字母',
        'password.length'  => '密码长度为4-16个字节',
        'nickname.require'        => '请填写昵称',
        'nickname.length' => '昵称长度为4-14个字节'    
    ];
}
