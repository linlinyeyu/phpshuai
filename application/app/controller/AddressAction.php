<?php
namespace app\app\controller;
class AddressAction{

    /**
     * 获取地址列表
     */
    public function getAddress() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99 , 'message' => '请重新登录'];
        }
        $res = D('address_model')->getAllAddress($customer_id);
//        if (count($res) <= 0) {
//            return ['errcode' => -100, 'meaasge' => '抱歉，您还未添加地址'];
//        }
        return ['errcode' => 0, 'message' => '成功', 'content' => $res];
    }

    /**
     * 存储地址
     */
    public function saveAddress() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99 , 'message' => '请重新登录'];
        }
        $province = I("province");
        $city = I("city");
        $district = I("district");
        $name = I("name");
        $phone = I("phone");
        $status = (int)I("status");
        $detail = I("address");
        $postcode = I('postcode');
        $tel = I('tel');
// 对用户地址信息进行验证，需要输入省份，市，区，收货人，电话，编码，详细地址
        if(empty($province)){
            $res['message'] = "请输入省份";
            $res['errcode'] = -101;
            return $res;
        }

        if(empty($city)){
            $res['message'] = "请输入市";
            $res['errcode'] = -102;
            return $res;
        }
        if(empty($district)){
            $res['message'] = "请输入区";
            $res['errcode'] = -103;
            return $res;
        }
        $province = addslashes($province);

        $city = addslashes($city);

        $district = addslashes($district);


        if(empty($name)){
            $res['message'] = "请输入收货人";
            $res['errcode'] = -104;
            return $res;
        }

        if(empty($phone)){
            $res['message'] = "请输入电话";
            $res['errcode'] = -105;
            return $res;
        }

        if(!preg_match("/^1\d{10}$/", $phone)){
            $res['message'] = "手机号格式错误";
            $res['errcode'] = -402;
            return $res;
        }

        if(empty($detail)){
            $res['message'] = "请输入详细地址";
            $res['errcode'] = -107;
            return $res;
        }
//     对输入的省份进行判断
        $address = D('address_model')->verify($province,$city,$district);

// 		默认为0
        if($status == 1){
            D('address_model')->mySave(['customer_id' => $customer_id],['status' => 0]);
        }
        $customer_address = array(
            'name' => $name,
            'phone' => $phone,
            'province_id'=> $address['province_id'],
            'province' => $province,
            'city_id' => $address['city_id'],
            'city' => $city,
            'district_id' => $address['area_id'],
            'district' => $district,
            'status' => $status,
            'address' => $detail,
            'date_upd' => time(),
            'postcode' => $postcode,
            'tel' => $tel,
        );
// 添加或更新用户信息
        $id = I("address_id");
        if(empty($id)){
            $customer_address['date_add'] = time();
            $customer_address['customer_id'] = $customer_id;
            D("address_model")->myAdd($customer_address);
        }else{
            D("address_model")->mySave(['address_id' => $id,'customer_id' => $customer_id],$customer_address);
        }
// 		返回信息
        return ['errcode' => 0, 'message' => '成功'];
    }

    /**
     * 删除地址
     */
    public function delAddress() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99 , 'message' => '请重新登录'];
        }
        $address_id = I('address_id');
        if (empty($address_id)) {
            return ['errcode' => -100, 'message' => '请传入地址信息'];
        }
        D('address_model')->myDel(['address_id' => $address_id,'customer_id' => $customer_id]);
        return ['errcode' => 0, 'message' => '删除成功'];
    }

    /**
     * 设置默认地址
     */
    public function defaultAddress() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99 , 'message' => '请重新登录'];
        }
        $address_id = I('address_id');
        if (empty($address_id)) {
            return ['errcode' => -100, 'message' => '请传入地址信息'];
        }
        D('address_model')->mySave(['customer_id' => $customer_id],['status' => 0]);
        D('address_model')->mySave(['address_id' => $address_id,'customer_id' => $customer_id],['status' => 1]);
        return ['errcode' => 0, 'message' => '成功'];
    }

    /**
     * 获取单个地址
     * @return array
     */
    public function getOneAddress() {
        $customer_id = get_customer_id();
        if (empty($customer_id)) {
            return ['errcode' => 99 , 'message' => '请重新登录'];
        }
        $address_id = I('address_id');
        if (empty($address_id)) {
            return ['errcode' => -100, 'message' => '请传入地址信息'];
        }
        $res = D('address_model')->myFind(['address_id' => $address_id],"address_id,name,phone,province,city,district,address,postcode,tel,status");
        return ['errcode' => 0, 'message' => '获取地址成功', 'content' => $res];
    }










/**
 * 删除某个用户的地址信息
*/
	public function Delete(){
		$res = array(
			'message' => '删除成功',
			'errcode' => 0);
		$address_id = I("address_id");
		
		$customer_id = get_customer_id();
		
		if(empty($customer_id)){
			return ['errcode' => 99 , 'message' => '请传入用户信息'];
		}
// 		是否有地址信息传入
		if(empty($address_id)){
			$res['message'] = "请传入地址";
			$res['errcode'] = -101;
			return $res;
		}
		D("address")
		->where(array("address_id" => $address_id,"customer_id" => $customer_id))
		->save(['active' => 0]);
		
		D("address")->updateAddress($address_id, ['active' => 0]);
//      返回信息
		return $res;
	}

/**
 * 获取所有地址信息
*/
	public function GetAllAddress(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['message' => '请传入用户信息', 'errcode' => 99];
		}
		$address=D("address")->getalladdress($customer_id);
		return ['errcode'=>0,'message'=>'请求成功','content'=>$address];
	}
/**
 * 获取单个地址信息
 */
/*
	public function GetOneAddress(){
		$res = array(
				'message' => '请求成功',
				'errcode' => 0
			);
		$customer_id = get_customer_id();
		$customer_address_id = I("address_id");
// 		是否有用户信息传入
		if($customer_id <= 0){
			$res['message'] = "请传入用户信息";
			$res['errcode'] = 99;
			return $res;
		}
// 		是否传入地址
		if(empty($customer_address_id)){
			$res['message'] = "请传入地址";
			$res['errcode'] = -101;
			return $res;
		}

		$res['content'] = M("address")
		->where(array("address_id" => $customer_address_id))
		->find();
// 		返回信息
		return $res;
	}
*/
	//选择收货地址
	public function detaile_getalladdress(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['message' => '请传入用户信息', 'errcode' => 99];
		}
		$address=D("address")->detaile_getalladdress($customer_id);
		return ['errcode'=>0,'message'=>'请求成功','content'=>$address];
	}
	public function addaddress(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['message' => '请传入用户信息', 'errcode' => 99];
		}
		$id = (int)I("id");
		$address=D("address")->addaddress($customer_id, $id);
		return ['errcode'=>0,'message'=>'请求成功','content'=>$address];
	}
	public function editStatus(){
		$customer_id = get_customer_id();
		if($customer_id <= 0){
			return ['message' => '请传入用户信息', 'errcode' => 99];
		}
		$address_id = (int)I("address_id");
		if($address_id <= 0){
			return ['message' => '请传入地址信息', 'errcode' => -111]; 
		}
		M("address")->where(['customer_id' => $customer_id,'status' => 1])->save(['status' => 0]);
		
		M("address")->where(['customer_id' => $customer_id,'address_id' => $address_id])->save(['status' => 1]);
		return ['errcode' => 0 ,'message' =>'操作成功'];
	}
}