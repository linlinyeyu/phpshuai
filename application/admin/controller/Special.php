<?php
namespace app\admin\controller;

use app\admin\Admin;
class Special extends Admin{
	public function index(){
		$special_id = I("special_id");
		$activity = D("special")->getIndex($special_id);
		
		$this->assign("activity",$activity);
		$this->assign("special_id",$special_id);
		$this->display();
	}
	
	public function add(){
		$special_id = I("special_id");
		$special_images_id = I("special_images_id");
		$params = array();
		$data = array();
		if (IS_POST){
			!($data['sort'] = I("sort")) && $this->error("请传入排序");
			!($data['name'] = I("picture_name")) && $this->error("请传入名称");
			$data['action_id'] = I("action_id");
			if (!empty(I("goods_id"))){
				$params['goods_id'] = I("goods_id");
			}
			if (!empty(I("seller_id"))){
				$params['seller_id'] = I('seller_id');
			}
			
			$params = serialize($params);
			$data['params'] = $params;
			!($data['image'] = I("image")) && $this->error("请上传图片");
			$data['status'] = I("status");
			if ($special_images_id){
				$data['date_upd'] = time();
				$res = M("special_images")->where(['special_images_id' => $special_images_id])->save($data);
				if ($res === false){
					$this->error("操作失败");
				}
				$this->log("编辑专题图片_".$special_images_id);
				//如果为线下提货
				if ($special_id == 13) {
					$data = array('date_upd' => time(),'address' => $address);
					M("special_goods")->where(['special_id' => $special_id,'goods_id' => I("goods_id")])->save($data);
				}
				$this->success("操作成功",null,U("special/index",['special_id' => $special_id]));
			}else {
				$data['date_add'] = time();
				$data['date_upd'] = time();
				$data['special_id'] = $special_id;
				$res = M("special_images")->add($data);
				if ($res === false){
					$this->error("操作失败");
				}
				$this->log("添加专题图片_".$res);
				//如果为线下提货
				if ($special_id == 13) {
					$data = array('date_add' => time(),'date_upd' => time(),'goods_id' => I("goods_id"),'special_id' => $special_id);
					M("special_goods")->add($data);
				}
				$this->success("操作成功",null,U("special/index",['special_id' => $special_id]));
			}
		}else {
			if (!empty($special_images_id)){
				$special = D("special")->getSpecial($special_images_id);
				\app\library\ActionTransferHelper::get_action_params($special);
				//如果为线下提货
				if ($special_id == 13) {
					$address = M("special_goods")->where(['special_id' => $special_id,'goods_id' => $special['goods_id']])->getField("address");
					$special['address'] = $address;
				}
				
				$this->assign("special",$special);
				$this->assign("special_id",$special_id);
				$this->display();
			}else {
				$data = array('name' => "","action_id" => 0,
						"status" => 1,"sort" => '',"special_images_id" => '',"image" => '',
						'goods_name' => '','seller_name' => '','shop_name' => '','goods_id' => '','seller_id' => '','address' => '');
				$this->assign("special",$data);
				$this->assign("special_id",$special_id);
				$this->display();
			}
		}
	}
	
	public function del(){
		!($special_images_id = I("special_images_id")) && $this->error("请传入id");
		!($special_id = I("special_id")) && $this->error("请传入special_id");
		
		$res = D("special")->del($special_images_id);
		
		if($res === false){
			$this->error("操作失败");
		}
		$this->success("操作成功",null,U("special/index",['special_id' => $special_id]));
	}
	
	public function is_active(){
		!($id = I("id")) && $this->error("请传入id");
		$active = I("active");
		!($special_id = I("special_id")) && $this->error("请传入special_id");
		
		$res = M("special_images")->where(['special_images_id' => $id])->save(['status' => $active]);
		$res ? $json = array('status'=>1) : $json = array('status'=>0);
		$this->ajaxReturn($json);
	}
	
