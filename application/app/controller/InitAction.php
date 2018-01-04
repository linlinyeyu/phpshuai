<?php
namespace app\app\controller;
use think\Cache;
use app\library\message\Message;
use app\library\ActionTransferHelper;
class InitAction {
	//专题商品
	public function getActivity(){
		$terminal = I("t");
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$activity = S("shuaibo_home_activity_".$terminal);
		if (empty($activity)){
			$home_activity = D("init_model")->getAppHomeActivity();
			$temp = [];
			$i = 0;
			if (!empty($home_activity)){
				foreach ($home_activity as $v){
					$temp[$v['special_id']] = $v;
				}
				foreach ($temp as $special_id => $value){
					$img = $value['img'];
					$condition = "s.type = ".$value['type']." and s.status = 1 and aha.status = 1 and g.is_delete = 0 and g.on_sale = 1 and g.apply_status = 2";
					$fields = "g.goods_id,g.name,concat('$host',IFNULL(ahag.image,g.cover)) as cover,(g.sale_count+g.virtual_count) as sale_count,g.shop_price";
					$activity[$i]['goods'] = D("goods_model")->getAppHomeActivityGoodsList($condition,$fields,"ahag.sort ASC",1,6);
					$activity[$i]['img'] = array("img" => $img,'jump' => $terminal == 1?$value['ios_param']:$value['android_param'],'params'=>[["key" => 'type',"value" => $value['action_id']],["key" => 'nav_title',"value" => $value['nav_title']]]);
					$activity[$i]['type'] = $value['type'];
					$i++;
				}
			}
			S("shuaibo_home_activity_".$terminal,$activity);
		}
		if (empty($activity)) {
		    $activity = [];
        }
		return ['errcode' => 0,'message' => '请求成功','content' => $activity];
	}
	
	//关键词列表
	public function getKeyWord(){
		$keyword = S("shuaibo_keyword");
		if (empty($keyword)){
			$keyword = D("init_model")->getKeywordList();
			S("shuaibo_keyword",$keyword);
		}
		return ['errcode' => 0,'message' => '请求成功','content' => $keyword];
	}
	
	//猜你喜欢
	public function guessLike(){
		$customer_id = get_customer_id();
		if (!empty($customer_id)){
			//足迹
			$trail = D("init_model")->getTrail($customer_id);
			if (empty($trail)){
				$goods = D("goods_model")->getDefaultLike();
				return ['errcode' => 0,'message' => '请求成功','content' => $goods];
			}
			$goods = D("goods_model")->getInitLike($trail);
			return ['errcode' => 0,'message' => '请求成功','content' => $goods];
		}
		$goods = D("goods_model")->getDefaultLike();
		
		return ['errcode' => 0,'message' => '请求成功','content' => $goods];
	}
	
	//每日上新
	public function getNewGoods(){
        $terminal = I("t");
        $new_goods = D("init_model")->getEveryNewGoods($terminal);

        return ['errcode' => 0,'message' => '请求成功','content' => $new_goods];
	}
	
	//首页轮播图
	public function getBanner(){
		$position = I("position");
		$terminal = I("t");
		$banners = D("banner_model")->getBanners($position,$terminal);
		
		return ['errcode' => 0,'message' => '请求成功','content' => $banners];
	}
	//首页标签
	public function getTag(){
		$position = I("position");		
		$terminal = I("t");
		$tags = D("home_tag_model")->GetTags($terminal,1,$position);
		
		return ['errcode' => 0,'message' => '请求成功','content'=>$tags];
	}
	
	/**
	 * 获取七牛的配置信息，传递给客户端
	 * */
	public function getUploadConfig(){
		$config = \app\library\SettingHelper::get("bear_qiniu",['is_open' => 0]);
		
		$config = $config['driverConfig'];
	
		$qiniu = new \org\upload\driver\qiniu\QiniuStorage($config);
	
		$token = $qiniu->getUploadToken();
	
		$config['zone'] = 0;
		$config['token'] = $token;
		$config = array_merge($config,$token);
		unset($config['secretKey']);
		unset($config['accessKey']);
		return ['errcode' => 0 ,'message' => '请求成功', 'content' => $config ];
	}

