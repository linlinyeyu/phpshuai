<?php
namespace app\admin\controller;
use app\admin\Admin;
class Verify extends Admin
{
	public function index()
	{
	    	$length=4;
	    	if (isset($_GET['length']) && intval($_GET['length'])){
	    		$length = intval($_GET['length']);
	    	}
	    	
	    	//设置验证码字符库
	    	$code_set="";
	    	if(isset($_GET['charset'])){
	    		$code_set= trim($_GET['charset']);
	    	}
	    	
	    	$use_noise=1;
	    	if(isset($_GET['use_noise'])){
	    		$use_noise= intval($_GET['use_noise']);
	    	}
	    	
	    	$use_curve=1;
	    	if(isset($_GET['use_curve'])){
	    		$use_curve= intval($_GET['use_curve']);
	    	}
	    	
	    	$font_size=25;
	    	if (isset($_GET['font_size']) && intval($_GET['font_size'])){
	    		$font_size = intval($_GET['font_size']);
	    	}
	    	
	    	$width=0;
	    	if (isset($_GET['width']) && intval($_GET['width'])){
	    		$width = intval($_GET['width']);
	    	}
	    	
	    	$height=0;
	    		
	    	if (isset($_GET['height']) && intval($_GET['height'])){
	    		$height = intval($_GET['height']);
	    	}
	    	
	    	/* $background="";
	    	if (isset($_GET['background']) && trim(urldecode($_GET['background'])) && preg_match('/(^#[a-z0-9]{6}$)/im', trim(urldecode($_GET['background'])))){
	    		$background=trim(urldecode($_GET['background']));
	    	} */
	    	//TODO ADD Backgroud param!
	    	
	    	$config = array(
		        'codeSet'   =>  !empty($code_set)?$code_set:"2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY",             // 验证码字符集合
		        'expire'    =>  1800,            // 验证码过期时间（s）
		        'useImgBg'  =>  false,           // 使用背景图片 
		        'fontSize'  =>  !empty($font_size)?$font_size:25,              // 验证码字体大小(px)
		        'useCurve'  =>  $use_curve===0?false:true,           // 是否画混淆曲线
		        'useNoise'  =>  $use_noise===0?false:true,            // 是否添加杂点	
		        'imageH'    =>  $height,               // 验证码图片高度
		        'imageW'    =>  $width,               // 验证码图片宽度
		        'length'    =>  !empty($length)?$length:4,               // 验证码位数
		        'bg'        =>  array(243, 251, 254),  // 背景颜色
		        'reset'     =>  true,           // 验证成功后是否重置
	    	);
	    	$Verify = new \org\Verify($config);
	    	$Verify->entry();
	}

    public function getTask(){
        $json = array();
        if(session('?'.C('USER_AUTH_KEY'))){
            $json= array('status'=>1);
        }else{
        		if(C('session.expire')){
	            $expire = C('session.expire');
	        	}else{
	            $expire = ini_get("session.gc_maxlifetime");
	        	}
            $json = array('status'=>0,'msg'=>'会话过期时间已超过 '.($expire/60).' 分钟');
        }
        $this->ajaxReturn($json);
    }


	private function genorder($now,$customer_id,$ip,$quantity)
	{
		$time = time();
       	$order = D("items_order")
        ->GetInitOrder($customer_id, $quantity,0,$time,$ip);
        $order['ip'] = $ip;
		$order['ip_area'] = get_location($ip);
 
        
        $order_number = $order['order_number'];
        $id = D("items_order")->add($order);
        $order_goods = [
        	'item_id' => $now['item_id'],
        	'quantity' => $quantity,
        	'order_id' => $id,
        	'order_number' => $order_number
        ];
        	M("order_goods")->add($order_goods);
        	D("items")->buy($order_number, $quantity);
	}
}