	public function goods(){
		!($special_id = I("special_id")) && $this->error("请传入special_id");
		$map = ['sg.special_id' => $special_id,'g.is_delete' => 0,'g.on_sale' => 1];
		$goods_name = I("goods_name");
		$min_date = I("min_date");
		$max_date = I("max_date");
		$goods_state = (int)I("goods_state");
		$seller_id = I("seller_id");
		if (!empty($goods_name)){
			$map['g.name'] = ['like','%'.$goods_name.'%'];
		}
		if (!empty($min_date)){
			$map['sg.date_add'] = ['egt',strtotime($min_date)];
		}
		if(!empty($max_date)){
			if (!empty($min_date)){
				$map['sg.date_add'][] = ['elt',strtotime($max_date)];
			}else {
				$map['sg.date_add'] = ['elt',strtotime($max_date)];
			}
		}
		if ($goods_state == 1){
			$map['sg.status'] = 1;
		}elseif ($goods_state == 2){
			$map['sg.status'] = 0;
		}
		if (!empty($seller_id)){
			$map['g.seller_id'] = $seller_id;
		}
		!($special_id = I("special_id")) && $this->error("请传入special_id");
		$goods = D("special")->getSpecialGoods($special_id,$map,"sg.sort ASC");
		
		$this->assign("goods",$goods['goods']);
		$this->assign('page',$goods['page']->show());
		$this->assign("special_id",$special_id);
		$this->display();
	}
	
	public function goodsAdd(){
		!($special_id = I("special_id")) && $this->error("请传入special_id");
		$special_goods_id = I("special_goods_id");
		if (IS_POST){
			!($data['goods_id'] = I("goods_id")) && $this->error("请传入商品");
//			!($data['quantity'] = I("quantity")) && $this->error("请传入库存");
			
			if ($special_id == 5){
				!($data['integral'] = I("integral")) && $this->error("请设置积分");
				$integral = M("goods")->where(['goods_id' => $data['goods_id']])->getField("max_integration");
				if ($integral < $data['integral']){
					$this->error("设置积分大于商品最大可使用积分");
				}
			}
			
			if ($special_id == 6){
				!($data['shopping_type'] = I("shopping_type")) && $this->error("请选择类型");
			}
			
			if ($special_id == 9){
				!($data['new_full_cut'] = I("new_full_cut")) && $this->error("请设置满减内容");
			}
			
//			$quantity = M("goods")->where(['goods_id' => $data['goods_id']])->getField("quantity");
//			if ($quantity < $data['quantity']){
//				$this->error("设置库存大于商品库存");
//			}
			$data['status'] = I("status");
            $data['sort'] = (int)I("sort");
            $data['image'] = !(I("image")) ? null : I("image");
			if ($special_goods_id){
				$goods =  M('special_goods')->where(['special_id' => $special_id, 'goods_id' => $data['goods_id']])->find();
				if (!empty($goods)) {
					$this->error("已添加该商品");
				}
				$data['date_upd'] = time();
				$res = M("special_goods")->where(['special_goods_id' => $special_goods_id])->save($data);
				if ($res === false){
					$this->error("操作失败");
				}
				$this->log("更新专题_".$special_id."商品_".$data['goods_id']);
				$this->success("操作成功",null,U("special/goods",['special_id' => $special_id]));
			}else {
				$data['date_add'] = time();
				$data['date_upd'] = time();
				$data['special_id'] = $special_id;
				$goods =  M('special_goods')->where(['special_id' => $special_id, 'goods_id' => $data['goods_id']])->find();
				if (!empty($goods)) {
					$this->error("已添加该商品");
				}
				$res = M("special_goods")->add($data);
				if($res === false){
					$this->error("操作失败");
				}
				$this->log("添加专题_".$special_id."商品_".$data['goods_id']);
				$this->success("操作成功",null,U("special/goods",['special_id' => $special_id]));
			}
		}else {
			if ($special_goods_id){
				$goods = M("special_goods")
				->alias("sg")
				->join("goods g",'g.goods_id = sg.goods_id')
				->join("seller_shopinfo ss",'ss.seller_id = g.seller_id',"left")
				->where(['sg.special_goods_id' => $special_goods_id])
				->field("g.goods_id,g.name,ifnull(ss.shop_name,'未找到关联店铺') as shop_name,sg.*")
				->find();
				
				$this->assign("goods",$goods);
				$this->assign("special_id",$special_id);
				$this->display();
			}else {
				$goods = array(
						'name' => '',
						'shop_name' => '',
						'goods_id' => '',
						'quantity' => '',
						'status' => 1,
						'special_goods_id' => 0,
						'integral' => '',
						'shopping_type' => 0,
						'new_full_cut' => '',
                        "goods_id" => "",
                        "image" => "",
                        "sort" => 0
				);
				$this->assign("goods",$goods);
				$this->assign("special_id",$special_id);
				$this->display();
			}
		}
	}
	