	public function getBaseData() {
        $res = array(
            'message' => '成功',
            'errcode' => 0
        );
        $content = [];
        $protocol = \app\library\SettingHelper::get("shuaibo_protocol",['protocol' => 'http://www.baidu.com']);
        $seller_info = \app\library\SettingHelper::get("shuaibo_seller_info",['address' => '杭州市' ,'qq' => '123456789']);
        $content['protocol'] = $protocol['protocol'];
        $content['kf_qq'] = $seller_info['qq'];
        $res['content'] = $content;
        return $res;
    }
	
/**
 * 获取基础数据
 */
	/*
	public function GetBaseData()
	{
		$res = array(
				'message' => '错误信息',
				'errcode' => 0
		);
		$content = array();

		$customer_id = get_customer_id();
// 	判断并保存用户信息	
		if($customer_id){
			$userinfo = D("customer")->GetUserInfo($customer_id);
			if($userinfo){
				D("customer")->where(['customer_id' => $customer_id])->save(['last_ip' => get_client_ip(),'date_upd' => time()]);
				$userinfo['cart_num'] = D("customer")->CartNumber($customer_id);
				$content['customer'] = $userinfo;
			}
			$time = Cache::get("yydb_login_time_customer_". $customer_id );
			Cache::set("yydb_login_time_customer_". $customer_id, time(),2 * 60 * 60);
			if(empty($time)){
				$time = M("customer")->where(['customer_id' => $customer_id])->getField("last_check_date");
			}
			$today = strtotime(date("Y-m-d"));
			if($today > $time){
				
			}
		}else{
			$res['errcode'] = 99;
		}
		
		$host = \app\library\SettingHelper::get("bear_image_url");
		$content['withdraw'] = \app\library\SettingHelper::get("bear_commission_withdraw",['is_open' => 1, 'weixin' => 1, 'min_withdraw' => 100 , 'min_audit' => 500 , 'min_date' => 7 ]);
		$seller_info = \app\library\SettingHelper::get("shuaibo_seller_info",['address' => '杭州市' ,'service_phone' => '123456789']);
		$content['seller_info'] = $seller_info;
		$content['wap_url'] = "http://" . get_domain() . "/wap/";
		$jpush = \app\library\SettingHelper::get("bear_jpush",['is_open' => 0, 'app_key' => '']);
		$content['jpush_key'] = $jpush['app_key'];
		$content['test'] = 0;
		$start_page = \app\library\SettingHelper::get("bear_start_page", ['is_open' => 0 , 'start_page' => '']);
		if($start_page['is_open'] == 1){
			$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(800, 600);
			$content['start_page'] = $host . $start_page['start_page'] . $suffix;
		}
		
		$config = \app\library\SettingHelper::get("bear_qiniu",['is_open' => 0]);
		if($config['is_open'] == 1){
			$config = $config['driverConfig'];
			$qiniu = new \org\upload\driver\qiniu\QiniuStorage($config);
			$token = $qiniu->getUploadToken();
			$config['zone'] = 0;
			$config['token'] = $token;
			$config = array_merge($config,$token);
			unset($config['secretKey']);
			unset($config['accessKey']);
			$content['upload'] = $config;
			
		}
		
		
		
		$res['content'] = $content;
		return $res;
	}
	*/
/**
 * 获取首页数据
 */
	public function GetHomeData(){
		$terminal = (int)I("t");
		$position_id = (int)I("position_id");
		$banners = D("banner_model")->getBanners($position_id,$terminal);
		$tags = D("home_tag_model")->GetTags($terminal);
		$realize_more = [];
		$ads = D("init_model")->getHomeInfo();
		foreach ($tags as $key => $tag){
			if($tag['name'] == '了解更多'){
				$realize_more = $tag;
				unset($tags[$key]);
				break;
			}
		}
		return ['errcode' => 0 , 'message' =>'请求成功', 'content' => ['banners' => $banners, 'tags' => $tags, 'realize_more' => $realize_more, 'ads' => $ads]];
	}
	
	public function generate_qrcode(){
		$customer_id = I("customer_id");
		$res = D("user")->qrcode($customer_id);
		return $res;
	}
	
