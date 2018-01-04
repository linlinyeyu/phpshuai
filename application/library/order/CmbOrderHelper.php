<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/12/25
 * Time: 17:37
 */

namespace app\library\order;

class CmbOrderHelper extends OrderHelper{

    public function pay_params($customer_id)
    {
        $params = \app\library\SettingHelper::get_pay_params(8, ['is_open' => 0]);
        if ($params['is_open'] == 0){
            return ['errcode' => -201,'message' => '招商一网通未开通'];
        }

        $this->return['version'] = "1.0";
        $this->return['charset'] = "UTF-8";
        $this->return['signType'] = "SHA-256";
        $this->return['reqData']['branchNo'] = $params['branch_no'];
        $this->return['reqData']['merchantNo'] = $params['merchant_no'];
        $this->return['reqData']['payNoticeUrl'] = "http://".get_domain()."/app/cmbpay/notify";
        $protocol_num = M("customer")->where(['customer_id' => $customer_id])->getField("protocol_num");
        if (empty($protocol_num)){
            $serial_no = createNo("cmb_protocol","serial_no","");
            $this->return['reqData']['agrNo'] = md5(uniqid(mt_rand(),true));
            //记录进表
            M("cmb_protocol")->add([
                'customer_id' => $customer_id,
                'serial_num' => $serial_no,
                'status' => 0,
                'date_add' => time(),
                'date_upd' => time(),
                'protocol_num' => $this->return['reqData']['agrNo']]
            );
            $this->return['reqData']['serial_no'] = $serial_no;
            $this->return['reqData']['signNoticeUrl'] = "http://".get_domain()."/app/cmbpay/signNotify";
        }else{
            $this->return['reqData']['agrNo'] = $protocol_num;
        }

        return ['errcode' => 0,'message' => '请求成功'];
    }
}