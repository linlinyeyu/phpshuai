<?php
namespace app\oauth;
use think\Cache;
use org\oauth\driver\Qq;
use org\oauth\Driver;
class QqWebOauth extends YZLOauth{
    protected $oauth_name = "QQ";
    protected $oauth_type = 4;
    protected $client_id = "101408899";
    protected $url = "http://app.shuaibomall.net/web/customerAction/qqWebLogin";
    protected $app_key = "08ff65d684973d0fb7184cad4c5c129d";
    /**
     * 登录or注册(non-PHPdoc)
     * @see \app\oauth\YZLOauth::isLoginOrRegsiter()
     */
    public function isLoginOrRegsiter(){
        $res = array('errcode' => 0 , 'message' => '成功','oauth' => 'QqWeb');
        $code = $this->param['code'];
        $openid = null;
        if (!empty($code)){
            $token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&client_id=".$this->client_id.
            "&redirect_uri=".urlencode($this->url)."&client_secret=".$this->app_key."&code=".$code;
            $response = file_get_contents($token_url);
            if (strpos($response, "callback") !== false){
                $lpos = strpos($response, "(");
                $rpos = strrpos($response, ")");
                $response = substr($response, $lpos + 1, $rpos - $lpos -1);
                $msg = json_decode($response);
                if (isset($msg->error))
                {
                    return ['errcode' => '-301','message' => $msg->error_description];
                }
            }
            $params = array();
            parse_str($response,$params);
            $graph_url = "https://graph.qq.com/oauth2.0/me?access_token=".$params['access_token'];
            $str = file_get_contents($graph_url);
            if (strpos($str, "callback") !== false)
            {
                $lpos = strpos($str, "(");
                $rpos = strrpos($str, ")");
                $str = substr($str, $lpos + 1, $rpos - $lpos -1);
            }
            $user = json_decode($str);
            if (isset($user->error))
            {
                return ['errcode' => '-301','message' => $user->error_description];
            }
            $openid = $user->openid;
        }
       
        if(empty($openid)){
            return ['errcode' => -101, 'message' => '请传入qq凭证'];
        }
        if(0 == preg_match('/^[0-9a-fA-F]{32}$/', $openid)){
            return ['errcode' => -102 , 'message' => '无效的凭证'];
        }
        $condition = array('qq_open_id' => $openid);
        $customer = M("customer")->where($condition)->field("customer_id, qq_open_id, phone")->find();
        $res = array_merge($res,$this->getRegParams());
        
        if(!empty($customer)){
            if (empty($customer['phone'])){
                $res['content'] = ['result' => false];
                return $res;
            }
            
            $this->customer_id = $customer['customer_id'];
            $access_token = M("customer")->where(['customer_id' => $this->customer_id])->getField("access_token");
            
            header("HTTP/1.1 303 See Other");
            header("Location:http://www.shuaibomall.net/module/index.html?access_token=$access_token");
            exit();
        }else{
            if (isset($this->param['agent_id'])) {
                header("HTTP/1.1 303 See Other");
                header("Location:http://www.shuaibomall.net/module/index.html?result=false&qq_open_id=".$openid."&agent_id=".$this->param['agent_id']);
                exit();
            }
            header("HTTP/1.1 303 See Other");
            header("Location:http://www.shuaibomall.net/module/index.html?result=false&qq_open_id=".$openid);
            exit();
        }
    }
    /**
     * 获取注册方式(non-PHPdoc)
     * @see \app\oauth\YZLOauth::getRegParams()
     */
    protected function getRegParams(){
        $info = array();
        $info['qq_open_id'] = $this->param['openid'];
        try{
            $avater = $this->param['figureurl_qq_2'];
            $info['nickname'] = $this->param['nickname'];
            if(!empty($avater)){
                $upload = \app\library\UploadHelper::getInstance();
                $result = $upload->download_image($avater);
                if($result['errcode'] >= 0){
                    $info['avater'] = $result['content']['name'];
                }
            }
        }catch(\Exception $e){
        }
        
        return $info;
    }
    
    //  验证注册方式(non-PHPdoc)
    
    protected function checkParamValidate(){
        if(empty($this->param['openid']) && empty($this->param['code'])){
            return ['errcode' => -101, 'message' => '请传入QQ凭证'];
        }
        return ['errcode' => 0, 'message' => '验证成功'];
    }
    // 注册
    protected function checkParamBeforeRegister(){
        $res = array('errcode' => 0, 'message' => '成功');
        $phone = isset($this->param['phone']) ? $this->param['phone'] : '';
        $passwd = isset($this->param['passwd']) ? $this->param['passwd'] : '';
        $verify_code = $this->param['verify_code'];
        $confirm_passwd = $this->param['confirm_passwd'];
        
        if(!$phone){
            $res['message'] = "请输入手机号";
            $res['errcode'] = -401;
            return $res;
        }
        if(!preg_match("/^1\d{10}$/", $phone)){
            $res['message'] = "手机号格式错误";
            $res['errcode'] = -402;
            return $res;
        }
        
        if(!$passwd){
            $res['message'] = "请输入密码";
            $res['errcode'] = -405;
            return $res;
        }
        
        if ($passwd != $confirm_passwd){
            return ['errcode' => -101, 'message' => '密码不一致'];
        }
        
        if(empty($verify_code)){
            $res['message'] = "请传入验证码";
            $res['errcode'] = -407;
            return $res;
        }
        
        $condition = array('phone' => $phone,'_string' => 'qq_open_id is not null');
        $count = M("customer")->where($condition)->count();
        if($count > 0){
            $res['message'] = "手机号已被使用";
            $res['errcode'] = -403;
            return $res;
        }
        
        $condition = array('phone' => $phone,'_string' => "qq_open_id is null");
        $customer = M("customer")->field("customer_id,passwd")->where($condition)->find();
        if(isset($customer['customer_id'])){
            $res = D("verify_code_model")->verify($phone, $verify_code,3);
            if($res['errcode'] < 0){
                return $res;
            }
            if($customer['passwd'] != $passwd){
                return array('errcode' => -103, 'message' => "手机号已注册，密码错误");
            }
            return array('errcode' => 0, 'message' => '成功', 'is_update' => true, 'customer_id' => $customer['customer_id']);
        }else {
            $res = D("verify_code_model")->verify($phone, $verify_code,3);
            if($res['errcode'] < 0){
                return $res;
            }
        }
        return array('errcode' => 0, 'message' => '成功');
    }
    // 登录
    protected function checkParamBeforeLogin(){
        return array('errcode' => 0, 'message' => '成功');
    }
    
    protected function checkEmptyParams(){
        return ['code','openid','agent_id'];
    }
    
}