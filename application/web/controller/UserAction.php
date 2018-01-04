<?php
/**
 * Created by PhpStorm.
 * User: sunhandong
 * Date: 2017/5/2
 * Time: 下午2:35
 */
namespace app\web\controller;

use think\Cache;
class UserAction{
    // 用户信息
    public function getUserInfo(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $res = D("user_model")->getUserInfo($customer_id);
        if ($res['errcode'] != 0) {
            return $res;
        }
        $customer = $res['content'];
        unset($customer['access_token']);
        return ["errcode" => 0, 'message' => '成功', 'content' => $customer];
    }

    //发送短信验证码
    public function sendCode(){
        return;
        vendor("Weixin.WxLog");

        $log_ = new \Log_();
        $dir = "data/code/";
        $log_name= $dir. "code_log.log";//log文件路径
        mkDirs($_SERVER['DOCUMENT_ROOT'] . "/" . $dir);
        $log_->log_result($log_name,json_encode($_SERVER)."\n");
        $param = I("param");
        $type = I("type");
        if(empty($param)){
            return ['errcode' => -101, 'message' => '请输入邮箱或手机号'];
        }
        //防止频繁发送
        $ip = get_client_ip();
        $times = (int)Cache::get("shuaibo_send_code_". $ip . "_" . $param);
        if($times > 3){
            return ['errcode' => -102, 'message' => '请慢点发送短信'];
        }

        Cache::set("shuaibo_send_code_". $ip . "_" . $param, ++$times, 60);

        $field = verifyParam($param);
        if ($field != 'phone' && $field != 'email'){
            return ['errcode' => -103, 'message' => '请填入正确格式手机号或邮箱'];
        }
        if ($type == 0){
        	$customer_id = M("customer")->where([$field => $param])->getField("customer_id");
        	if (!empty($customer_id)){
        		return ['errcode' => -101,'message' => '该账号已注册'];
        	}
        }
        if ($field == 'phone'){
        	$customer = D("customer_model")->where(['phone' => $param])->find();
        	if(empty($customer)){
        		$type = 0;
        	}
        }
        $code = D("verify_code_model")->send($param,$type,$field);

        return ['errcode' => 0, 'message' => '发送成功'];
    }

    // 修改密码
    public function modifyPassword() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $phone = I("phone");
        if (empty($phone)) {
            return ['errcode' => -100, 'message' => '请传入手机号码'];
        }
        $code = I('code');
        if (empty($code)) {
            return ['errcode' => -101, 'message' => '请输入验证码'];
        }
        $res = D("verify_code_model")->verify($phone, $code, 6);
        if($res['errcode'] < 0){
            return $res;
        }
        $passwd = I("passwd");
        if (empty($passwd)) {
            return ['errcode' => -102, 'message' => '请输入新密码'];
        }
        $confirm_passwd = I("confirm_passwd");
        if (empty($confirm_passwd)) {
            return ['errcode' => -103, 'message' => '请输入确认密码'];
        }
        if($passwd != $confirm_passwd){
            return ['errcode' => -104, 'message' => '新密码跟确认密码不一致,请重新输入'];
        }

        $condition = array(
            'customer_id' => $customer_id
        );

        $customer = D("user_model")->myFind($condition);
        if(empty($customer)){
            return ['errcode' => -105, 'message' => '没有该用户'];
        }
        if ($customer['passwd'] == md5(sha1($passwd))){
            return ['errcode' => -106, 'message' => '新旧密码不能一样'];
        }

