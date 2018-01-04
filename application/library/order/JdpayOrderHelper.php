<?php
namespace app\library\order;
class JdpayOrderHelper extends OrderHelper{
	/* (non-PHPdoc)
	 * @see \app\library\OrderHelper::pay_params()
	 */
	public function pay_params($customer_id) {
		vendor("Jdpay.SignUtil");
		$jdSign = new \SignUtil();
		vendor("Jdpay.TDESUtil");
		$jdTDES = new \TDESUtil();
		$jdpay_config = C("jdpay");
		$parameter = array(
				"version" => $jdpay_config['version'],
				"merchant" => $jdpay_config['merchant'],
				"tradeNum" => $this->order_number,
				"tradeName" => $this->pay_name,
				"tradeDesc" => $this->subject,
				"tradeTime" => time(),
				"amount" => $this->sum,
				"currency" => $jdpay_config['currency'],
				"callbackUrl" => $jdpay_config['callbackUrl'],
				"notifyUrl" => $jdpay_config['notifyUrl'],
				"orderType" => '0',
		);
		
		$unSignKeyList = array ("sign");
		$oriUrl = $jdpay_config["serverPayUrl"];
		$desKey = $jdpay_config["desKey"];
		
		$sign = $jdSign->signWithoutToHex($parameter, $unSignKeyList);
		$parameter["sign"] = $sign;
		$keys = base64_decode($desKey);
		
		$parameter["tradeNum"]=$jdTDES->encrypt2HexStr($keys, $parameter["tradeNum"]);
		if($parameter["tradeName"] != null && $parameter["tradeName"]!=""){
			$parameter["tradeName"]=$jdTDES->encrypt2HexStr($keys, $parameter["tradeName"]);
		}
		if($parameter["tradeDesc"] != null && $parameter["tradeDesc"]!=""){
			$parameter["tradeDesc"]=$jdTDES->encrypt2HexStr($keys, $parameter["tradeDesc"]);
		}
		
		$parameter["tradeTime"]=$jdTDES->encrypt2HexStr($keys, $parameter["tradeTime"]);
		$parameter["amount"]=$jdTDES->encrypt2HexStr($keys, $parameter["amount"]);
		$parameter["currency"]=$jdTDES->encrypt2HexStr($keys, $parameter["currency"]);
		$parameter["callbackUrl"]=$jdTDES->encrypt2HexStr($keys, $parameter["callbackUrl"]);
		$parameter["notifyUrl"]=$jdTDES->encrypt2HexStr($keys, $parameter["notifyUrl"]);
		
		$this->return = array_merge($this->return, $parameter);
		$this->return['count'] = $this->sum;
		return ['errcode' => 0, 'message' => '请求成功'];
	}

	
}