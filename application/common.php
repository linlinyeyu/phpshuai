<?php
/**
 * 全局获取验证码图片
 * 生成的是个HTML的img标签
 * @param string $imgparam <br>
 * 生成图片样式，可以设置<br>
 * length=4&font_size=20&width=238&height=50&use_curve=1&use_noise=1<br>
 * length:字符长度<br>
 * font_size:字体大小<br>
 * width:生成图片宽度<br>
 * heigh:生成图片高度<br>
 * use_curve:是否画混淆曲线  1:画，0:不画<br>
 * use_noise:是否添加杂点 1:添加，0:不添加<br>
 * @param string $imgattrs<br>
 * img标签原生属性，除src,onclick之外都可以设置<br>
 * 默认值：style="cursor: pointer;" title="点击获取"<br>
 * @return string<br>
 * 原生html的img标签<br>
 * 注，此函数仅生成img标签，应该配合在表单加入name=verify的input标签<br>
 * 如：&lt;input type="text" name="verify"/&gt;<br>
 */
function sp_verifycode_img($imgparam='length=4&font_size=20&width=238&height=50&use_curve=1&use_noise=1',$imgattrs='style="cursor: pointer;" title="点击获取"'){
	$src=U("verify/index"). "?" .$imgparam;
	$img="<img class='verify_img' src='$src' onclick='this.src=\"$src&time=\"+Math.random();' $imgattrs/>";
	return $img;
}

/**
 * 验证码检查，验证完后销毁验证码增加安全性 ,<br>返回true验证码正确，false验证码错误
 * @return boolean <br>true：验证码正确，false：验证码错误
 */
function sp_check_verify_code(){
	$verify = new \org\Verify();
	$code = I("verify");
	return $verify->check($code, "");
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @return mixed
 */
function get_client_ip($type = 0) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos    =   array_search('unknown',$arr);
        if(false !== $pos) unset($arr[$pos]);
        $ip     =   trim($arr[0]);
    }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip     =   $_SERVER['HTTP_CLIENT_IP'];
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
*
* update config file
* @param $data @type array
* @return boolean
**/

function save_admin_config($data = [],$type='config')
{
    switch ($type) {
        case 'config':
            $config_file = CONF_DEFINE. $type . '.php';
            break;
        case 'db':
            $config_file = CONF_DEFINE. $type . '.php';
            break;
        default:
            return false;
    }
    if(file_exists($config_file)){
        $config=include $config_file;
    }else {
        $config=array();
    }
    $configs=array_merge($data,$config);
    $result = file_put_contents($config_file, "<?php\treturn " . var_export($configs, true) . ";");
    return $result;    
}

function get_admin_config($type='config')
{
    switch ($type) {
        case 'config':
            $config_file = include CONF_DEFINE. $type . '.php';
            break;
        case 'db':
            $config_file = include CONF_DEFINE. $type . '.php';
            break;
        default:
            return false;
    }
   
    return $config_file;    
}

//获得某天前的最后一秒时间戳
function xtime($day){
    $day = intval($day);
    return mktime(23,59,59,date("m"),date("d")-$day,date("y"));
}

// 获取时间颜色
function get_color_date($time,$type='Y-m-d H:i:s',$color='red'){
    if($time > xtime(1)){
        return date($type,$time);
    }else if(!empty($time)){
        return date($type,$time);
    }
    return "";
}
// 获取时间
function get_date($time,$type='Y-m-d'){

  return date($type,$time);
}

function remove_xss($val) {
   // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
   // this prevents some character re-spacing such as <java\0script>
   // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
   $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

   // straight replacements, the user should never need these since they're normal characters
   // this prevents like <IMG SRC=@avascript:alert('XSS')>
   $search = 'abcdefghijklmnopqrstuvwxyz';
   $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
   $search .= '1234567890!@#$%^&*()';
   $search .= '~`";:?+/={}[]-_|\'\\';
   for ($i = 0; $i < strlen($search); $i++) {
      // ;? matches the ;, which is optional
      // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

      // @ @ search for the hex values
      $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
      // @ @ 0{0,7} matches '0' zero to seven times
      $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
   }

   // now the only remaining whitespace attacks are \t, \n, and \r
   $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
   $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
   $ra = array_merge($ra1, $ra2);

   $found = true; // keep replacing as long as the previous round replaced something
   while ($found == true) {
      $val_before = $val;
      for ($i = 0; $i < sizeof($ra); $i++) {
         $pattern = '/';
         for ($j = 0; $j < strlen($ra[$i]); $j++) {
            if ($j > 0) {
               $pattern .= '(';
               $pattern .= '(&#[xX]0{0,8}([9ab]);)';
               $pattern .= '|';
               $pattern .= '|(&#0{0,8}([9|10|13]);)';
               $pattern .= ')*';
            }
            $pattern .= $ra[$i][$j];
         }
         $pattern .= '/i';
         $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
         $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
         if ($val_before == $val) {
            // no replacements were made, so exit the loop
            $found = false;
         }
      }
   }
   return $val;
}