        $data = array(
            'passwd' => md5(sha1($passwd))
        );
        $res = D("user_model")->mySave($condition,$data);
        if(!$res){
            return ['errcode' => -107, 'message' => '修改密码失败'];
        }
        // 同步lg修改密码
        $LG = new \app\api\LGApi();
        $lg_user = $LG->getUsername($customer['nickname']);
        if ($lg_user == true) {
            $res = $LG->EditUpdatePass([
                'userid' => $lg_user,
                'userpass' => $passwd,
                'passtype' => 1
            ]);
            $res = json_decode($res['return_content'],true);
            if ($res['code'] != 0) {
                return ['errcode' => -100, 'message' => $res['message']];
            }
        }
        return ['errcode' => 0, 'message' => '修改密码成功'];

    }

    /**
     * 添加绑定手机
     */
    public function phoneBind() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $phone = I('phone');
        if (empty($phone)) {
            return ['errcode' => -100, 'message' => '请输入手机号码'];
        }
        $code = I('code');
        if (empty($code)) {
            return ['errcode' => -101, 'message' => '请输入验证码'];
        }
        // type = 8 添加绑定手机 type = 9 更改绑定手机
        $res = D("verify_code_model")->verify($phone, $code, 8);
        if($res['errcode'] < 0){
            return $res;
        }
        D('user_model')->mySave(['customer_id' => $customer_id],['phone' => $phone]);
        return ['errcode' => 0, 'message' => '绑定手机成功', 'content' => $phone];
    }

    /**
     * 更改绑定手机
     */
    public function changePhoneBind() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $phone = I('phone');
        if (empty($phone)) {
            return ['errcode' => -100, 'message' => '请输入原手机号码'];
        }
        $code = I('code');
        if (empty($code)) {
            return ['errcode' => -101, 'message' => '请输入原手机号码验证码'];
        }
        $newPhone = I('newphone');
        if (empty($newPhone)) {
            return ['errcode' => -102, 'message' => '请输入新手机号码'];
        }
        $newCode = I('newcode');
        if (empty($newCode)) {
            return ['errcode' => -103, 'message' => '请输入新手机号码验证码'];
        }
        // type = 8 添加绑定手机 type = 9 更改绑定手机
        $res = D("verify_code_model")->verify($phone, $code, 9);
        if($res['errcode'] < 0){
            return $res;
        }
        $res = D("verify_code_model")->verify($newPhone, $newCode, 9);
        if($res['errcode'] < 0){
            return $res;
        }
        D('user_model')->mySave(['customer_id' => $customer_id],['phone' => $newPhone]);
        return ['errcode' => 0, 'message' => '更改绑定手机成功', 'content' => $newPhone];
    }

    /**
     * 解绑邮箱
     */
    public function unbindEmail() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $email = I("email");
        $code = I('code');
        $res = D("verify_code_model")->verify($email,$code, 7, "email");
        if($res['errcode'] < 0){
            return $res;
        }
        $customer = D("customer_model")->myFind(['customer_id' => $customer_id],"phone");
        if (empty($customer)) {
            return ['errcode' => -100, 'message' => '未找到该用户'];
        }
        D("customer_log_model")->Log($customer_id, 3, "解绑邮箱". $email);
        D("customer_model")->mySave(['customer_id' => $customer_id],['email' => ""]);
        return ['errcode' => 0, 'message' => '解绑成功'];
    }

    /**
     * 设置头像
     */
    public function changeAvater() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        // 上传用户图像，进行修改
        $avater = I("avater");
        if(empty($avater)){
            return ['errcode' => -100, 'message' => '请传入头像图片'];
        }

        D("customer_log_model")->Log($customer_id, 3, "修改头像，头像url为：". $avater);
        D("customer_model")->mySave(['customer_id' => $customer_id],['avater' => $avater]);

        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        return ['errcode' => 0, 'message' => '成功', 'content' => $host.$avater.$suffix];
    }

    /**
     * 修改用户名
     */
    public function changeUsername() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $username = I('nickname');
        if (empty($username)) {
            return ['errcode' => -100, 'message' => '请传入用户名'];
        }
        D("customer_log_model")->Log($customer_id, 3, "修改用户名，用户名为：". $username);
        D("user_model")->mySave(['customer_id' => $customer_id],['nickname' => $username]);
        return ['errcode' => 0, 'message' => '成功', 'content' => $username];
    }

    /**
     * 设置出生日期
     */
    public function changeBirthday() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $birthday = I('birthday');
        if (empty($birthday)) {
            return ['errcode' => -100, 'message' => '请传入出生日期'];
        }
        D("customer_log_model")->Log($customer_id, 3, "修改出生日期，出生日期为：". date("Y-m-d",$birthday));
        D("user_model")->mySave(['customer_id' => $customer_id],['birthday' => $birthday]);
        return ['errcode' => 0, 'message' => '成功', 'content' => $birthday];
    }

    /**
     * 设置性别
     * @return array
     */
    public function changeSex() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $type = (int)I('type');
        $sex = $type == 1 ? "男" : "女";
        D("customer_log_model")->Log($customer_id, 3, "修改性别，性别为：". $sex);
        D("user_model")->mySave(['customer_id' => $customer_id],['sex' => $sex]);
        return ['errcode' => 0, 'message' => '成功', 'content' => $sex];
    }

    /**
     * 可用优惠券列表
     * @return array
     */
    public function getCoupons() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        return D('user_model')->getCoupons($customer_id,1);
    }

    /**
     * 不可用优惠券列表
     * @return array
     */
    public function getUnableCoupons() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        return D('user_model')->getCoupons($customer_id,0);
    }

    /**
     * 余额
     * @return array
     */
    public function balance() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        return D('user_model')->balance($customer_id);
    }

    /**
     * 资金明细
     * @return array
     */
    public function finance() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }
        return D('user_model')->finance($customer_id,$page);
    }

    /**
     * 会员积分
     * @return array
     */
    public function shoppingCoin() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        return D('user_model')->shoppingCoin($customer_id);
    }

    /**
     * 会员积分明细
     * @return array
     */
    public function shoppingCoinDetail() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }
        return D('user_model')->shoppingCoinDetail($customer_id,$page);
    }

    /**
     * 积分
     * @return array
     */
    public function integration() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        return D('user_model')->integration($customer_id);
    }

    /**
     * 积分明细
     * @return array
     */
    public function integrationDetail() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }
        return D('user_model')->integrationDetail($customer_id,$page);
    }

    /**
     * 收藏列表
     * @return array
     */
    public function collection() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }
        return D('user_model')->collection($customer_id,$page);
    }

    /**
     * 取消收藏
     * @return array
     */
    public function cancelCollections() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $ids = I('ids');
        if (empty($ids)) {
            return ['errcode' => -100, 'message' => '请传入收藏信息'];
        }
        return D('user_model')->cancelCollections($customer_id,$ids);
    }

    /**
     * 我的关注
     * @return array
     */
    public function attention() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }
        return D('user_model')->attention($customer_id,$page);
    }

    /**
     * 取消关注
     * @return array
     */
    public function cancelAttentions() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $ids = I('ids');
        if (empty($ids)) {
            return ['errcode' => -100, 'message' => '请传入关注信息'];
        }
        return D('user_model')->cancelAttentions($customer_id,$ids);
    }

    /**
     * 我的足迹
     * @return array
     */
    public function footmark() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }

        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        $foots = M('trail')
            ->alias('t')
            ->where(['t.user_id' => $customer_id])
            ->join("goods g","g.goods_id = t.goods_id AND g.on_sale = 1 AND g.is_delete = 0")
            ->field("t.id as foot_id,t.date_add,g.goods_id,g.name,concat('$host',g.cover,'$suffix') as cover,g.shop_price as price,g.sale_count")
            ->order("t.date_add DESC")
            ->select();
        return ['errcode' => 0, 'message' => '成功', 'content' => $foots];
    }

    /**
     * 删除足迹
     * @return array
     */
    public function delFoots() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $ids = I('ids');
        if (empty($ids)) {
            return ['errcode' => -100, 'message' => '请传入足迹信息'];
        }
        return D('user_model')->delFoots($customer_id,$ids);
    }

    /**
     * 商家入驻
     * @return array
     */
    public function shopJoin() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }
        $company = I('company');
        if (empty($company)) {
            return ['errcode' => -100, 'message' => '请输入公司名'];
        }
        $province = I('province');
        if (empty($province)) {
            return ['errcode' => -101, 'message' => '请传入省'];
        }
        $city = I('city');
        if (empty($city)) {
            return ['errcode' => -102, 'message' => '请传入市'];
        }
        $district = I('district');
        if (empty($district)) {
            return ['errcode' => -103, 'message' => '请传入区'];
        }
        $address = I('address');
        if (empty($address)) {
            return ['errcode' => -104, 'message' => '请输入详细地址'];
        }
        $licence = I('licence');
        if (empty($licence)) {
            return ['errcode' => -105, 'message' => '请传入营业执照'];
        }
        $name = I('name');
        if (empty($name)) {
            return ['errcode' => -106, 'message' => '请传入姓名'];
        }
        $phone = I('phone');
        if (empty($phone)) {
            return ['errcode' => -107, 'message' => '请传入联系电话'];
        }
        if(!preg_match("/^1\d{10}$/", $phone)){
            return ['errcode' => -108, 'message' => '手机号格式错误'];
        }
        $shop_name = I('shop_name');
        if (empty($shop_name)) {
            return ['errcode' => -109, 'message' => '请传入店铺名'];
        }
        $wx_qq = I('wx_qq');
        $card_f = I('card_f');
        if (empty($card_f)) {
            return ['errcode' => -110, 'message' => '请传入身份证正面照'];
        }
        $card_b = I('card_b');
        if (empty($card_b)) {
            return ['errcode' => -111, 'message' => '请传入身份证背面照'];
        }

        $shop = M('seller_check')->where(['customer_id' => $customer_id, 'state' => ['neq',3]])->find();
        if (!empty($shop)) {
            return ['errcode' => -300, 'message' => '不能重复提交'];
        }

        //     对输入的省份进行判断
        $addressVerify = D('address_model')->verify($province,$city,$district);

        $data = [
            'customer_id' => $customer_id,
            'company_name' => $company,
            'province' => $addressVerify['province_id'],
            'city' => $addressVerify['city_id'],
            'district' => $addressVerify['area_id'],
            'address' => $address,
            'licence' => $licence,
            'contact_people_name' => $name,
            'phone' => $phone,
            'shop_name' => $shop_name,
            'qq_wx' => $wx_qq,
            'card_f' => $card_f,
            'card_b' => $card_b,
            'date_add' => time(),
            'date_upd' => time(),
        ];

        $id = M('seller_check')->add($data);
        if (empty($id)) {
            return ['errcode' => -200, 'message' => '添加失败'];
        }

        return ['errcode' => 0, 'message' => '添加成功'];
    }

    /**
     * 投诉
     * @return array
     */
    public function complain() {
        $customer_id = get_customer_id();
        if(empty($customer_id)){
            return ['errcode' => 99 , 'message' => '请传入用户信息'];
        }
        $title = I('title');
        if (empty($title)) {
            return ['errcode' => -100, 'message' => '请传入投诉主题'];
        }
        $content = I('content');
        if (empty($content)) {
            return ['errcode' => -101, 'message' => '请传入具体内容'];
        }
        return D('user_model')->complain($customer_id,$title,$content);
    }

    /**
     * 店铺佣金
     * @return array
     */
    public function commission() {
        $customer_id = get_customer_id();
        if(empty($customer_id)){
            return ['errcode' => 99 , 'message' => '请传入用户信息'];
        }
        return D('user_model')->commission($customer_id);
    }

    /**
     * 佣金明细
     * @return array
     */
    public function commissionDetail() {
        $customer_id = get_customer_id();
        if(empty($customer_id)){
            return ['errcode' => 99 , 'message' => '请传入用户信息'];
        }
        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }
        return D('user_model')->commissionDetail($customer_id,$page);
    }

    /**
     * 缴纳保证金
     * @return array
     */
    public function bail() {
        $customer_id = get_customer_id();
        if(empty($customer_id)){
            return ['errcode' => 99 , 'message' => '请传入用户信息'];
        }
        $type = I("type");

        $sum = I('account');

        $bail = \app\library\SettingHelper::get("shuaibo_bail",['money' => 10000,'url' => "http://" . get_domain() ."/wap/home/protocol"]);
        if ($sum != $bail['money']) {
            return ['errcode' => -100, 'message' => '请传入正确的金额'];
        }

        return D('user_model')->bail($customer_id,$type,$sum);
    }


    /**
     * 充值
     * @return array
     */
    public function recharge() {
        $customer_id = get_customer_id();
        if(empty($customer_id)){
            return ['errcode' => 99 , 'message' => '请传入用户信息'];
        }
        $type = I("type");
        if ($type == 1) {
            return ['errcode' => -200, 'message' => '不能使用余额支付'];
        }

        $sum = I('account');

        return D('user_model')->recharge($customer_id,$type,$sum);
    }

    /*
     * 提现
     */
    public function withdraw() {
        $customer_id = get_customer_id();
        if(!$customer_id){
            return ['errcode' => 99 , 'message' => '请重新登录'];
        }
        $money = round(I('money'),2);
        if ($money <= 0) {
            return ['errcode' => -103 , 'message' => '请输入提现金额'];
        }
        $pay_type = I('pay_type');
        if (empty($pay_type)) {
            return ['errcode' => -101 , 'message' => '请输入提现类型'];
        }
        $account = I("account");
        if(!$account){
            return ['errcode' => -101 , 'message' => '请输入账号'];
        }
        $realname = I("realname");
        if(!$realname){
            return ['errcode' => -102 , 'message' => '请输入真实姓名'];
        }
        $subbranch = I('subbranch');
        if ($pay_type == 1 && empty($subbranch)) {
            return ['errcode' => -102 , 'message' => '请输入开户支行'];
        }
        $type = I('type');
        if (empty($type)) {
            return ['errcode' => -104 , 'message' => '请输入类型'];
        }

        // 可提现佣金
        $condition = [
            'op1.finance_type' => 1,
            'op1.is_minus' => 2,
            'o.order_state' => ['in','4,5,7'],
            'cwo.state' => 1
        ];
        $condition['_string'] = "op1.customer_id = fo.customer_id";
        $sql1 = M('finance_op')
            ->alias("op1")
            ->where($condition)
            ->join("order o","o.order_sn = op1.order_sn")
            ->join('customer_withdraw_order cwo','cwo.order_sn = op1.order_sn')
            ->field("IFNULL(sum(real_amount),0)")
            ->buildSql();

        $commission = M("finance_op")
            ->alias("fo")
            ->join("seller_shopinfo ss","ss.customer_id = fo.customer_id")
            ->where(['fo.customer_id' => $customer_id])
            ->field("ifnull($sql1,0) as amount")
            ->group("fo.customer_id")
            ->find();

        $customer = M("customer")->where(['customer_id' => $customer_id])->field("active, phone, account, commission")->find();
        $customer['commission'] = $commission['amount'];

        if(!$customer || $customer['active'] == 0){
//            $seller_info = \app\library\SettingHelper::get("store_seller_info",["service_phone" => '15906716507','address' => '杭州市']);
            return ['errcode' => -106, 'message' => '抱歉，您无法执行此操作，如有疑问，请拨打客服电话'];
        }
//        if(!$customer['phone']){
//            return ['errcode' => -107 , 'message' =>'抱歉，您需先绑定手机号，方可提现'];
//        }
        if ($type == 1) {
            if ($customer['account'] - $money < 0) {
                return ['errcode' => -108 ,'message' =>'可提现余额不足'];
            }
        } elseif ($type == 2) {
            if ($customer['commission'] - $money < 0) {
                return ['errcode' => -108 ,'message' =>'可提现佣金不足'];
            }
            if ($customer['commission'] - $money != 0) {
                return ['errcode' => -108 ,'message' =>'佣金必须全额提现'];
            }
        } else {
            return ['errcode' => -104 , 'message' => '类型无法识别'];
        }

        $order_sn = createNo("customer_withdraw","order_sn", "CA");

        $data = [
            'order_sn' => $order_sn,
            'money' => $money,
            'date_add' => time(),
            'customer_id' => $customer_id,
            'account' => $account,
            'realname' => $realname,
            'subbranch' => $subbranch,
            'type' => $pay_type,
            'style' => $type,
        ];
        $id = M("customer_withdraw")->add($data);
        if ($type == 1) {
            M("customer")->where(['customer_id' => $customer_id])->setDec("account",$money);
        } elseif ($type == 2) {
            M("customer")->where(['customer_id' => $customer_id])->setDec("commission",$money);
            M("customer_withdraw_order")->where(['customer_id' => $customer_id,'state' => 1])->save(['state' => 2]);
        }

        return ['errcode' => 0, 'message' => '申请提现成功'];

        // 支付宝提现
//        return D("customer_withdraw")->withdraw($id, 0);
    }

    /**
    *是否实名认证
    */
    public function check_real_customer(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }

        $real_customer = M("real_customer")->where(["customer_id" => $customer_id])->field("*")->find();
        if (empty($real_customer)) {
            return ['errcode' => -103,'message' => '未进行实名认证'];
        }
        return ['errcode' => 0,'message' => '实名认证通过'];
    }

    /**
    *实名认证
    */
    public function authentication(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99, 'message' => '请重新登录'];
        }

        $name = I("name");
        $phone = I("phone");
        $trade_num = I("trade_num");
        $address = I("address");
        $arr = array('name' => $name,"phone" => $phone,"trade_num" => $trade_num,"address" => $address );
        foreach ($arr as $key => $value) {
            if (empty($value)) {
                return ['errcode' => -101,'message' => '参数:'.$key."为空"];
            }
        }
        if ($trade_num != "无") {
            if (!preg_match("/079/", substr($trade_num,0,3)) || strlen($trade_num) != 12) {
            return ['errcode' => -102,'message' => '交易商账号格式错误'];
            }
        }
        
        $arr['customer_id'] = $customer_id;
        $res = M("real_customer")->add($arr);
        if ($res === false) {
            return ['errcode' => -201,'message' => '认证失败'];
        }

        return ['errcode'=> 0,'message' => '认证成功'];
    }
    /**
    *分享
    */
    public function share(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99,'message' => '请重新登录'];
        }
        $customer = M("customer")->where(['customer_id' => $customer_id])->find();
        $data = array('title' => '帅柏商城','content'=>'来买吧','url' => 'http://www.baidu.com','user' => $customer_id,'cover' => $customer['avater']);
        return ['errcode' => 0,'message' => '请求成功','content' => $data];
    }

    /**
    *余额宝
    */
    public function rewardAmount(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99,'message' => '请重新登录'];
        }

        $reward = D("customer_model")->myFind(['customer_id' => $customer_id],"reward_amount,transfer_amount,is_frozen");
        if ($reward['is_frozen'] == 1){
            $reward['frozen_amount'] = $reward['reward_amount'] + $reward['transfer_amount'];
        }else{
            $reward['frozen_amount'] = 0;
        }
        unset($reward['is_frozen']);
        $page = I("page");
        if (empty($page)){
            $page = 1;
        }
        $detail = D("user_model")->yuebaoDetail($customer_id,$page);
        $reward['detail'] = $detail;

        return ['errcode' => 0,'message' => '请求成功','content' => $reward];
    }

    /**
    *余额宝充值
    */
    public function yuebaoCharge(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99,'message' => '请重新登录'];
        }

        $pay_type = I("pay_type");
        $amount = I("amount");

        if (in_array($pay_type,[1,6,7])) {
            return ['errcode' => -201,'message' => '不能使用该支付方式'];
        }
        $order = \app\library\order\OrderHelper::getInstance($pay_type);
        $order_sn = createNo("order_info","order_sn","PO");
        $order->setOrderType(6)
        ->setOrderNumber($order_sn)
        ->setSubject("余额宝充值"+$amount)
        ->setBody("余额宝充值"+$amount);

        $return = $order->get_init_order($customer_id,$amount);

        M("order_info")->add($order->getOrder());

        return ['errcode' => 0,'message' => '请求成功','content' => $order->getReturn()];
    }

    /**
    *余额宝提现
    **/
    public function yuebaoWithdraw(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99,'message' => '请重新登录'];
        }
        
        $account = I("account");
        $amount = I("amount");
        $realname = I("realname");
        $type = I("type");
        $subbranch = I("subbranch");
        $invoice = I("invoice");
        return D("user_model")->yuebaoWithdraw($customer_id,$account,$amount,$realname,$type,$subbranch,$invoice);
    }

    /**
    *余额宝转账
    **/
    public function yuebaoTransfer(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => -101,'message' => '请重新登录'];
        }

        $amount = I("amount");
        $user = I("user");
        if ($amount <= 0) {
            return ['errcode' => -201,'message' => '金额不正确'];
        }

        //查看转账人
        $field = verifyParam($user);

        $transfer_id = M("customer")->where([$field => $user])->getField("customer_id");
        if (empty($transfer_id)) {
            return ['errcode' => -202,'message' => '转账人不存在'];
        }

        return D("user_model")->transfer($customer_id,$amount,$transfer_id,$user);
    }


    /**
    *验证转账密码
    **/
    public function verifyTransfer(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => -101,'message' => '请重新登录'];
        }

        $transfer_pass = I("transfer_pass");

        $verify_pass = M("customer")->where(['customer_id' => $customer_id])->getField("transfer_passwd");
        if ($transfer_pass != $verify_pass) {
            return ['errcode' => -201,'message' => '密码错误'];
        }

        return ['errcode' => 0,'message' => '验证成功'];
    }

    /**
    *验证支付密码
    **/
    public function verifyPay(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => -101,'message' => '请重新登录'];
        }

        $pay_pass = I("pay_pass");

        $verify_pass = M("customer")->where(['customer_id' => $customer_id])->getField("pay_passwd");
        if ($pay_pass != $verify_pass) {
            return ['errcode' => -201,'message' => '密码错误'];
        }

        return ['errcode' => 0,'message' => '验证成功'];
    }


    /**
    *转账明细
    **/
    public function transferRecord(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => -101,'message' => '请重新登录'];
        }
        $transfer_amount = M("customer")->where(['customer_id' => $customer_id])->getField("transfer_amount");
        $page = I("page");
        if (empty($page)){
            $page = 1;
        }

        $record = D("user_model")->transferRecord($customer_id,$page);

        return ['errcode' => 0,'message' => '请求成功','content' => ['transfer_amount' => $transfer_amount,'detail' => $record]];
    }

    /**
    *鸿府积分明细
    **/
    public function hongfuDetail(){
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => -101,'message' => '请重新登录'];
        }
        $hongfu = M("customer")->where(['customer_id' => $customer_id])->getField("hongfu");
        $page = I("page");
        if (empty($page)){
            $page = 1;
        }

        $detail = D("user_model")->hongfuDetail($customer_id,$page);

        return ['errcode' => 0,'message' => '请求成功','content' => ['hongfu' => $hongfu,'detail' => $detail]];
    }

    /**
     * 手续费比例
     */
    public function withdrawServiceFee(){
        $service_fee = \app\library\SettingHelper::get("shuaibo_withdraw_fee",0.1);

        return ['errcode' => 0,'message' => '请求成功','content' => $service_fee];
    }

    /**
     * 余额宝明细
     */
    public function yuebaoDetail(){
        $customer_id = get_customer_id();
        if(empty($customer_id)){
            return ['errcode' => -101,'message' => '请重新登录'];
        }

        $page = I("page");
        if (empty($page)){
            $page = 1;
        }
        $detail = D("user_model")->singelYueDetail($customer_id,$page);

        return ['errcode' => 0,'message' => '请求成功','content' => ['detail' => $detail]];
    }
}