	public function goodsOff(){
		!($special_goods_id = I("special_goods_id")) && $this->error("请传入id");
		
		$res = M("special_goods")->where(['special_goods_id' => $special_goods_id])->save(['status' => 0]);
		
		if ($res === false){
			$this->error("操作失败");
		}
		$this->log("专题商品，special_goods_id_".$special_goods_id."关闭");
		$this->success("成功");
	}
	
	public function goodsOn(){
		!($special_goods_id = I("special_goods_id")) && $this->error("请传入id");
		
		$res = M("special_goods")->where(['special_goods_id' => $special_goods_id])->save(['status' => 1]);
		
		if ($res === false){
			$this->error("操作失败");
		}
		$this->log("专题商品,special_goods_id_".$special_goods_id."开启");
		$this->success("成功");
	}
	
	public function goodsDel(){
		!($special_goods_id = I("special_goods_id")) && $this->error("请传入id");
		
		$res = M("special_goods")->where(['special_goods_id' => $special_goods_id])->delete();
		
		if ($res === false){
			$this->error("操作失败");
		}
		
		$this->log("专题商品，special_goods_id_".$special_goods_id."被删除");
		$this->success("成功");
	}
	
	public function addStock(){
		!($id = I("rid")) && $this->error("请传入id");
		!($stock = I("addcount")) && $this->error("请传入库存");
		
		$goods = M("special_goods")
		->alias("sg")
		->join("goods g",'g.goods_id = sg.goods_id')
		->where(['special_goods_id' => $id])
		->field("sg.quantity as squantity,g.quantity")
		->find();
		
		if ($goods['squantity'] + $stock > $goods['quantity']){
			$this->error("增加库存大于商品原库存");
		}
		
		$res = M("special_goods")->where(['special_goods_id' => $id])->setInc("quantity",$stock);
		if($res === false){
			$this->error("增加失败");
		}
		$this->log("增加special_goods_id".$id."库存成功");
		$this->success("成功");
	}
	
	public function shop(){
		$map = ['ss.status' => 1];
		$min_date = I("min_date");
		$max_date = I("max_date");
		$seller_id = I("seller_id");
		$status = I("status");
		if (!empty($min_date)){
			$map['iss.date_add'] = ['egt',$min_date];
		}
		if (!empty($max_date)){
			if (!empty($min_date)){
				$map['iss.date_add'][] = ['elt',$max_date];
			}else {
				$map['iss.date_add'] = ['elt',$max_date];
			}
		}
		if (!empty($seller_id)){
			$map['iss.seller_id'] = $seller_id;
		}
		if ($status == 1){
			$map['iss.status'] = 1;
		}elseif ($status == 2){
			$map['iss.status'] = 0;
		}
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
		!($special_id = I("special_id")) && $this->error("请传入special_id");
		
		$count = M("intergral_shop")
		->alias("iss")
		->join("seller_shopinfo ss",'ss.seller_id = iss.seller_id')
		->where($map)
		->count();
		
		$page = new \com\Page($count,15);
		
		$shop = M("intergral_shop")
		->alias("iss")
		->join("seller_shopinfo ss",'ss.seller_id = iss.seller_id')
		->where($map)
		->field("ss.shop_name,concat('$host',iss.images,'$suffix') as image,iss.seller_id,iss.status,iss.date_add,iss.intergral_shop_id")
		->limit($page->firstRow.",".$page->listRows)
		->select();
		
		$this->assign("shop",$shop);
		$this->assign("page",$page->show());
		$this->assign("special_id",$special_id);
		$this->display();
	}
	