//输出安全的html
function h($text, $tags = null) {
    $text   =   trim($text);
    //完全过滤注释
    $text   =   preg_replace('/<!--?.*-->/','',$text);
    //完全过滤动态代码
    $text   =   preg_replace('/<\?|\?'.'>/','',$text);
    //完全过滤js
    $text   =   preg_replace('/<script?.*\/script>/','',$text);

    $text   =   str_replace('[','&#091;',$text);
    $text   =   str_replace(']','&#093;',$text);
    $text   =   str_replace('|','&#124;',$text);
    //过滤换行符
    $text   =   preg_replace('/\r?\n/','',$text);
    //br
    $text   =   preg_replace('/<br(\s\/)?'.'>/i','[br]',$text);
    $text   =   preg_replace('/(\[br\]\s*){10,}/i','[br]',$text);
    //过滤危险的属性，如：过滤on事件lang js
    while(preg_match('/(<[^><]+)( lang|on|action|background|codebase|dynsrc|lowsrc)[^><]+/i',$text,$mat)){
        $text=str_replace($mat[0],$mat[1],$text);
    }
    while(preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i',$text,$mat)){
        $text=str_replace($mat[0],$mat[1].$mat[3],$text);
    }
    if(empty($tags)) {
        $tags = 'table|td|th|tr|i|b|u|strong|img|p|br|div|strong|em|ul|ol|li|dl|dd|dt|a';
    }
    //允许的HTML标签
    $text   =   preg_replace('/<('.$tags.')( [^><\[\]]*)>/i','[\1\2]',$text);
  $text = preg_replace('/<\/('.$tags.')>/Ui','[/\1]',$text);
    //过滤多余html
    $text   =   preg_replace('/<\/?(html|head|meta|link|base|basefont|body|bgsound|title|style|script|form|iframe|frame|frameset|applet|id|ilayer|layer|name|script|style|xml)[^><]*>/i','',$text);
    //过滤合法的html标签
    while(preg_match('/<([a-z]+)[^><\[\]]*>[^><]*<\/\1>/i',$text,$mat)){
        $text=str_replace($mat[0],str_replace('>',']',str_replace('<','[',$mat[0])),$text);
    }
    //转换引号
    while(preg_match('/(\[[^\[\]]*=\s*)(\"|\')([^\2=\[\]]+)\2([^\[\]]*\])/i',$text,$mat)){
        $text=str_replace($mat[0],$mat[1].'|'.$mat[3].'|'.$mat[4],$text);
    }
    //过滤错误的单个引号
    while(preg_match('/\[[^\[\]]*(\"|\')[^\[\]]*\]/i',$text,$mat)){
        $text=str_replace($mat[0],str_replace($mat[1],'',$mat[0]),$text);
    }
    //转换其它所有不合法的 < >
    $text   =   str_replace('<','&lt;',$text);
    $text   =   str_replace('>','&gt;',$text);
    $text   =   str_replace('"','&quot;',$text);
     //反转换
    $text   =   str_replace('[','<',$text);
    $text   =   str_replace(']','>',$text);
    $text   =   str_replace('|','"',$text);
    //过滤多余空格
    $text   =   str_replace('  ',' ',$text);
    return $text;
}
function microtime_float(){ 
    if( PHP_VERSION > 5){ 
        return microtime(true); 
    }else{ 
        list($usec, $sec) = explode(" ", microtime()); 
        return ((float)$usec + (float)$sec); 
    }
} 

function get_microtime_time($time = null){
    if(!$time){
        $time = microtime_float();
    }
    
    $micro = explode(".", $time);
    if(count($micro) > 1){
        $micro = substr($micro[1]."000", 0,3);
    }else{
        $micro = "000";
    }
    $time = date("His",$time).$micro;
    return (int)$time;
}

  

function build_uuid()
{
    $str = "";
    $count = 0;
    do{
    	$str = rand(10000000, 99999999).substr(time(),-1,2);
    	$count = M("customer")->where(['uuid' => $str])->count();
    }while($count > 0);
    return $str;
}

