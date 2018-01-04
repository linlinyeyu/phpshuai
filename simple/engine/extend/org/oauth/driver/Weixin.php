<?php
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: oliverxu <471066925@qq.com>
// +----------------------------------------------------------------------

namespace org\oauth\driver;

use org\oauth\Driver;

class Weixin extends Driver
{
    /**
     * 获取requestCode的api接口
     * @var string
     */
    protected $getRequestCodeURL = 'https://api.weixin.qq.com/sns/oauth2';

    /**
     * 获取access_token的api接口
     * @var string
     */
    protected $getAccessTokenURL = 'https://api.weixin.qq.com/sns/oauth2/access_token';

    /**
     * API根路径
     * @var string
     */
    protected $apiBase = 'https://api.weixin.qq.com/';

    /**
     * 组装接口调用参数 并调用接口
     * @param  string $api    微博API
     * @param  string $param  调用API的额外参数
     * @param  string $method HTTP请求方法 默认为GET
     * @return json
     */
    protected function call($api, $param = '', $method = 'GET', $multi = false)
    {
        /* Weixin调用公共参数 */
        $params = [
            'appid' => $this->appKey,
            'secret' => $this->appSecret,
            'access_token'       => $this->token['access_token'],
            'openid'             => $this->getOpenId(),
        	'lang' => 'zh_CN'
        ];
        $data = $this->http($this->url($api), $this->param($params, $param), $method);
        return json_decode($data, true);
    }

    public function getAccessToken($code)
    {
        $params = [
            'appid'     => $this->appKey,
            'secret' => $this->appSecret,
            'grant_type'    => $this->grantType,
            'code'          => $code,
        ];
        $data = $this->http($this->getAccessTokenURL, $params);
        // 解析token
        $this->token = $this->parseToken($data);
        return $this->token;
    }
    /**
     * 解析access_token方法请求后的返回值
     * @param string $result 获取access_token的方法的返回值
     */
    protected function parseToken($result)
    {
        $data = json_decode($result,true);
 
        if (isset($data['access_token']) && isset($data['expires_in'])) {
          //  $data['openid'] = $this->getOpenId();
            return $data;
        } else {
            return $result;
        }

    }

    /**
     * 获取当前授权应用的openid
     * @return string
     */
    public function getOpenId()
    {
        if (isset($this->token['openid']) && !empty($this->token['openid'])) {
            return $this->token['openid'];
        }
        return null;
    }

    /**
     * 获取当前授权应用的openid
     * @return string
     */
    public function getUnionId()
    {
        if (isset($this->token['unionid']) && !empty($this->token['unionid'])) {
            return $this->token['unionid'];
        }
        return null;
    }

    public function getOauthInfo($sub = false)
    {
    	$url = $sub ? "cgi-bin/user/info" :'sns/userinfo';
        $data = $this->call($url);
        if (!isset($data['errcode'])) {
            return $data;
        } else {
            return null;
        }
    }
}