	public function shopAdd(){
		$special_id = I("special_id");
		$intergral_shop_id = I("intergral_shop_id");
		if(IS_POST){
			!($data['images'] = I("image")) && $this->error("请上传图片");
			!($data['seller_id'] = I("seller_id")) && $this->error("请填写店铺");
			!($data['sort'] = I("sort")) && $this->error("请传入排序");
			$data['status'] = I("status");
			if ($intergral_shop_id){
				$data['date_upd'] = time();
				$res = M("intergral_shop")->where(['intergral_shop_id' => $intergral_shop_id])->save($data);
				if ($res === false){
					$this->error("操作失败");
				}
				$this->log("更新积分优选店铺,seller_id=".$data['seller_id']);
				$this->success("操作成功",null,U("special/shop",['special_id' => $special_id]));
			}else {
				$data['date_add'] = time();
				$data['date_upd'] = time();
				$res = M("intergral_shop")->add($data);
				if ($res === false){
					$this->error("操作失败");
				}
				$this->log("新增积分优选店铺,seller_id=".$data['seller_id']);
				$this->success("操作成功",null,U("special/shop",['special_id' => $special_id]));
			}
		}else{
			if ($intergral_shop_id){
				$shop = M("intergral_shop")
				->alias("iss")
				->join("seller_shopinfo ss",'ss.seller_id = iss.seller_id')
				->where(['ss.status' => 1])
				->field("images as image,ss.shop_name,iss.status,iss.intergral_shop_id,sort,iss.seller_id")
				->find();
				
				$this->assign("shop",$shop);
				$this->assign("special_id",$special_id);
				$this->display();
			}else {
				$shop = array(
						'image' => '',
						'shop_name' => '',
						'status' => 1,
						'intergral_shop_id' => 0,
						'sort' => '',
						'seller_id' => ''
				);
				
				$this->assign("shop",$shop);
				$this->assign("special_id",$special_id);
				$this->display();
			}
		}
	}
	
	public function shopDel(){
		!($intergral_shop_id = I("intergral_shop_id")) && $this->error("请传入id");
			
		$res = M("intergral_shop")->where(['intergral_shop_id' => $intergral_shop_id])->delete();
			
		if ($res === false) {
			$this->error("操作失败");
		}
		$this->log("删除店铺,intergral_shop_id".$intergral_shop_id);
		$this->success("操作成功");
	}
	
	public function baicheng(){
		$special_id = I("special_id");
		$map = ['baicheng_apply' => ['neq',0],'status' => 1];
		$status = I("status");
		$seller_id = I("seller_id");
		$is_youxiu = I("is_youxiu");
		
		if ($status != null && $status != -1){
			$map['baicheng_apply'] = $status;
		}
		if (!empty($seller_id)){
			$map['seller_id'] = $seller_id;
		}
		if ($is_youxiu == 2){
			$map['is_youxiu'] = 0;
		}elseif ($is_youxiu == 1){
			$map['is_youxiu'] = 1;
		}
		
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
		
		$count = M("seller_shopinfo")
		->where($map)
		->count();
		
		$page = new \com\Page($count,15);
		$shop = M("seller_shopinfo")
		->where($map)
		->field("seller_id,shop_name,is_baicheng,baicheng_apply,concat('$host',shop_logo) as shop_logo,type,is_youxiu")
		->limit($page->firstRow.",".$page->listRows)
		->select();
		
		$this->assign("shop",$shop);
		$this->assign("page",$page->show());
		$this->assign("special_id",$special_id);
		$this->display();
	}
	