function make_promotion() {    
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';    
        $rand = $code[rand(0,25)]    
            .strtoupper(dechex(date('m')))    
            .date('d').substr(time(),-5)    
            .substr(microtime(),2,5)    
            .sprintf('%02d',rand(0,99));    
        for(    
            $a = md5( $rand, true ),    
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',    
            $d = '',    
            $f = 0;    
            $f < 8;    
            $g = ord( $a[ $f ] ),    
            $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],    
            $f++    
        );    
        return $d;    
}




function get_location($ip){
    if(!$ip){
        return;
    }
    $content = file_get_contents("http://api.map.baidu.com/location/ip?ak=qaTfrMITskQl2Nt8KGOXd1GnB8is0rwR&ip={$ip}&coor=bd09ll");
  	try{
  		$content = json_decode($content, true);
  	}catch(Exception $e){
  		return "本机地址";
  	}
  	if($content['status'] != 0){
  		return "本机地址";
  	}
    return $content['content']['address'];
}

function mkDirs($dir){
	if(!is_dir($dir)){
		if(!mkDirs(dirname($dir))){
			return false;
		}
		if(!mkdir($dir,0777)){
			return false;
		}
	}
	return true;
}

function array_key_arr($arr , $key){
	if(empty($arr) || !is_array($arr)){
		return [];
	}
	$a = [];
	foreach ($arr as $v){
		if(isset($v[$key])){
			if(empty($a[$v[$key]])){
				$a[$v[$key]] = [$v];
			}else{
				$a[$v[$key]][] = $v;
			}
			
		}
	}
	return $a;
}

function is_weixin(){
	if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
		return true;
	}
	return false;
}

function token()
{
	$token = mt_rand(0,9999).substr(time(),-4).substr(microtime(),2,6).mt_rand(1000,9999).NOW_TIME;
	return sha1($token);
}

function get_domain(){
	$domain = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
 	if(!$domain){
 		return \app\library\SettingHelper::get("shuaibo_host", "lmyh.anewbegin.com");	
 	}
	return $domain;
	//return isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
}

function convertUnderline ( $str , $ucfirst = true)
{
	$str = ucwords(str_replace('_', ' ', $str));
	$str = str_replace(' ','',lcfirst($str));
	return $ucfirst ? ucfirst($str) : $str;
}

function arrayToXml($arr)
{
	$xml = "<xml>";
	foreach ($arr as $key=>$val)
	{
		if (is_numeric($val))
		{
			$xml.="<".$key.">".$val."</".$key.">";

		}
		else
			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
	}
	$xml.="</xml>";
	return $xml;
}

function xmlToArray($xml)
{
	//将XML转为array
	$array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
	return $array_data;
}

function createNo($table = "order_info", $field = "order_sn", $prefix ="PO"){
	$billno = "";
	while (1) {
		$billno = date('YmdHis') . random(6, true);
		$billno = $prefix . $billno;
		
		$exists = S("shuaibo_order_sn_dup_" . $table . "_" . $billno);
		
		if(!$exists){
			break;
		}
	}
	\think\Cache::set("shuaibo_order_sn_dup_" . $table . "_" . $billno, 1, 10);
	return  $billno;
}

function get_customer_id($access_token = '', $use_uuid = false , $force = false){
	$customer_id = "";
	if($use_uuid && I("uuid")){
		$customer = M("customer")->where(["uuid" => I("uuid")])->field("customer_id")->find();
		if(!empty($customer)){
			$customer_id = $customer['customer_id'];
		}
	}else{
		if(empty($access_token)){
			$access_token = I("access_token");
		}
		
		$customer_id = "";
		if(!empty($access_token)){
			$customer_id = S($access_token);
			if(empty($customer_id)){
				$customer_id = M("customer")->where(['access_token' => $access_token])->getField("customer_id");
				S($access_token, $customer_id);
			}
		}
	}
    
    return $customer_id;
}

function array_key_arr_single($arr , $key){
	if(empty($arr) || !is_array($arr)){
		return [];
	}
	$a = [];
	foreach ($arr as $v){
		if(isset($v[$key])){
			$a[$v[$key]] = $v;
		}
	}
	return $a;
}

function get_rand_name(){
	return substr(md5(time().rand(0,9999)),0, 8);
}

function get_current_url() {
	$sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
	$php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
	$path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
	$relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
	return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
}

function random($length, $numeric = FALSE) {
	$seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
	if ($numeric) {
		$hash = '';
	} else {
		$hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
		$length--;
	}
	$max = strlen($seed) - 1;
	for ($i = 0; $i < $length; $i++) {
		$hash .= $seed{mt_rand(0, $max)};
	}
	return $hash;
}