	public function pay(){
		$order_sn = "PO20170115212215424877";
		 
		$start_time = microtime_float();
		$notify = new \app\library\order\OrderNotify($order_sn, 0.66, 7);
		$notify->notify();
		echo microtime_float() - $start_time;
	}
/**
 * 测试
 */
public function test(){

    return;

    $data = [
        'userid' => '55239898',
        'orderid' => 'SH20170719153344815850',
        'ordermoney' => 120,
        'orderpv' => 120,
        'orderdate' => date("Y-m-d H:i:s",time()),
        'name' => '测试',
        'mobiletele' => '18712345678',
        'country' => '中国',
        'province' => '浙江省',
        'city' => '杭州市',
        'xian' => '滨江区',
        'address' => '测试地址'
    ];
    $api = new \app\api\LGApi();
    $res = $api->AddOrderInfo($data);

    return $res;

    $notify = new \app\library\order\OrderNotify("PO20170610105241644674", 0.01, 2);

    $res = $notify->notify();

    var_dump($res);

    return;
	
	$client = \app\library\chat\factory\ChatClient::getInstance();
	
	$response = $client->userManager()->createMember(['username' => '4234234', 'password' => '111111']);
	
	return $response;
	/*$TEST = \app\library\SettingHelper::get_pay_params(2);
	return $TEST;*/
	
	return $test;
	/*$ids = 'a:4:{i:0;a:2:{s:7:"orderid";s:3:"802";s:5:"level";i:1;}i:1;a:2:{s:7:"orderid";s:3:"819";s:5:"level";i:1;}i:2;a:2:{s:7:"orderid";s:3:"805";s:5:"level";i:2;}i:3;a:2:{s:7:"orderid";s:3:"816";s:5:"level";i:2;}},a:16:{i:0;a:2:{s:7:"orderid";s:3:"844";s:5:"level";i:1;}i:1;a:2:{s:7:"orderid";s:3:"868";s:5:"level";i:1;}i:2;a:2:{s:7:"orderid";s:3:"899";s:5:"level";i:1;}i:3;a:2:{s:7:"orderid";s:3:"900";s:5:"level";i:1;}i:4;a:2:{s:7:"orderid";s:3:"821";s:5:"level";i:2;}i:5;a:2:{s:7:"orderid";s:3:"823";s:5:"level";i:2;}i:6;a:2:{s:7:"orderid";s:3:"828";s:5:"level";i:2;}i:7;a:2:{s:7:"orderid";s:3:"882";s:5:"level";i:2;}i:8;a:2:{s:7:"orderid";s:3:"888";s:5:"level";i:2;}i:9;a:2:{s:7:"orderid";s:3:"896";s:5:"level";i:2;}i:10;a:2:{s:7:"orderid";s:3:"901";s:5:"level";i:2;}i:11;a:2:{s:7:"orderid";s:3:"940";s:5:"level";i:2;}i:12;a:2:{s:7:"orderid";s:3:"869";s:5:"level";i:3;}i:13;a:2:{s:7:"orderid";s:3:"938";s:5:"level";i:3;}i:14;a:2:{s:7:"orderid";s:3:"950";s:5:"level";i:3;}i:15;a:2:{s:7:"orderid";s:3:"951";s:5:"level";i:3;}},a:10:{i:0;a:2:{s:7:"orderid";s:3:"966";s:5:"level";i:1;}i:1;a:2:{s:7:"orderid";s:4:"1007";s:5:"level";i:1;}i:2;a:2:{s:7:"orderid";s:4:"1062";s:5:"level";i:1;}i:3;a:2:{s:7:"orderid";s:4:"1063";s:5:"level";i:1;}i:4;a:2:{s:7:"orderid";s:3:"980";s:5:"level";i:2;}i:5;a:2:{s:7:"orderid";s:4:"1010";s:5:"level";i:2;}i:6;a:2:{s:7:"orderid";s:4:"1017";s:5:"level";i:2;}i:7;a:2:{s:7:"orderid";s:4:"1036";s:5:"level";i:3;}i:8;a:2:{s:7:"orderid";s:4:"1111";s:5:"level";i:3;}i:9;a:2:{s:7:"orderid";s:4:"1131";s:5:"level";i:3;}},a:15:{i:0;a:2:{s:7:"orderid";s:4:"1145";s:5:"level";i:1;}i:1;a:2:{s:7:"orderid";s:4:"1183";s:5:"level";i:1;}i:2;a:2:{s:7:"orderid";s:4:"1188";s:5:"level";i:1;}i:3;a:2:{s:7:"orderid";s:4:"1189";s:5:"level";i:1;}i:4;a:2:{s:7:"orderid";s:4:"1190";s:5:"level";i:1;}i:5;a:2:{s:7:"orderid";s:4:"1202";s:5:"level";i:1;}i:6;a:2:{s:7:"orderid";s:4:"1335";s:5:"level";i:1;}i:7;a:2:{s:7:"orderid";s:4:"1185";s:5:"level";i:2;}i:8;a:2:{s:7:"orderid";s:4:"1193";s:5:"level";i:2;}i:9;a:2:{s:7:"orderid";s:4:"1245";s:5:"level";i:2;}i:10;a:2:{s:7:"orderid";s:4:"1334";s:5:"level";i:2;}i:11;a:2:{s:7:"orderid";s:4:"1337";s:5:"level";i:2;}i:12;a:2:{s:7:"orderid";s:4:"1341";s:5:"level";i:2;}i:13;a:2:{s:7:"orderid";s:4:"1280";s:5:"level";i:3;}i:14;a:2:{s:7:"orderid";s:4:"1329";s:5:"level";i:3;}},a:20:{i:0;a:2:{s:7:"orderid";s:4:"1413";s:5:"level";i:1;}i:1;a:2:{s:7:"orderid";s:4:"1422";s:5:"level";i:1;}i:2;a:2:{s:7:"orderid";s:4:"1434";s:5:"level";i:1;}i:3;a:2:{s:7:"orderid";s:4:"1438";s:5:"level";i:1;}i:4;a:2:{s:7:"orderid";s:4:"1479";s:5:"level";i:1;}i:5;a:2:{s:7:"orderid";s:4:"1537";s:5:"level";i:1;}i:6;a:2:{s:7:"orderid";s:4:"1419";s:5:"level";i:2;}i:7;a:2:{s:7:"orderid";s:4:"1420";s:5:"level";i:2;}i:8;a:2:{s:7:"orderid";s:4:"1433";s:5:"level";i:2;}i:9;a:2:{s:7:"orderid";s:4:"1436";s:5:"level";i:2;}i:10;a:2:{s:7:"orderid";s:4:"1437";s:5:"level";i:2;}i:11;a:2:{s:7:"orderid";s:4:"1447";s:5:"level";i:2;}i:12;a:2:{s:7:"orderid";s:4:"1478";s:5:"level";i:2;}i:13;a:2:{s:7:"orderid";s:4:"1488";s:5:"level";i:2;}i:14;a:2:{s:7:"orderid";s:4:"1499";s:5:"level";i:2;}i:15;a:2:{s:7:"orderid";s:4:"1522";s:5:"level";i:2;}i:16;a:2:{s:7:"orderid";s:4:"1538";s:5:"level";i:2;}i:17;a:2:{s:7:"orderid";s:4:"1550";s:5:"level";i:2;}i:18;a:2:{s:7:"orderid";s:4:"1406";s:5:"level";i:3;}i:19;a:2:{s:7:"orderid";s:4:"1435";s:5:"level";i:3;}},a:18:{i:0;a:2:{s:7:"orderid";s:4:"1556";s:5:"level";i:1;}i:1;a:2:{s:7:"orderid";s:4:"1591";s:5:"level";i:1;}i:2;a:2:{s:7:"orderid";s:4:"1608";s:5:"level";i:1;}i:3;a:2:{s:7:"orderid";s:4:"1637";s:5:"level";i:1;}i:4;a:2:{s:7:"orderid";s:4:"1665";s:5:"level";i:1;}i:5;a:2:{s:7:"orderid";s:4:"1677";s:5:"level";i:1;}i:6;a:2:{s:7:"orderid";s:4:"1586";s:5:"level";i:2;}i:7;a:2:{s:7:"orderid";s:4:"1587";s:5:"level";i:2;}i:8;a:2:{s:7:"orderid";s:4:"1589";s:5:"level";i:2;}i:9;a:2:{s:7:"orderid";s:4:"1634";s:5:"level";i:2;}i:10;a:2:{s:7:"orderid";s:4:"1641";s:5:"level";i:2;}i:11;a:2:{s:7:"orderid";s:4:"1650";s:5:"level";i:2;}i:12;a:2:{s:7:"orderid";s:4:"1653";s:5:"level";i:2;}i:13;a:2:{s:7:"orderid";s:4:"1675";s:5:"level";i:2;}i:14;a:2:{s:7:"orderid";s:4:"1678";s:5:"level";i:2;}i:15;a:2:{s:7:"orderid";s:4:"1555";s:5:"level";i:3;}i:16;a:2:{s:7:"orderid";s:4:"1605";s:5:"level";i:3;}i:17;a:2:{s:7:"orderid";s:4:"1663";s:5:"level";i:3;}},a:22:{i:0;a:2:{s:7:"orderid";s:4:"1805";s:5:"level";i:1;}i:1;a:2:{s:7:"orderid";s:4:"1855";s:5:"level";i:1;}i:2;a:2:{s:7:"orderid";s:4:"1693";s:5:"level";i:2;}i:3;a:2:{s:7:"orderid";s:4:"1734";s:5:"level";i:2;}i:4;a:2:{s:7:"orderid";s:4:"1740";s:5:"level";i:2;}i:5;a:2:{s:7:"orderid";s:4:"1741";s:5:"level";i:2;}i:6;a:2:{s:7:"orderid";s:4:"1812";s:5:"level";i:2;}i:7;a:2:{s:7:"orderid";s:4:"1860";s:5:"level";i:2;}i:8;a:2:{s:7:"orderid";s:4:"1879";s:5:"level";i:2;}i:9;a:2:{s:7:"orderid";s:4:"1883";s:5:"level";i:2;}i:10;a:2:{s:7:"orderid";s:4:"1884";s:5:"level";i:2;}i:11;a:2:{s:7:"orderid";s:4:"1885";s:5:"level";i:2;}i:12;a:2:{s:7:"orderid";s:4:"1890";s:5:"level";i:2;}i:13;a:2:{s:7:"orderid";s:4:"1895";s:5:"level";i:2;}i:14;a:2:{s:7:"orderid";s:4:"1920";s:5:"level";i:2;}i:15;a:2:{s:7:"orderid";s:4:"1943";s:5:"level";i:2;}i:16;a:2:{s:7:"orderid";s:4:"1737";s:5:"level";i:3;}i:17;a:2:{s:7:"orderid";s:4:"1743";s:5:"level";i:3;}i:18;a:2:{s:7:"orderid";s:4:"1759";s:5:"level";i:3;}i:19;a:2:{s:7:"orderid";s:4:"1817";s:5:"level";i:3;}i:20;a:2:{s:7:"orderid";s:4:"1827";s:5:"level";i:3;}i:21;a:2:{s:7:"orderid";s:4:"1873";s:5:"level";i:3;}},a:2:{i:0;a:2:{s:7:"orderid";s:4:"1975";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:4:"2029";s:5:"level";i:2;}},a:2:{i:0;a:2:{s:7:"orderid";s:4:"2090";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:4:"2119";s:5:"level";i:2;}},a:1:{i:0;a:2:{s:7:"orderid";s:4:"2363";s:5:"level";i:1;}},a:4:{i:0;a:2:{s:7:"orderid";s:4:"2500";s:5:"level";i:1;}i:1;a:2:{s:7:"orderid";s:4:"2505";s:5:"level";i:2;}i:2;a:2:{s:7:"orderid";s:4:"2538";s:5:"level";i:2;}i:3;a:2:{s:7:"orderid";s:4:"2562";s:5:"level";i:3;}},a:5:{i:0;a:2:{s:7:"orderid";s:4:"2984";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:4:"3013";s:5:"level";i:2;}i:2;a:2:{s:7:"orderid";s:4:"3036";s:5:"level";i:2;}i:3;a:2:{s:7:"orderid";s:4:"2969";s:5:"level";i:3;}i:4;a:2:{s:7:"orderid";s:4:"3027";s:5:"level";i:3;}},a:4:{i:0;a:2:{s:7:"orderid";s:4:"3462";s:5:"level";i:1;}i:1;a:2:{s:7:"orderid";s:4:"3111";s:5:"level";i:3;}i:2;a:2:{s:7:"orderid";s:4:"3301";s:5:"level";i:3;}i:3;a:2:{s:7:"orderid";s:4:"3399";s:5:"level";i:3;}},a:3:{i:0;a:2:{s:7:"orderid";s:4:"3602";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:4:"3592";s:5:"level";i:3;}i:2;a:2:{s:7:"orderid";s:4:"3677";s:5:"level";i:3;}},a:2:{i:0;a:2:{s:7:"orderid";s:4:"3963";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:4:"3781";s:5:"level";i:3;}},a:2:{i:0;a:2:{s:7:"orderid";s:4:"5801";s:5:"level";i:1;}i:1;a:2:{s:7:"orderid";s:4:"5170";s:5:"level";i:3;}},a:1:{i:0;a:2:{s:7:"orderid";s:4:"6005";s:5:"level";i:1;}},a:3:{i:0;a:2:{s:7:"orderid";s:4:"7018";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:4:"7277";s:5:"level";i:2;}i:2;a:2:{s:7:"orderid";s:4:"7158";s:5:"level";i:3;}},a:3:{i:0;a:2:{s:7:"orderid";s:4:"7782";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:4:"7865";s:5:"level";i:2;}i:2;a:2:{s:7:"orderid";s:4:"8121";s:5:"level";i:3;}},a:2:{i:0;a:2:{s:7:"orderid";s:4:"8252";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:4:"8597";s:5:"level";i:2;}},a:2:{i:0;a:2:{s:7:"orderid";s:4:"8988";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:4:"8772";s:5:"level";i:3;}},a:2:{i:0;a:2:{s:7:"orderid";s:5:"10313";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:5:"11074";s:5:"level";i:3;}},a:3:{i:0;a:2:{s:7:"orderid";s:5:"12563";s:5:"level";i:3;}i:1;a:2:{s:7:"orderid";s:5:"13329";s:5:"level";i:3;}i:2;a:2:{s:7:"orderid";s:5:"13870";s:5:"level";i:3;}},a:2:{i:0;a:2:{s:7:"orderid";s:5:"14475";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:5:"14559";s:5:"level";i:2;}},a:2:{i:0;a:2:{s:7:"orderid";s:5:"15658";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:5:"15327";s:5:"level";i:3;}},a:2:{i:0;a:2:{s:7:"orderid";s:5:"16522";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:5:"16648";s:5:"level";i:3;}},a:3:{i:0;a:2:{s:7:"orderid";s:5:"17928";s:5:"level";i:1;}i:1;a:2:{s:7:"orderid";s:5:"17709";s:5:"level";i:2;}i:2;a:2:{s:7:"orderid";s:5:"17084";s:5:"level";i:3;}},a:2:{i:0;a:2:{s:7:"orderid";s:5:"18635";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:5:"18389";s:5:"level";i:3;}},a:3:{i:0;a:2:{s:7:"orderid";s:5:"22901";s:5:"level";i:3;}i:1;a:2:{s:7:"orderid";s:5:"22998";s:5:"level";i:3;}i:2;a:2:{s:7:"orderid";s:5:"23711";s:5:"level";i:3;}},a:2:{i:0;a:2:{s:7:"orderid";s:5:"26793";s:5:"level";i:1;}i:1;a:2:{s:7:"orderid";s:5:"24362";s:5:"level";i:2;}},a:2:{i:0;a:2:{s:7:"orderid";s:5:"27641";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:5:"27810";s:5:"level";i:2;}},a:3:{i:0;a:2:{s:7:"orderid";s:5:"28744";s:5:"level";i:1;}i:1;a:2:{s:7:"orderid";s:5:"28745";s:5:"level";i:2;}i:2;a:2:{s:7:"orderid";s:5:"28961";s:5:"level";i:3;}},a:2:{i:0;a:2:{s:7:"orderid";s:5:"29347";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:5:"31308";s:5:"level";i:3;}},a:4:{i:0;a:2:{s:7:"orderid";s:5:"40791";s:5:"level";i:3;}i:1;a:2:{s:7:"orderid";s:5:"44214";s:5:"level";i:3;}i:2;a:2:{s:7:"orderid";s:5:"45766";s:5:"level";i:3;}i:3;a:2:{s:7:"orderid";s:5:"50024";s:5:"level";i:3;}},a:2:{i:0;a:2:{s:7:"orderid";s:5:"55901";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:5:"69863";s:5:"level";i:3;}},a:1:{i:0;a:2:{s:7:"orderid";s:5:"93447";s:5:"level";i:1;}},a:2:{i:0;a:2:{s:7:"orderid";s:6:"103572";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:6:"108532";s:5:"level";i:2;}},a:2:{i:0;a:2:{s:7:"orderid";s:6:"125141";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:6:"126267";s:5:"level";i:3;}},a:3:{i:0;a:2:{s:7:"orderid";s:6:"130689";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:6:"129455";s:5:"level";i:3;}i:2;a:2:{s:7:"orderid";s:6:"132035";s:5:"level";i:3;}},a:2:{i:0;a:2:{s:7:"orderid";s:6:"132764";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:6:"133417";s:5:"level";i:2;}},a:3:{i:0;a:2:{s:7:"orderid";s:6:"134741";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:6:"133618";s:5:"level";i:3;}i:2;a:2:{s:7:"orderid";s:6:"134125";s:5:"level";i:3;}},a:2:{i:0;a:2:{s:7:"orderid";s:6:"136826";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:6:"138211";s:5:"level";i:2;}},a:2:{i:0;a:2:{s:7:"orderid";s:6:"144720";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:6:"139450";s:5:"level";i:3;}},a:4:{i:0;a:2:{s:7:"orderid";s:6:"149640";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:6:"149860";s:5:"level";i:2;}i:2;a:2:{s:7:"orderid";s:6:"149974";s:5:"level";i:2;}i:3;a:2:{s:7:"orderid";s:6:"149961";s:5:"level";i:3;}},a:4:{i:0;a:2:{s:7:"orderid";s:6:"157496";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:6:"152337";s:5:"level";i:3;}i:2;a:2:{s:7:"orderid";s:6:"155823";s:5:"level";i:3;}i:3;a:2:{s:7:"orderid";s:6:"158728";s:5:"level";i:3;}},a:2:{i:0;a:2:{s:7:"orderid";s:6:"164044";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:6:"169611";s:5:"level";i:3;}},a:3:{i:0;a:2:{s:7:"orderid";s:6:"170661";s:5:"level";i:3;}i:1;a:2:{s:7:"orderid";s:6:"175722";s:5:"level";i:3;}i:2;a:2:{s:7:"orderid";s:6:"180521";s:5:"level";i:3;}},a:6:{i:0;a:2:{s:7:"orderid";s:6:"196896";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:6:"206439";s:5:"level";i:2;}i:2;a:2:{s:7:"orderid";s:6:"207123";s:5:"level";i:2;}i:3;a:2:{s:7:"orderid";s:6:"206468";s:5:"level";i:3;}i:4;a:2:{s:7:"orderid";s:6:"206479";s:5:"level";i:3;}i:5;a:2:{s:7:"orderid";s:6:"207049";s:5:"level";i:3;}},a:3:{i:0;a:2:{s:7:"orderid";s:6:"208271";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:6:"207958";s:5:"level";i:3;}i:2;a:2:{s:7:"orderid";s:6:"207983";s:5:"level";i:3;}},a:3:{i:0;a:2:{s:7:"orderid";s:6:"213806";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:6:"213098";s:5:"level";i:3;}i:2;a:2:{s:7:"orderid";s:6:"213554";s:5:"level";i:3;}},a:3:{i:0;a:2:{s:7:"orderid";s:6:"220157";s:5:"level";i:2;}i:1;a:2:{s:7:"orderid";s:6:"219059";s:5:"level";i:3;}i:2;a:2:{s:7:"orderid";s:6:"220945";s:5:"level";i:3;}}';
	$ids = explode(",", $ids);
	$tmp = [];
	
	$order1 = [];
	$order2 = [];
	$order3 = [];
	foreach ($ids as $i){
		$tt = unserialize($i);
		foreach ($tt as $t){
			if($t['level'] == 1){
				$order1[] = $t['orderid'];
			}elseif($t['level'] == 2){
				$order2[] = $t['orderid'];
			}else{
				$order3[] = $t['orderid'];
			}
		}
	}
	$order1_d = '805,816,828,888,896,901,940,1010,1017,1185,1193,1334,1337,1341,1419,1420,1433,1436,1437,1447,1478,1499,1586,1587,1589,1641,1675,1693,1740,1741,1812,1879,1883,1884,1885,1895,1920,1943,8988,29347,821,823,882,980,1245,1734,1890,1975,2029,7018,8597,16522,18635,1488,1634,1653,1860,2119,2505,7782,10313,7865,8252,2090,1522,1538,1550,1650,1678,2538,2984,3013,3036,3963,103572,108532,125141,130689,132764,133417,134741,136826,138211,144720,149640,149860,149974,157496,164044,196896,206439,207123,208271,213806,220157,3602,7277,14475,14559,15658,17709,24362,27641,27810,55901';
	$order1_d = explode(",", $order1_d);
	return array_diff($order2, $order1_d);*/
    //$content = "测试";
	//$content = \app\library\SmsHelper::sign($content);
	//return	$result = \app\library\SmsHelper::send("15957103422",$content);

	//\app\library\MessageHelper::sms(356, "测试一下");
// 	vendor("JPush.JPush");
// 	$client = new \JPush(C("jpush.app_key"), C("jpush.app_secret"));
// 	$result = $client->push()
// 	->setPlatform('ios')
// 	->addAlias('2163720')
// 	->setNotificationAlert('Hi, JPush')
// 	->send();
		
		$test = "<xml><appid><![CDATA[wx031e9aeed68bd4a9]]></appid>
<bank_type><![CDATA[CFT]]></bank_type>
<cash_fee><![CDATA[17]]></cash_fee>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[1427590502]]></mch_id>
<nonce_str><![CDATA[aV06fOFEY7pfSVlmpj8jkDIZpkGYcjKN]]></nonce_str>
<openid><![CDATA[oQYZtv4nGt_OMplBJ3QJq_gqAR8k]]></openid>
<out_trade_no><![CDATA[PO20170120111545822748]]></out_trade_no>
<result_code><![CDATA[SUCCESS]]></result_code>
<return_code><![CDATA[SUCCESS]]></return_code>
<sign><![CDATA[A318673FADD274F02EF77B21E28FA27E]]></sign>
<time_end><![CDATA[20170120111604]]></time_end>
<total_fee>17</total_fee>
<trade_type><![CDATA[NATIVE]]></trade_type>
<transaction_id><![CDATA[4006742001201701206938443384]]></transaction_id>
</xml>";
		
//$test = '{"payment_type":"1","subject":"\u8ba2\u5355\u53f7\uff1aPO20161207205749964482","trade_no":"2016120721001004580221286217","buyer_email":"yyq63728198@163.com","gmt_create":"2016-12-07 20:57:55","notify_type":"trade_status_sync","quantity":"1","out_trade_no":"PO20161207205749964482","seller_id":"2088421569613920","notify_time":"2016-12-07 20:57:56","body":"\u4f18\u989c\u4eae\u80a4\u8695\u4e1d\u9762\u819c\u7b49","trade_status":"TRADE_SUCCESS","is_total_fee_adjust":"N","total_fee":"0.10","gmt_payment":"2016-12-07 20:57:56","seller_email":"3520565497@qq.com","price":"0.10","buyer_id":"2088712906373583","notify_id":"e7cc824460bffb7fcc4d93a7c8e72bdkh6","use_coupon":"N","sign_type":"RSA","sign":"D+NHJWKz0M\/\/MU1V+SeSUbrT6xIa1jzVG8VCejbNWhtpUIRqOkEeG959KIFsWIYCRSsndkhUhudBmPr+PrVO9tgVZ9sKjFOpvPR+7JvGunNZeMcItBQ5tjv9df4ifOy+GptHEd82uowg1GYZW17YJhCnuxiPEWU5IxDy8J\/y9KE="}';
//$test = json_decode($test,true);
		$url = "http://localhost:8081/app/wxpay/notifypub";
		$header[] = "Content-type: text/xml";
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt ( $ch , CURLOPT_POSTFIELDS , $test);
		
