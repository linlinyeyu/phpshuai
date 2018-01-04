<?php
namespace app\common\model;
use think\Model;
use app\library\ActionTransferHelper;
class AddressModel extends Model{
    //新增
    public function myAdd($data) {
        return $this->add($data);
    }

    //更新
    public function mySave($condition,$data){
        return $this->where($condition)->save($data);
    }

    //查找
    public function myFind($condtion,$field="*"){
        return $this->field($field)->where($condtion)->find();
    }

    //删除
    public function myDel($condtion){
        return $this->where($condtion)->delete();
    }

    /**
     * 获取所有地址
     * @param $customer_id
     * @return mixed
     */
    public function getAllAddress($customer_id){
        $address = $this->where(['customer_id' => $customer_id, 'active' =>1])
            ->field("address_id,name,phone,province,city,district,address,postcode,tel,status")
            ->order("status desc,date_add desc")
            ->select();
        return $address;
    }

    /**
     * 验证
     */
    public function verify($province,$city,$district) {
        $condition = array(
            'z1.pid' => 0,
            'z1.name' => $province
        );
        $address = M("zone")
            ->alias("z1")
            ->join("__ZONE__ z2","z1.zone_id = z2.pid and z2.name = '$city'","LEFT")
            ->join("__ZONE__ z3", "z2.zone_id = z3.pid and z3.name = '$district'","LEFT")
            ->field('z1.zone_id as province_id, z2.zone_id as city_id , z3.zone_id as area_id')
            ->where($condition)
            ->find();
        if(empty($address)){
            $address = array('province_id' => null,
                'city_id' => null,
                'area_id' => null);
        }
        return $address;
    }



    /**
     * 获取地址
     */
    public function getAddress($address_id, $value = []){
        $address = S("shuaibo_address_" . $address_id);
        if(empty($address)){
            $address = $this->setAddress($address_id);
            S("shuaibo_address_" . $address_id);
        }
        if(empty($address)){
            return null;
        }

        $address = unserialize($address);

        $result = [];
        if(!empty($value)){
            foreach ($value as $v){
                if(isset($address[$v])){
                    $result[$v] = $address[$v];
                }
            }
        }else{
            $result = $address;
        }

        return $result;
    }

    public function setAddress($address_id){
        $address = $this->where(['address_id' => $address_id])->find();

        if(empty($address)){
            return null;
        }
        return serialize($address);

    }








	public function detaile_getalladdress($customer_id){
		$address = $this
		->where(['customer_id' => $customer_id, 'active' =>1])
		->order("status desc,date_add desc")
		->select();
		return $address;
	}
	public function addaddress($customer_id , $id=0){
		$address = null;
		if($id > 0){
			$address = $this->where(['customer_id' => $customer_id, 'address_id' => $id])->find();
		}
		if(empty($address)){
			$address = $this->getEmptyFields();
		}
		return $address;
	}
	
	public function updateAddress($address_id , $value = []){
		$address = $this->getAddress($address_id);
		if(!empty($address) && !empty($value)){
			foreach ($value as $k => $v){
				$address[$k] = $v;
			}
		}
		S("bear_address_" . $address_id, serialize($address));
	}

}