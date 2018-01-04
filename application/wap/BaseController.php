<?php
namespace app\wap;
use think\Controller;
use app\library\JSSDK;
use think\Cache;
use org\oauth\driver\Weixin;
use app\library\message\Message;
class BaseController extends Controller
{
	protected $customer_id = "";
	
	protected $host = "";
	
	protected $skip_weixin = false;
	
	protected $access_token = "";
    public function __construct(){
    	parent::__construct();
    	
        $signPackage = [
        		'appId'=> '',
        		'timestamp' => 0,
        		'nonceStr' => '',
        		'signature' => ''
        ];
        $site = \app\library\SettingHelper::get("bear_close", ['is_open' => 1,'reason' => '']);
        
        if($site['is_open'] == 0){
        	$this->assign("reason", $site['reason']);
        	$this->assign("title", "站点关闭");
        	$this->display('normal/close');
        	exit;
        }
        
       	
        $access_token = I("access_token");
        
        $openid = cookie("openid");
		if(empty($access_token)){
			$access_token = cookie("access_token");
		}else{
			setcookie("access_token", $access_token,  time() + 365 * 24 * 60 * 60, "/");
		}
		$customer_id = get_customer_id($access_token);
        $res = [];
        
        //微信参数配置
        $config = \app\library\SettingHelper::get("bear_weixin_gz_config",[]);
        
        $logout = cookie("force_logout");
        
        $login = I("weixin_login") || !$logout;
        if(is_weixin() && !empty($config) && isset($config['appid'])){
        	if(!$this->skip_weixin && $login){
        		if( empty($customer_id)){
        			$code = I("code");
        			$unionid = I("unionid");
        			if(empty($openid)){
        				$openid = I("openid");
        			}
        			$customer = "";
        			if(!empty($openid)){
        				$customer = M("customer")->where(['wx_gz_openid' => $openid])->field("customer_id, access_token")->find();
        				if(empty($customer) && !empty($unionid)){
        					$customer = M("customer")->where(['wx_unionid' => $unionid])->field("customer_id, access_token")->find();
        				}
        			}
        		
        			if(empty($customer) && empty($code)){
        				setcookie("force_logout", "0",  time() - 1, "/");
        				$curr = get_current_url();
        				$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$config['appid']."&redirect_uri=".urlencode($curr)."&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
        				$this->redirect($url);
        				exit;
        			}
        				$oauth = new \app\oauth\WeixinGzOauth(["openid" => $openid, 'unionid' => $unionid,'code' => $code]);
        				$result = $oauth->loginOrRegsiter();
        				if($result['errcode'] == 0){
        					$access_token = $result['content']['access_token'];
        					setcookie("access_token",$access_token, time() + 365 * 24 * 60 * 60, "/");
        					$customer_id = S($access_token);
        				}else{
        					setcookie("force_logout", 1,time() + 365 * 24 * 60 * 60, "/" );
        					$this->error($result['message'], U("home/index"));
        				}
        		}
        	}else if(empty($openid)){
        		if(I("code")){
        			$weixin_config = array(
        					'app_key' => $config['appid'],
        					'app_secret' => $config['app_secret'],
        					'authorize' => 'authorization_code'
        			);
        			$weixin = new Weixin($weixin_config);
        			$token = $weixin->getAccessToken(I("code"));
        			if(!empty($token) && isset($token['openid'])){
        				setcookie("openid", $token['openid'], time() + 60 * 60 * 24 * 365 , "/");
        				if(isset($token['unionid'])){
        					setcookie("unionid", $token['unionid'], time() + 60 * 60 * 24 * 365 , "/");
        				}
        			}
        		}else {
        			$curr = get_current_url();
        			$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$config['appid']."&redirect_uri=".urlencode($curr)."&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
        			$this->redirect($url);
        			die();
        		}
        	}
        	
        	$JSSDK = new JSSDK($config['appid'],$config['app_secret']);
        	try{
        		$signPackage = $JSSDK->getSignPackage();
        	}catch(\Exception $e){
        	}
        }
        
    	if(!empty($customer_id)){
        	$time = Cache::get("yydb_login_time_customer_". $customer_id );
        	Cache::set("yydb_login_time_customer_". $customer_id, time(),2 * 60 * 60);
        	if(empty($time)){
        		$time = M("customer")->where(['customer_id' => $customer_id])->getField("last_check_date");
        	}
        	$ignore = isset($_COOKIE['ignore_improve_' . $customer_id]);
        	$has_phone = cookie("has_phone_" . $customer_id);
        	if(!$ignore ){
        		$phone =  M("customer")->where(["customer_id" => $customer_id])->getField("phone");
        		if(empty($phone) && ACTION_NAME != "improve_data"){
        			$this->redirect(U('user/improve_data'));
        			die();
        		}elseif (!empty($phone)){
        			setcookie("has_phone_" . $customer_id, "1", time() + 365 * 24 * 60 * 60 , "/");
        		}
        	}
        	
        	
        	$today = strtotime(date("Y-m-d")); 
        	if($today > $time){
        		/*$is_open = \app\library\SettingHelper::get("carousel_condition");
        		$num = \app\library\SettingHelper::get("day_gift");
        		$data = ['last_check_date' => time()];
        		if($num > 0 && $is_open == 1){
        		}
        		M("customer")->where(['customer_id' => $customer_id])->save($data);
        		*/
        	}
        }
        
        
        
        $this->customer_id = $customer_id;
        $this->access_token = $access_token;
        $title = \app\library\SettingHelper::get("bear_title", "网站标题");
        //新增
        $keywords = \app\library\SettingHelper::get("bear_keywords", "网站关键字");
        $description = \app\library\SettingHelper::get("bear_description", "网站描述"); 
        $bear_copyright = \app\library\SettingHelper::get("bear_copyright", "站点版权信息");
        $bear_record = \app\library\SettingHelper::get("bear_record", "站点备案号");         

        $share = \app\library\SettingHelper::get("wx_share", 
                [
                'share_image'=> "/public/share_logo.png",
                'share_title' => $title,
                'share_desc' => '描述'
        ]);
        $uuid = 0;
        if($customer_id > 0){
            $uuid = M("customer")->where(['customer_id'=>$customer_id])->getField("uuid");
        }
        
        $host = \app\library\SettingHelper::get("bear_image_url");
        
        $this->host = $host;
        $this->assign("goods_number" , 0);
        $this->assign("title", $title);

        //新增
        $this->assign("keywords", $keywords);
        $this->assign("description", $description);
        $this->assign("bear_copyright", $bear_copyright);
        $this->assign("bear_record", $bear_record);         

        $this->assign("tips_time_out", 3);
        $this->assign("signPackage", $signPackage);
        $this->assign("app_url",  "http://" .get_domain() . "/wap/home/index?uuid=" .$uuid);
        $this->assign("app_image", $host .$share['share_image']);
        $this->assign("wap_url", "/wap");
        $this->assign("app_title", $share['share_title']);
        $this->assign("app_desc", $share['share_desc']);
    }
    

}