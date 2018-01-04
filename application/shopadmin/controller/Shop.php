<?php
namespace app\shopadmin\controller;

use app\shopadmin\Admin;
class Shop extends Admin{
	public function index(){
		$seller_id = session("sellerid");
		if (empty($seller_id)){
			$this->error("请重新登录");
		}
		$shop = D("shop")->getShopInfo($seller_id);
		
		$this->assign("shop",$shop);
		$this->display();
	}
	
	//App轮播图
	public function banner(){
		$seller_id = session("sellerid");
		if (empty($seller_id)){
			$this->error("请重新登录");
		}
		$banner = D("shop")->getBanner($seller_id);
		$this->assign("banner",$banner);
		$this->display();
	}
	
	public function addBanner(){
		$seller_id = session("sellerid");
		if (empty($seller_id)){
			$this->error("请重新登录");
		}
		$id = I("id");
		if (IS_POST){
			$data['name'] = I("name");
			!($data['img_url'] = I("image")) && $this->error("请上传图片");
			$data['action_id'] = I("action_id");
			$data['img_order'] = I("img_order");
			$data['status'] = I("status");
			!($goods_id = I("goods_id")) && $this->error("请选择商品");
			$params['goods_id'] = I("goods_id");
			$data['params'] = serialize($params);
			$data['seller_id'] = session("sellerid");
			
			if ($id){
				$data['date_upd'] = time();
				$res = M("seller_shopslide")->where(['id' => $id])->save($data);
				if ($res === false){
					$this->error("操作失败");
				}
				$this->success("操作成功",null,U("shop/banner"));
			}else {
				$data['date_add'] = time();
				$data['date_upd'] = time();
				$res = M("seller_shopslide")->add($data);
				if ($res === false){
					$this->error("操作失败");
				}
				$this->success("操作成功",null,U("shop/banner"));
			}
		}else {
			if ($id){
				$banner = M("seller_shopslide")
				->alias("ss")
				->join("action a",'a.action_id = ss.action_id')
				->where(['ss.id' => $id])
				->field("ss.name,ss.img_url,a.action_id,ss.params,ss.img_order,ss.status,ss.id")
				->find();
				
				\app\library\ActionTransferHelper::get_action_params($banner);
							
				$this->assign("banner",$banner);
				$this->display();
			}else {
				$banner = array(
						'name' => '',
						'img_url' => '',
						'action_id' => 0,
						'goods_id' => '',
						'img_order' => '',
						'status' => 1,
						'id' => 0,
						'goods_name' => ''
				);
				
				$this->assign("banner",$banner);
				$this->display();
			}
		}
	}
	
	public function is_active(){
		!($id = I("id")) && $this->error("请传入id");
		$active = I("active");
		
		$res = M("seller_shopslide")->where(array('id'=>$id))->save(array('status'=>$active));
		$res ? $json = array('status'=>1) : $json = array('status'=>0);
		$this->ajaxReturn($json);
	}
	
	public function delBanner(){
		!($id = I("id")) && $this->error("请传入id");
		
		$res = M("seller_shopslide")->where(array('id'=>$id))->delete();
		$res ? $this->success("成功"):$this->error("失败");
	}
	
	public function settings(){
		$seller_id = session("sellerid");
		if (empty($seller_id)){
			$this->error("请重新登录");
		}
		if (IS_POST){
			$data['shop_header'] = I("shop_header");
			$data['shop_title'] = I("shop_title");
            $data['shop_name'] = I("shop_name");
			$data['shop_logo'] = I("shop_logo");
			$data['action_id'] = I("action_id");
			$data['province'] = I("province");
            $data['city'] = I("city");
            $data['area_id'] = I("district");
            $data['address'] = I("address");
            $shop_address = M('zone')
                ->alias("z1")
                ->join("zone z2","z2.pid = z1.zone_id")
                ->join("zone z3","z3.pid = z2.zone_id")
                ->where(["z1.zone_id" => $data['province'], 'z2.zone_id' => $data['city'], 'z3.zone_id' => $data['area_id']])
                ->field("concat(z1.name,z2.name,z3.name) as address")
                ->find();
            $data['shop_address'] = $shop_address['address'].$data['address'];
			$goods_id = I("goods_id");
			if (!empty($goods_id)){
				$data['params'] = serialize(['goods_id' => $goods_id]);
			}
			$res = M("seller_shopinfo")->where(['seller_id' => $seller_id])->save($data);
			if ($res === false){
				$this->error("操作失败");
			}
			$this->success("操作成功");
		}else {
			$seller = M("seller_shopinfo")
					->where(['seller_id' => session("sellerid")])
					->field("action_id,params,shop_name,shop_title,shop_logo,shop_header,IFNULL(province,0) as province,IFNULL(city,0) as city,IFNULL(area_id,0) as district,address")
					->find();
			\app\library\ActionTransferHelper::get_action_params($seller);
			if (empty($seller['goods_name'])){
				$seller['goods_name'] = '';
				$seller['goods_id'] = 0;
				$seller['action_id'] = 0;
			}

            $all_district = M("zone")
                ->alias('z')
                ->where(['z.level'=>3])
                ->join("zone zo","zo.zone_id=z.pid")
                ->join("zone zoo","zoo.zone_id=zo.pid")
                ->field("z.zone_id,z.pid,z.name,zo.name as pname,zo.zone_id as pzone_id,zo.pid as ppid,zoo.name as ppname,zoo.zone_id as ppzone_id")
                ->select();
            $citys = [];
            foreach($all_district as $district){
                if(!isset($citys[$district['pzone_id']])){
                    $citys[$district['pzone_id']] = ['id' => $district['pzone_id'], 'name' => $district['pname'], 'ppzone_id' => $district['ppzone_id'], 'ppname' => $district['ppname'], 'districts' => []];
                }

                $citys[$district['pzone_id']]['districts'][$district['zone_id']] = ['id' => $district['zone_id'], 'name' => $district['name']];
            }

            $provinces = [];

            foreach($citys as $city){
                if(!isset($provinces[$city['ppzone_id']])){
                    $provinces[$city['ppzone_id']] = ['id' => $city['ppzone_id'], 'name' => $city['ppname'], 'cities' => []];
                }

                $provinces[$city['ppzone_id']]['cities'][$city['id']] = ['id' => $city['id'], 'name' => $city['name'] ,'districts' => $city['districts']];
            }

            $this->assign("seller",$seller);
            $this->assign("province" , $seller['province']);
            $this->assign("city" ,$seller['city']);
            $this->assign("district" ,$seller['district']);
            $this->assign("provinces",$provinces);
			$this->display();
		}
	}
	
	//申请百城
	public function baichengApply(){
		$seller_id = session("sellerid");
		if(empty($seller_id)){
			$this->error("请重新登录");
		}
		
		$res = M("seller_shopinfo")->where(['seller_id' => $seller_id])->save(['baicheng_apply' => 1]);
		
		if ($res === false){
			$this->error("申请失败");
		}
		$this->success("申请成功",null,U("shop/index"));
	}
}