	public function baichengApply(){
		!($id = I("id")) && $this->error("请传入id");
		!($active = I("active")) && $this->error("请传入状态");
		
		$data['baicheng_apply'] = $active;
		$data['is_baicheng'] = 0;
		if ($active == 2){
			$data['is_baicheng'] = 1;
			$data['baicheng_agree_time'] = time();
		}
		$res = M("seller_shopinfo")->where(['seller_id' => $id])->save($data);
		$this->log("审核店铺百城申请,".$active == 2?"通过":"拒绝");
		$res ? $json = array('status'=>1) : $json = array('status'=>0);
		$this->ajaxReturn($json);
	}
	
	public function cancelYouxiu(){
		!($seller_id = I("seller_id")) && $this->error("请传入seller_id");
		
		$res = M("seller_shopinfo")->where(['seller_id' => $seller_id])->save(['is_youxiu' => 0]);
		if($res === false){
			$this->error("操作失败");
		}
		$this->log("取消优秀店铺，seller_id".$seller_id);
		$this->success("操作成功");
	}
	
	public function setYouxiu(){
		!($seller_id = I("seller_id")) && $this->error("请传入seller_id");
		
		$res = M("seller_shopinfo")->where(['seller_id' => $seller_id])->save(['is_youxiu' => 1]);
		if($res === false){
			$this->error("操作失败");
		}
		$this->log("设置优秀店铺，seller_id".$seller_id);
		$this->success("操作成功");
	}
	
	public function brand(){
		$map = ['ss.status' => 1];
		$min_date = I("min_date");
		$max_date = I("max_date");
		$status = I("status");
		$brand_name = I("brand");
		
		if (!empty($min_date)){
			$map['ss.date_add'] = ['egt',$min_date];
		}
		
		if (!empty($max_date)){
			if (!empty($min_date)){
				$map['ss.date_add'][] = ['elt',$max_date];
			}else {
				$map['ss.date_add'] = ['elt',$max_date];
			}
		}
		
		if ($status == 2){
			$map['ss.status'] = 0;
		}elseif ($status == 1){
			$map['ss.status'] = 1;
		}
		
		if (!empty($brand_name)){
			$map['ss.brand_name'] = ['like',"%".$brand_name."%"];
		}
		
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
		
		$count = M("seller_brand")
		->alias("sb")
		->join("seller_shopinfo ss",'ss.seller_id = sb.seller_id')
		->where($map)
		->count();
		
		$page = new \com\Page($count,15);
		
		$brand = M("seller_brand")
		->alias("sb")
		->join("seller_shopinfo ss",'ss.seller_id = sb.seller_id')
		->where($map)
		->field("sb.seller_id,ss.shop_name,concat('$host',sb.image,'$suffix') as image,sb.status,sb.brand_name,sb.date_add,sb.seller_brand_id")
		->limit($page->firstRow.",".$page->listRows)
		->select();
		
		$this->assign("brand",$brand);
		$this->assign("page",$page->show());
		$this->display();
	}
	
