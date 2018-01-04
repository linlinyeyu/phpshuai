<?php
namespace app\common\model;
use think\Model;
class CustomerWithdraw extends Model{

	public function withdraw($id, $user_id = 0){
		$withdraw = M("customer_withdraw")
		->alias("cw")
		->join("customer c","c.customer_id = cw.customer_id")
		->field("cw.order_sn,cw.money,cw.type,cw.goods_id, cw.order_sn,cw.state,c.active,c.wx_gz_openid,wx_openid,c.customer_id,cw.account,cw.realname,cw.style")
		->where(['cw.id' => $id])
		->find();
		
		if(empty($withdraw)){
			return ['errcode' => -101 ,'message' => '找不到相关提现请求'];
		}
			
		$sum = $withdraw['money'];
		
		$type = $withdraw['type'];
		
		$data = [
				'date_audit' => time(),
				'state' => 1,
				'user_id' => $user_id
		];
		
		if($type != 2){
			$pay_type = $type == 1 ? 4 : 4 ;
			$openid = $type == 1 ? $withdraw['wx_gz_openid'] : $withdraw['wx_gz_openid'];
			$config = \app\library\SettingHelper::get_pay_params($pay_type);
			
			vendor("Weixin.WxPayHelper");
			$weixin = new \WxPayHelper($config);
			
			$response = $weixin->companyPay($withdraw['order_sn'], $openid, $sum);
			
			if($response['errcode'] != 0){
				weixin_log("weixin_error.log", "/withdraw/", "【请求参数】\n" . "order_sn: {$withdraw['order_sn']}, openid:{$openid}, 金额：{$sum}元" . "\n" . 
				"【接收到的错误通知】:\n". json_encode($response)."\n");
				
				return $response;
			}
			$data['out_trade_no'] = $response['content']['payment_no'];
		}{
            // 支付宝提现操作
            vendor("Alipay.alipay.AopSdk");

            $params = \app\library\SettingHelper::get_pay_params(2, ['is_open' => 0]);

            if($params['is_open'] == 0){
                return ['errcode' => -102 , 'message' => '支付宝暂未开启'];
            }

            // 账号信息
            $out_biz_no = $withdraw['order_sn'];
            $payee_account = $withdraw['account'];
            $amount = $withdraw['money'];
            $realname = $withdraw['realname'];

            $aop = new \AopClient ();
            $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
            $aop->appId = '2017051907286434';
            $aop->rsaPrivateKey = $params['private_key'];
            $aop->alipayrsaPublicKey = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB";
            $aop->apiVersion = '1.0';
            $aop->signType = 'RSA';
            $aop->postCharset='utf-8';
            $aop->format='json';
            $request = new \AlipayFundTransToaccountTransferRequest ();
            $request->setBizContent("{" .
                "    \"out_biz_no\":\"$out_biz_no\"," .
                "    \"payee_type\":\"ALIPAY_LOGONID\"," .
                "    \"payee_account\":\"$payee_account\"," .
                "    \"amount\":\"$amount\"," .
                "    \"payee_real_name\":\"$realname\"," .
                "  }");
            $result = $aop->execute ( $request);

            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            if(!empty($resultCode)&&$resultCode == 10000){
            } else {
                return ['errcode' => -200, 'message' => '提现失败，请联系客服'];
            }
        }
		
		M("customer_withdraw")->where(['id' => $id])->save($data);
		if ($withdraw['style'] == 1) {
            M('customer')->where(['customer_id' => $withdraw['customer_id']])->setDec('account',$sum);
            // 资金明细
            M('finance')->add([
                'customer_id' => $withdraw['customer_id'],
                'finance_type_id' => 3,
                'type' => 5,
                'amount' => $sum,
                'date_add' => time(),
                'is_minus' => 1,
                'order_sn' => $out_biz_no,
                'comments' => '提现 '.$out_biz_no,
                'title' => '-'.$sum,
            ]);
        } else {
            M('customer')->where(['customer_id' => $withdraw['customer_id']])->setDec('commission',$sum);
            M('finance')->add([
                'customer_id' => $withdraw['customer_id'],
                'finance_type' => 5,
                'amount' => $sum,
                'real_amount' => $sum,
                'date_add' => time(),
                'is_minus' => 1,
                'order_sn' => $out_biz_no,
                'comments' => '提现 '.$out_biz_no,
                'title' => '-'.$sum,
            ]);
		}

//        $client = \app\library\message\MessageClient::getInstance();
//		$message = new \app\library\message\Message();
//		$message->setTargetIds($withdraw['customer_id'])
//		->setPlatform([\app\library\message\Message::PLATFORM_ALL])
//		->setTemplate("withdraw_ok")
//		->setExtras(['title' => '申请提现成功', 'content' => '您的提现申请已通过', 'goods_id' => $withdraw['goods_id']])
//		->setWeixinExtra(['money' => $withdraw['money'], 'date_add' => time()])
//		->setAction(\app\library\message\Message::ACTION_WITHDRAW_RECORD);
//		$client->pushCache($message);
		
		return ['errcode' => 0 ,'message' => '操作成功'];
	}
	
}