function http_request($url){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$res = curl_exec($ch);
	$result['content'] = $res;	
	return $result;
}


function weixin_log( $filename,$dir, $comment){
	vendor("Weixin.WxLog");
	
	$log_ = new \Log_();
	$dir = "data/" . $dir . "/" ;
	$log_name= $dir. $filename;//log文件路径
	
	mkDirs($_SERVER['DOCUMENT_ROOT'] . "/" . $dir);
	$log_->log_result($log_name,$comment);
}

function get_str_length($str){
	return (strlen($str) + mb_strlen($str,'UTF8')) / 2;
}

function get_url($str){
	if(strlen($str) < 2){
		return "http://".get_domain()."/images/no_picture.gif";
	}
	return "http://".get_domain()."/".$str;
}

//正则匹配手机号
function verifyParam($param){
	if(preg_match("/^1[34578]{1}\d{9}$/",$param)){
		return "phone";
	}
	if(preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $param)){
		return "email";
	}
	return "nickname";
}

//发送邮件
function think_send_mail($to, $name, $subject = '', $body = '', $attachment = null){

	$config = C('THINK_EMAIL');
	
	vendor('PHPMailer.class#phpmailer'); //从PHPMailer目录导class.phpmailer.php类文件
	vendor('SMTP');
	$mail = new PHPMailer(); //PHPMailer对象
	
	$mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
	
	$mail->IsSMTP(); // 设定使用SMTP服务
	
	$mail->SMTPDebug = 0; // 关闭SMTP调试功能
	
	// 1 = errors and messages
	
	// 2 = messages only
	
	$mail->SMTPAuth = true; // 启用 SMTP 验证功能
	
	$mail->SMTPSecure = 'ssl'; // 使用安全协议
	
	$mail->Host = $config['SMTP_HOST']; // SMTP 服务器
	
	$mail->Port = $config['SMTP_PORT']; // SMTP服务器的端口号
	
	$mail->Username = $config['SMTP_USER']; // SMTP服务器用户名
	
	$mail->Password = $config['SMTP_PASS']; // SMTP服务器密码
	
	$mail->SetFrom($config['FROM_EMAIL'], $config['FROM_NAME']);
	
	$replyEmail = $config['REPLY_EMAIL']?$config['REPLY_EMAIL']:$config['FROM_EMAIL'];
	
	$replyName = $config['REPLY_NAME']?$config['REPLY_NAME']:$config['FROM_NAME'];
	
	$mail->AddReplyTo($replyEmail, $replyName);
	
	$mail->Subject = $subject;
	
	$mail->AltBody = "为了查看该邮件，请切换到支持 HTML 的邮件客户端"; 
	
	$mail->MsgHTML($body);
	
	$mail->AddAddress($to, $name);
	
	if(is_array($attachment)){ // 添加附件
	
	foreach ($attachment as $file){
	
	is_file($file) && $mail->AddAttachment($file);
	
	}
	
	}
	
	return $mail->Send() ? true : $mail->ErrorInfo;

}

//httpPost
function httpPostData($url,$string){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json;charset=utf-8','Content-Length:'.strlen($string)));
	$return_content = curl_exec($ch);
	$return_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
	return ['errcode' => $return_code,'return_content' => $return_content];
}

//httpget
function httpGetData($url){
    $ch = curl_init();
    $timeout = 5;
    $header = array("charset=UTF-8");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}


function getSign($params) {
    ksort($params);
    $stringToBeSigned = "";
    $i = 0;
    foreach ($params as $k => $v) {
        if (false === checkEmpty($v) && "@" != substr($v, 0, 1)) {

            if ($i == 0) {
                $stringToBeSigned .= "$k" . "=" . "$v";
            } else {
                $stringToBeSigned .= "&" . "$k" . "=" . "$v";
            }
            $i++;
        }
    }
    unset ($k, $v);
    return md5($stringToBeSigned);
}

function checkEmpty($value) {
    if (!isset($value))
        return true;
    if ($value === null)
        return true;
    if (trim($value) === "")
        return true;

    return false;
}

//招行post
function httpPost($parasData, $url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "jsonRequestData=" . $parasData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

//招行签名
 function sign($reqData, $merKey){

    $strToSign = '';
    $reqData = $this->dictSort($reqData);
    foreach($reqData as $key => $val){
        $strToSign = $strToSign.$key."=".$val."&";
    }
    $strToSign = $strToSign.$merKey;

    //sha256加密
    $strEncrypt = hash('sha256', $strToSign);
    return $strEncrypt;

}