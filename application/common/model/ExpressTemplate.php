<?php
namespace app\common\model;
use think\Model;
class ExpressTemplate extends Model{

	public function calculateExpress($goods_id , $address_id, $count = 1, $option_id = 0){
		if($goods_id <= 0){
			return 0;
		}
		if($count <= 0){
			return 0;
		}
		$goods = D("goods_model")->getGoods($goods_id);
		if(empty($goods) || $goods['dispatch_type'] == 0){
			return 0;
		}
		if(isset($goods['options']) && !empty($goods['options']) 
				&& $option_id > 0 ){
			foreach ($goods['options'] as $k => $o){
				if($o['id'] == $option_id){
					if(isset($o['weight'])){
						$goods['weight'] = $o['weight'];
					}
					break;
				}
			}
		}
		if($goods['dispatch_type'] == 1){
			return $goods['dispatch_fee'] * $count;
		}
		if($goods['dispatch_type'] != 2 || empty($goods['dispatch_id'])){
			return 0;
		}
		$records = $this->getAllRecords();
		$records = array_key_arr($records, "template_id");
		$template = isset($records[$goods['dispatch_id']]) ? $records[$goods['dispatch_id']][0] : null;
		if(empty($template)){
			return 0;
		}
		if (empty($address_id))
		{
			return $this->calculate($goods['weight'] * $count , $template['first'],$template['first_weight'], $template['additional'], $template['additional_weight']) ;
		}
		else
		{
			$address = D("address_model")->getAddress($address_id);
			if(empty($address) || empty($address['province_id'])){
				return $this->calculate($goods['weight'] * $count , $template['first'],$template['first_weight'], $template['additional'], $template['additional_weight']) ;
			}
			$province_id = $address['province_id'];
			
			$items = [];
			if(!empty($template['items'])){
				$items = unserialize($template['items']);
			}
			
			foreach ($items as $val)
			{
				$province_list = explode(",", $val['province_list']);
				if(in_array($province_id, $province_list)){
					return $this->calculate($goods['weight'] * $count , $val['first'], $val['first_weight'], $val['additional'], $val['additional_weight']) ;
				}
			}
			return $this->calculate($goods['weight'] * $count , $template['first'],$template['first_weight'], $template['additional'], $template['additional_weight']) ;
			
		}
	}

    public function calculateExpressWithoutAddressId($goods_id , $province_id, $count = 1, $option_id = 0){
        if($goods_id <= 0){
            return 0;
        }
        if($count <= 0){
            return 0;
        }
        $goods = D("goods_model")->getGoods($goods_id);
        if(empty($goods) || $goods['dispatch_type'] == 0){
            return 0;
        }
        if(isset($goods['options']) && !empty($goods['options'])
            && $option_id > 0 ){
            foreach ($goods['options'] as $k => $o){
                if($o['id'] == $option_id){
                    if(isset($o['weight'])){
                        $goods['weight'] = $o['weight'];
                    }
                    break;
                }
            }
        }
        if($goods['dispatch_type'] == 1){
            return $goods['dispatch_fee'] * $count;
        }
        if($goods['dispatch_type'] != 2 || empty($goods['dispatch_id'])){
            return 0;
        }
        $records = $this->getAllRecords();
        $records = array_key_arr($records, "template_id");
        $template = isset($records[$goods['dispatch_id']]) ? $records[$goods['dispatch_id']][0] : null;
        if(empty($template)){
            return 0;
        }
        if (empty($province_id))
        {
            return $this->calculate($goods['weight'] * $count , $template['first'],$template['first_weight'], $template['additional'], $template['additional_weight']) ;
        }
        else
        {
            if(empty($province_id)){
                return $this->calculate($goods['weight'] * $count , $template['first'],$template['first_weight'], $template['additional'], $template['additional_weight']) ;
            }

            $items = [];
            if(!empty($template['items'])){
                $items = unserialize($template['items']);
            }

            foreach ($items as $val)
            {
                $province_list = explode(",", $val['province_list']);
                if(in_array($province_id, $province_list)){
                    return $this->calculate($goods['weight'] * $count , $val['first'], $val['first_weight'], $val['additional'], $val['additional_weight']) ;
                }
            }
            return $this->calculate($goods['weight'] * $count , $template['first'],$template['first_weight'], $template['additional'], $template['additional_weight']) ;

        }
    }
	
	public function calculate($total_weight, $first, $first_weight, $additional, $additional_weight)
	{
		if ($total_weight <= $first_weight)
		{
			return $first;
		}
		if ($additional_weight <= 0) {
            $remain = 0;
        } else {
            $remain = ceil(($total_weight - $first_weight) / $additional_weight);
        }
		return $first + $additional * $remain;
	}
}