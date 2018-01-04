<?php
class WxPayHelper{
    
	private $extra = [];
	
	public $data ;
	
	private $config = [];
	
    public function  __construct($config = array()) {
    	$this->config = $config;
    }
    
    
    public function setExtra($extra = []){
    	$this->extra = $extra;
    	return $this;
    }

    //获取预支付订单
    public function getPrePayOrder($body, $out_trade_no, $total_fee){
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $notify_url = $this->config["notify_url"];

        $nonoce_str = $this->getRandChar(32);

        $data["appid"] = $this->config["appid"];
        $data["body"] = $body;
        $data["mch_id"] = $this->config['mch_id'];
        $data["nonce_str"] = $nonoce_str;
        $data["notify_url"] = $notify_url;
        $data["out_trade_no"] = $out_trade_no;
        $data["spbill_create_ip"] = $this->get_client_ip();
        $data["total_fee"] = $total_fee;
        $data["trade_type"] = "APP";
        
        if(!empty($this->extra)){
        	$data = array_merge( $data, $this->extra);
        }
        $s = $this->getSign($data, false);
        $data["sign"] = $s;
        $xml = $this->arrayToXml($data);
        $response = $this->postXmlCurl($xml, $url);
        //将微信返回的结果xml转成数组
        return $this->xmlstr_to_array($response);
    }

    //执行第二次签名，才能返回给客户端使用
    public function getOrder($prepayId){
        $data["appId"] = $this->config["appid"];
        $data["nonceStr"] = $this->getRandChar(32);
        $data["package"] = "prepay_id=$prepayId";
        $data['signType'] = "MD5";
        $data["timeStamp"] = time();
        $data["paySign"] = $this->getSign($data);
        return $data;
    }
    
    public function getAppOrder($prepayId){
    	$data['appid'] = $this->config['appid'];
    	$data['partnerid'] = $this->config['mch_id'];
    	$data['prepayid'] = $prepayId;
    	$data['package'] = "Sign=WXPay";
    	$data['noncestr'] = $this->getRandChar(32);
    	$data['timestamp'] = time();
    	$data['sign'] = $this->getSign($data);
    	return $data;
    }

    /*
        生成签名
    */
    function getSign($Obj)
    {
        foreach ($Obj as $k => $v)
        {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //echo "【string】 =".$String."</br>";
        //签名步骤二：在string后加入KEY
        $String = $String."&key=".$this->config['api_key'];
        //签名步骤三：MD5加密
        $result_ = strtoupper(md5($String));
        return $result_;
    }

    //获取指定长度的随机字符串
    function getRandChar($length){
       $str = null;
       $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
       $max = strlen($strPol)-1;

       for($i=0;$i<$length;$i++){
        $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
       }

       return $str;
    }

    //数组转xml
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

    //post https请求，CURLOPT_POSTFIELDS xml格式
    function postXmlCurl($xml,$url,$second=30, $cert = false)
    {       
        //初始化curl        
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        if($cert && isset($this->config['client_pem_path'])){
        	curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
        	$pem_path = $_SERVER['DOCUMENT_ROOT'] . $this->config['client_pem_path'];
        	$key_path = $_SERVER['DOCUMENT_ROOT'] . $this->config['client_key_path'];
        	curl_setopt($ch,CURLOPT_SSLCERT,$pem_path);
        	curl_setopt($ch,CURLOPT_SSLKEY,$key_path);
        }
        
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data)
        {
            curl_close($ch);
            return $data;
        }
        else 
        { 
            $error = curl_errno($ch);
            curl_close($ch);
            return false;
        }
    }
    
    /*
        获取当前服务器的IP
    */
    function get_client_ip()
    {
        if ($_SERVER['REMOTE_ADDR']) {
        $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
        $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
        $cip = getenv("HTTP_CLIENT_IP");
        } else {
        $cip = "unknown";
        }
        if($cip == "::1"){
        	$cip = "127.0.0.1";
        }
        return $cip;
    }