	public function addBrand(){
		$seller_brand_id = I("seller_brand_id");
		if(IS_POST){
			$data['image'] = I("image");
			$data['brand_name'] = I("brand_name");
			$data['status'] = I("status");
			$data['seller_id'] = I("seller_id");
			$count = M('seller_brand')->where(['seller_id' => $data['seller_id']])->count();
			if ($count > 0) {
			    $this->error("同一店铺只能设置一个品牌");
            }
			if ($seller_brand_id){
				$data['date_upd'] = time();
				$res = M("seller_brand")->where(['seller_brand_id' => $seller_brand_id])->save($data);
				if ($res === false){
					$this->error("操作失败");
				}
				$this->log("更新品牌,seller_id=".$data['seller_id']);
				$this->success("操作成功",null,U("special/brand"));
			}else {
				$data['date_add'] = time();
				$data['date_upd'] = time();
				$res = M("seller_brand")->add($data);
				if ($res === false){
					$this->error("操作失败");
				}
				$this->log("添加品牌,seller_id=".$data['seller_id']);
				$this->success("操作成功",null,U("special/brand"));
			}
		}else {
			if ($seller_brand_id){
				$brand = M("seller_brand")
				->alias("sb")
				->join("seller_shopinfo ss",'ss.seller_id = sb.seller_id')
				->where(['seller_brand_id' => $seller_brand_id])
				->field("ss.shop_name,ss.seller_id,sb.image,sb.status,sb.brand_name,sb.seller_brand_id")
				->find();
				
				$this->assign("brand",$brand);
				$this->display();
			}else {
				$brand = array(
						'shop_name' => '',
						'seller_id' => '',
						'image' => '',
						'status' => 1,
						'brand_name' => '',
						'seller_brand_id' => 0
				);
				
				$this->assign("brand",$brand);
				$this->display();
			}
		}
	}
	
	public function brandOff(){
		!($seller_brand_id = I("seller_brand_id")) && $this->error("请传入id");
		
		$res = M("seller_brand")->where(['seller_brand_id' => $seller_brand_id])->save(['status' => 0]);
		if($res === false){
			$this->error("操作失败");
		}
		$this->log("更新品牌,seller_brand_id=".$seller_brand_id);
		$this->success("操作成功");
	}
	
	public function brandOn(){
		!($seller_brand_id = I("seller_brand_id")) && $this->error("请传入id");
	
		$res = M("seller_brand")->where(['seller_brand_id' => $seller_brand_id])->save(['status' => 1]);
		if($res === false){
			$this->error("操作失败");
		}
		$this->log("更新品牌,seller_brand_id=".$seller_brand_id);
		$this->success("操作成功");
	}
	
	public function brandDel(){
		!($seller_brand_id = I("seller_brand_id")) && $this->error("请传入id");
		
		$res = M("seller_brand")->where(['seller_brand_id' => $seller_brand_id])->delete();
		if($res === false){
			$this->error("操作失败");
		}
		$this->log("删除品牌,seller_brand_id=".$seller_brand_id);
		$this->success("操作成功");
	}
	
	public function baichengArea(){
		$special_id = I("special_id");
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$area = M("area")->field("area_id,concat('$host',image) as image,area_name,status")->select();
		
		$this->assign("area",$area);
		$this->assign("special_id",$special_id);
		$this->display();
	}
	
	public function areaActive(){
		!($id = (int)I('post.id')) && $this->error('参数错误!','',U('special/baichengArea'));
		$active = (int)I('post.active');
		$area = M('area');
		$res = $area->where(array('area_id'=>$id))->save(array('status'=>$active));
		$res ? $json = array('status'=>1) : $json = array('status'=>0);
		$this->ajaxReturn($json);
	}
	
	public function areaEdit(){
		$area_id = I('area_id');
		$special_id = I("special_id");
		if (IS_POST){
			$data['image'] = I("image");
			$data['area_name'] = I("area_name");
			$data['date_upd'] = time();
			
			$res = M("area")->where(['area_id' => $area_id])->save($data);
			if ($res === false){
				$this->error("操作失败");
			}
			$this->success("操作成功",null,U("special/baichengArea",['special_id' => $special_id]));
		}else {
			$area = M("area")->where(['area_id' => $area_id])->find();
			
			$this->assign("area",$area);
			$this->assign("special_id",$special_id);
			$this->display();
		}
	}
}