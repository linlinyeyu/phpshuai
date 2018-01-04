<?php
namespace behavior;

use think\Response;

/**
 * 系统行为扩展：表单令牌生成
 */
class AutoTokenValidate
{
	public function run(&$data)
	{
	   if(!$this->autoCheckToken($data)) {
	   		echo json_encode(Response::error(L('_TOKEN_ERROR_')));
        		exit;
        }
	}

    // 自动表单令牌验证 oliver
    // TODO  ajax无刷新多次提交暂不能满足
    private function autoCheckToken($data) {
        // 支持使用token(false) 关闭令牌验证
        if(isset($this->options['token']) && !$this->options['token']) return true;
        if(C('token.token_on')){
            $name   = C('token.token_name');
            if(!isset($data[$name]) || !isset($_SESSION[$name])) { // 令牌数据无效
                return false;
            }
            // 令牌验证
            list($key,$value)  =  explode('_',$data[$name]);
            if(isset($_SESSION[$name][$key]) && $value && $_SESSION[$name][$key] === $value) { // 防止重复提交
                unset($_SESSION[$name][$key]); // 验证完成销毁session
                return true;
            }
            // 开启TOKEN重置
            if(C('token.token_reset')) unset($_SESSION[$name][$key]);
            return false;
        }
        return true;
    }
}