    //将数组转成uri字符串
    function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            if($urlencode)
            {
               $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0) 
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }
    

    /**
    xml转成数组
    */
    function xmlstr_to_array($xmlstr) {
      $doc = new DOMDocument();
      $doc->loadXML($xmlstr);
      return $this->domnode_to_array($doc->documentElement);
    }
    function domnode_to_array($node) {
      $output = array();
      switch ($node->nodeType) {
       case XML_CDATA_SECTION_NODE:
       case XML_TEXT_NODE:
        $output = trim($node->textContent);
       break;
       case XML_ELEMENT_NODE:
        for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
         $child = $node->childNodes->item($i);
         $v = $this->domnode_to_array($child);
         if(isset($child->tagName)) {
           $t = $child->tagName;
           if(!isset($output[$t])) {
            $output[$t] = array();
           }
           $output[$t][] = $v;
         }
         elseif($v) {
          $output = (string) $v;
         }
        }
        if(is_array($output)) {
         if($node->attributes->length) {
          $a = array();
          foreach($node->attributes as $attrName => $attrNode) {
           $a[$attrName] = (string) $attrNode->value;
          }
          $output['@attributes'] = $a;
         }
         foreach ($output as $t => $v) {
          if(is_array($v) && count($v)==1 && $t!='@attributes') {
           $output[$t] = $v[0];
          }
         }
        }
       break;
      }
      return $output;
    }
    
    
    public function checkSign($xml){
    	$data = $this->xmlstr_to_array($xml);
    	$this->data = $data;
    	$tmpData = $data;
    	unset($tmpData['sign']);
    	$sign = $this->getSign($tmpData);//本地签名
    	if ($this->data['sign'] == $sign) {
    		return TRUE;
    	}
    	return FALSE;
    }
    
    public function returnXml($code, $message){
    	$data = ['return_code' => $code , 'return_msg' => $message];
    	return $this->arrayToXml($data);
    }
    
    public function companyPay($trade_no, $openid, $amount, $comment = "提现"){
    	$url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
    	
    	if(!isset($this->config['mch_id']) ){
    		return ['errcode' => -101, 'message' => '请先配置微信支付'];
    	}
    	if(!isset($this->config['client_pem_path'])){
    		return ['errcode' => -102 , 'message' => '请先配置微信证书信息'];
    	}
    	
    	$data['mchid'] = $this->config['mch_id'];
    	$data['mch_appid'] = $this->config['appid'];
    	$data['nonce_str'] = $this->getRandChar(32);
    	$data['partner_trade_no'] = $trade_no;
    	$data['openid'] = $openid;
    	$data['check_name'] = "NO_CHECK";
    	$data['amount'] = (int)($amount * 100) ;
    	$data['desc'] = $comment;
    	$data['spbill_create_ip'] = $this->get_client_ip();
    	$s = $this->getSign($data, false);
    	$data["sign"] = $s;
    	$xml = $this->arrayToXml($data);
    	$response = $this->postXmlCurl($xml, $url , 30 , true);
    	if(!$response){
    		return ['errcode' => -103 , 'message' => '请求出错'];
    	}
    	$response = $this->xmlstr_to_array($response);
    	if(isset($response['result_code']) && $response['result_code'] == "SUCCESS"){
    		unset($response['result_code']);
    		unset($response['return_code']);
    		unset($response['return_msg']);
    		return ['errcode' => 0 ,'message' => '请求成功', 'content' => $response];
    	}
    	if(isset($response['result_code']) && $response['result_code'] != "SUCCESS" && isset($response['return_msg'])){
    		return ['errcode' => -104 , 'message' => $response['return_msg']];
    	}
    	return ['errcode' => -105 , 'message' => '未知错误'];
    }
    
    public function refund($refund_no, $trade_no , $total_fee, $refund_fee){
    	$url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
    	 
    	if(!isset($this->config['mch_id']) ){
    		return ['errcode' => -101, 'message' => '请先配置微信支付'];
    	}
    	if(!isset($this->config['client_pem_path'])){
    		return ['errcode' => -102 , 'message' => '请先配置微信证书信息'];
    	}
    	 
    	$data['mch_id'] = $this->config['mch_id'];
    	$data['appid'] = $this->config['appid'];
    	$data['nonce_str'] = $this->getRandChar(32);
    	$data['out_trade_no'] = $trade_no;
    	$data['out_refund_no'] = $refund_no;
    	$data['total_fee'] = (int)($total_fee * 100);
    	$data['refund_fee'] = (int)($refund_fee * 100);
    	$data['op_user_id'] = $this->config['mch_id'];
    	$s = $this->getSign($data, false);
    	$data["sign"] = $s;
    	$xml = $this->arrayToXml($data);
    	$response = $this->postXmlCurl($xml, $url , 30 , true);
    	if(!$response){
    		return ['errcode' => -103 , 'message' => '请求出错'];
    	}
    	$response = $this->xmlstr_to_array($response);
    	if(isset($response['result_code']) && $response['result_code'] == "SUCCESS"){
    		unset($response['result_code']);
    		unset($response['return_code']);
    		unset($response['return_msg']);
    		return ['errcode' => 0 ,'message' => '请求成功', 'content' => $response];
    	}
    	if(isset($response['result_code']) && $response['result_code'] != "SUCCESS" && isset($response['err_code_des'])){
    		return ['errcode' => -104 , 'message' => $response['err_code_des']];
    	}
    	if(isset($response['return_code']) && $response['return_code'] == "FAIL"){
    		return ['errcode' => -104 , 'message' => $response['return_msg']];
    	}
    	
    	return ['errcode' => -105 , 'message' => '未知错误'];
    }
}
?>