		$result = curl_exec($ch);
		return $result;
		
	}
	
	/*
	public function test2(){
		$string1 = "#";
		$string1 = "$";
		$province = M("zone")->where(['level' => 1])->field("name")->select();
		var_dump($province);
		return $province;
	}
	
	public function transfer_xml(){
		$path = $_SERVER['DOCUMENT_ROOT'] . "/Area.xml";
		$arr = simplexml_load_file($path);
		$province = [];
		foreach ($arr->province as $p){
			$name = $p['name'];
			$name = (string)$name;
			$p_id = M("zone")->add(['pid' => 0 ,'name' => $name,'level' => 1]);
			var_dump($p_id);
			foreach ($p->city as $c){
				$name = (string)$c['name'];
				$c_id = M("zone")->add(['pid' => $p_id,'name' => $name, 'level' => 2]);
				
				foreach ($c->county as $a){
					$name = (string)$a['name'];
					$a_id = M("zone")->add(['pid'=> $c_id,'name' => $name , 'level' => 3]);
				}
			}
			
		}
		return "";
		return $arr;
	}
	
	public function transfer_address(){
		$province = M("zone")->where(['level' => 1])->field("zone_id as id, name")->select();
		
		foreach($province as &$p){
			$citys = M("zone")->where(['level' => 2 , 'pid' => $p['id']])->field("zone_id as id ,name")->select();
			
			foreach ($citys as &$c){
				$areas = M("zone")->where(['level' => 3, 'name'=> ['neq' , '其它区'], 'pid' => $c['id']])->field("zone_id as id , name")->select();
				
				$c['child'] = $areas;
			}
			$p['child'] = $citys;
		}
		
		$arr = json_encode($province);
		file_put_contents($_SERVER['DOCUMENT_ROOT']. "/address.js", "var LAreaData = " . $arr);
	}
	
	public function transfer_city(){
		$part=M("zone")
		 ->alias("z")
		 ->join("zone z2","z.pid = z2.zone_id")
		 ->where(['z.level'=>2,'z.first'=>['neq',""]])
		 ->field("GROUP_CONCAT(z.name) as names,GROUP_CONCAT(z.zone_id) as zone_ids,".
		 "GROUP_CONCAT(z.pid) as pids, GROUP_CONCAT(z2.name) as pnames, z.first")
		 ->group("z.first")
		 ->select();
		 $arr = [];
		 for($i=0;$i<count($part);$i++){
			 $names = explode(",", $part[$i]['names']);
			 $zone_ids = explode(",", $part[$i]['zone_ids']);
			 $pnames = explode(",", $part[$i]['pnames']);
			 $zones = [];
			 for($j = 0 ; $j < count($names); $j++){
			 	$zones[] = ['name' => $names[$j] , 'pname' => $pnames[$j]];
			 }
		 	$arr[] = ['code' => $part[$i]['first'],'cities' => $zones];
		 }
		 $arr = json_encode($arr);
		 file_put_contents($_SERVER['DOCUMENT_ROOT']. "/city.js", $arr);
	}
	public function generate(){
		$zones = M("zone")->where(['level' => 2])->select();
		vendor("Area.str2PY");
		$cls = new \str2PY();
		foreach ($zones as $z){
			M("zone")->where(['zone_id' => $z['zone_id']])->save(['first' => $cls->getInitials($z['name'])]);
		}
	}
	
	public function ttttt(){
		$string1 = "#";
		$string2 = "$";
		$string3 = "|";
		$string4 = ",";
		$total_string = "";
		$province = M("zone")->where(['level' => 1])->field("zone_id as id,name")->select();
		$first_province_id = $province[0]['id'];
		for($i = 0; $i<count($province);$i++){
				
			if($province[$i]['id']==$first_province_id){
				$total_string .= $province[$i]['name'].$string2;//浙江省$
			}else{
				$total_string .= $string1.$province[$i]['name'].$string2;//#浙江省$
			}
			echo "ttt";
			$citys = M("zone")->where(['level' => 2 , 'pid' => $province[$i]['id']])->field("zone_id as id ,name")->select();
			$first_city_id = $citys[0]['id'];
			for($j = 0; $j<count($citys);$j++){
				if($citys[$j]['id']==$first_city_id){
					$total_string .= $citys[$j]['name'];//杭州市,
				}else{
					$total_string .= $string3.$citys[$j]['name'];// |温州市,
				}
				$areas = M("zone")->where(['level' => 3, 'pid' => $citys[$j]['id']])->field("zone_id as id , concat('$string4',name) as name")->select();
				for($k = 0; $k < count($areas);$k++){
					$total_string .= $areas[$k]['name'];
				}
			}
		}
		
		file_put_contents(getcwd() . "/test.txt", $total_string);
	}
	
	
	
	public function ttt(){
		$uuid = I("uuid");
		$customer_id = M("customer")->where(['uuid' => $uuid])->getField("customer_id");
		$message = new \app\library\message\Message();
		$client = \app\library\message\MessageClient::getInstance();
		$message->setAction(\app\library\message\Message::ACTION_ORDER_LIST)
		->setTargetIds($customer_id)
		->setPlatform(Message::PLATFORM_PUSH)
		->setExtras(['title' => '标题', 'content' => '内容啊啊啊啊']);
		$client->pushCache($message);
	}
	*/
	/*
	public function upload_unionid(){
		$customers = M()->query("select customer_id,wx_gz_openid from vc_customer where (wx_unionid is null or wx_unionid = '') and wx_gz_openid is not null and wx_gz_openid != '' limit 2000");
		$config = \app\library\SettingHelper::get_pay_params(4);
		$weixin_config = array(
				'app_key' => $config['appid'],
				'app_secret' => $config['app_secret'],
				'authorize' => 'authorization_code'
		);
		$weixin = new \org\oauth\driver\Weixin($weixin_config);
		$access_token = \app\library\weixin\WeixinClient::getInstance()->getAccessToken();
		$sql = "";
		$token = [];
		$token['access_token'] = $access_token;
		$k = 0;
		$sql = "insert into vc_customer (customer_id , wx_unionid) values ";
		foreach ($customers as  $c){
			echo $k;
			if($k % 20 == 0 && $k > 0){
				if(!empty($sql)){
					$sql = substr($sql, 0, strlen($sql) - 1) . " on duplicate key update wx_unionid = values(wx_unionid)";
					var_dump($sql);
					$test = M()->execute($sql);
				}
				$sql = "insert into vc_customer (customer_id , wx_unionid) values ";
				$k = 0;
			}
			
			$token['openid'] = $c['wx_gz_openid'];
			if(empty($token['openid'])){
				continue;
			}
			$weixin->setToken($token);
			$result = $weixin->getOauthInfo(true);
			var_dump($result);
			if(isset($result['unionid'])){
				$c['wx_unionid'] = $result['unionid'];
			}
			if(isset($c['wx_unionid'])){
				$sql .="({$c['customer_id']}, '{$c['wx_unionid']}'),";
				$k ++;
			}
			
			unset($c);
		}
		
		
		$sql = substr($sql, 0, strlen($sql) - 1) . " on duplicate key update wx_unionid = values(wx_unionid)";
		$test = M()->execute($sql);
		var_dump($test);
	}
	
	
	public function upload_files(){
		$dir = "/home/wwwroot/lingmeiyunhe/upload";
		var_dump($dir);
		$this->listDir($dir);
	}
	*/
	private function listDir($dir){
		if(is_dir($dir))
	   	{
	     	if ($dh = opendir($dir)) 
			{
	        	while (($file = readdir($dh)) !== false)
				{
	     			if((is_dir($dir."/".$file)) && $file!="." && $file!="..")
					{
	     				echo "<b><font color='red'>文件名：</font></b>",$file,"<br><hr>";
	     				$this->listDir($dir."/".$file."/");
	     			}
					elseif(strpos($file, "_w=") === false && strpos($file, ".xls") === false && strpos($file, ".xlxs") === false)
					{
	         			if($file!="." && $file!="..")
						{
							$s = $dir . $file;
							$index = strpos( $dir , "upload" );
							$str = substr($dir, $index);
							$str = str_replace("//", "/", $str);
							$res = \app\library\UploadHelper::getInstance()->set_cate($str)->setName($file)->upload_image($s);
	         				echo $file."<br>";
	      				}
	     			}
	        	}
	        	closedir($dh);
	     	}
	   	}
	}
	
	
}