<?php
namespace app\admin\controller;
use app\admin\Admin;
class Goods extends Admin
{
	public function index()
	{
	    $map['g.is_delete'] = 0;
	    $map['g.goods_type'] = 1;
	    $map['g.apply_status'] = 2;
	    $goods_number = I("get.goods_number");
	    $goods_name = I("get.goods_name");
	    $min_date = I("get.min_date");
	    $max_date = I("get.max_date");
	    $goods_state=I("get.goods_state");
	    $category_id = (int)I("category_id");
	    $seller_id = (int)I("seller_id");
	    if(!empty($goods_number)){
	        $map['g.goods_id']=['like',$goods_number."%"];
	    }
	    if($goods_state==1){
	    	$map['g.on_sale']=['eq',$goods_state];
	    }else if($goods_state==2){
	    	$map['g.on_sale']=['eq',0];
	    }
	    if(!empty($goods_name)){
	        $map['g.name'] = ['like',"%" . $goods_name . "%"];
	    }
	    if(!empty($min_date)){

	        $map['g.date_add'] = ['egt', strtotime($min_date)];
	    }
	    if(!empty($max_date)){
	        if (!empty($map['g.date_add']) ) {
	            $map['g.date_add'][] =  ['elt', strtotime($max_date) ];
	        }else{
	            $map['g.date_add'] = ['elt', strtotime($max_date) ];
	        }
	    }
	    if(!empty($seller_id)){
	    	$map['g.seller_id'] = $seller_id;
	    }

		$count=M('goods')
		->alias('g')
		->where($map);
		if($category_id > 0){
			$count = $count->join("category_goods cg","cg.goods_id = g.goods_id and cg.category_id = $category_id");
		}
		$count = $count->count();

		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$page = new \com\Page($count, 15);
		$lists = M('goods')
		->alias('g')
		->join("seller_shopinfo ss",'ss.seller_id = g.seller_id',"left")
		->field("g.*, concat('$host', g.cover) as image_url,ifnull(ss.shop_name,'未找到店铺') as shop_name")
		->where($map)
		->group("g.goods_id")
		->order("g.date_add desc")
		->limit($page->firstRow . ',' . $page->listRows);
		if($category_id > 0){
			$lists = $lists->join("category_goods cg","cg.goods_id = g.goods_id and cg.category_id = $category_id");
		}
		$lists = $lists->select();
		$Node = M("category")->where(['active' => 1])->select();
		$array = array();
		foreach($Node as $k => $r) {
			$r['id']         = $r['category_id'];
			$r['title']      = $r['name'];
			$r['name']       = $r['name'];
			$r['disabled'] = false;
			$array[$r['category_id']] = $r;
		}
		$str  = "<option value='\$id' \$selected \$disabled >\$spacer \$title</option>";
		$Tree = new \com\Tree();
		$Tree->init($array);
		$select_categorys = $Tree->get_tree(0, $str, $category_id);
		$this->assign('select_categorys',$select_categorys);
		$this->assign('page',$page->show());

		$this->assign('lists',$lists);
		$this->display();
	}

	public function excel() {
        $map['g.is_delete'] = 0;
        $map['g.goods_type'] = 1;
        $map['g.apply_status'] = 2;
        $goods_number = I("get.goods_number");
        $goods_name = I("get.goods_name");
        $min_date = I("get.min_date");
        $max_date = I("get.max_date");
        $goods_state=I("get.goods_state");
        $category_id = (int)I("category_id");
        $seller_id = (int)I("seller_id");
        if(!empty($goods_number)){
            $map['g.sku']=['like',$goods_number."%"];
        }
        if($goods_state==1){
            $map['g.on_sale']=['eq',$goods_state];
        }else if($goods_state==2){
            $map['g.on_sale']=['eq',0];
        }
        if(!empty($goods_name)){
            $map['g.name'] = ['like',"%" . $goods_name . "%"];
        }
        if(!empty($min_date)){

            $map['g.date_add'] = ['egt', strtotime($min_date)];
        }
        if(!empty($max_date)){
            if (!empty($map['g.date_add']) ) {
                $map['g.date_add'][] =  ['elt', strtotime($max_date) ];
            }else{
                $map['g.date_add'] = ['elt', strtotime($max_date) ];
            }
        }
        if(!empty($seller_id)){
            $map['g.seller_id'] = $seller_id;
        }

        $lists = M('goods')
            ->alias('g')
            ->join("seller_shopinfo ss",'ss.seller_id = g.seller_id',"left")
            ->field("g.goods_id,g.name,ifnull(ss.shop_name,'未找到店铺') as shop_name,g.shop_price,g.max_integration,g.max_shopping_coin,g.manage_fee,g.pv,g.sale_count")
            ->where($map)
            ->group("g.goods_id");
        if($category_id > 0){
            $lists = $lists->join("category_goods cg","cg.goods_id = g.goods_id and cg.category_id = $category_id");
        }
        $lists = $lists->select();

        $filename="商品列表";
        $headArr=array("商品ID","商品名称","商店名称","商城售价","积分","购物积分","运营服务费","PV费率" ,"销售数量");
        $this->getExcel($filename,$headArr,$lists);
    }

	//审核列表
	public function applyIndex(){
		$map['g.is_delete'] = 0;
		$map['g.goods_type'] = 1;
		$map['g.apply_status'] = 1;
		$goods_number = I("get.goods_number");
		$goods_name = I("get.goods_name");
		$min_date = I("get.min_date");
		$max_date = I("get.max_date");
		$category_id = (int)I("category_id");
		$seller_id = (int)I("seller_id");
		if(!empty($goods_number)){
			$map['g.sku']=['like',$goods_number."%"];
		}
		if(!empty($goods_name)){
			$map['g.name'] = ['like',"%" . $goods_name . "%"];
		}
		if(!empty($min_date)){
			$map['g.date_add'] = ['egt', strtotime($min_date)];
		}
		if(!empty($max_date)){
			if (!empty($map['g.date_add']) ) {
				$map['g.date_add'][] =  ['elt', strtotime($max_date) ];
			}else{
				$map['g.date_add'] = ['elt', strtotime($max_date) ];
			}
		}
		if(!empty($seller_id)){
			$map['g.seller_id'] = $seller_id;
		}
		
		$count=M('goods')
		->alias('g')
		->where($map);
		if($category_id > 0){
			$count = $count->join("category_goods cg","cg.goods_id = g.goods_id and cg.category_id = $category_id");
		}
		$count = $count->count();
		
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$page = new \com\Page($count, 15);
		$lists = M('goods')
		->alias('g')
		->join("seller_shopinfo ss",'ss.seller_id = g.seller_id',"left")
		->field("g.*, concat('$host', g.cover) as image_url,ifnull(ss.shop_name,'未找到店铺') as shop_name")
		->where($map)
		->group("g.goods_id")
		->order("g.date_add desc")
		->limit($page->firstRow . ',' . $page->listRows);
		if($category_id > 0){
			$lists = $lists->join("category_goods cg","cg.goods_id = g.goods_id and cg.category_id = $category_id");
		}
		$lists = $lists->select();
		$Node = M("category")->where(['active' => 1])->select();
		$array = array();
		foreach($Node as $k => $r) {
			$r['id']         = $r['category_id'];
			$r['title']      = $r['name'];
			$r['name']       = $r['name'];
			$r['disabled'] = false;
			$array[$r['category_id']] = $r;
		}
		$str  = "<option value='\$id' \$selected \$disabled >\$spacer \$title</option>";
		$Tree = new \com\Tree();
		$Tree->init($array);
		$select_categorys = $Tree->get_tree(0, $str, $category_id);
		$this->assign('select_categorys',$select_categorys);
		$this->assign('page',$page->show());
		
		$this->assign('lists',$lists);
		$this->display();
	}
	
	//审核通过
	public function agree(){
		!($goods_id = I('goods_id')) && $this->error("请传入id");
		$manage_fee = I("manage_fee");
		$pv = I("pv");
		
		$res = M("goods")->where(['goods_id' => $goods_id])->save(['apply_status' => 2,'manage_fee' => $manage_fee,'pv' => $pv]);
		if ($res === false){
			$this->error('失败');
		}
		$this->log("审核商品".M("goods")->where(['goods_id'=> $goods_id])->getField("name")."通过");
		$this->success("成功");
	}
	
	//审核拒绝
	public function reject(){
		!($goods_id = I('goods_id')) && $this->error("请传入id");
        $reply = I("reply");

        $res = M("goods")->where(['goods_id' => $goods_id])->save(['apply_status' => 3,'apply_reply' => $reply]);
		if ($res === false){
			$this->error('失败');
		}
		$this->log("拒绝商品".M("goods")->where(['goods_id'=> $goods_id])->getField("name")."通过");
		$this->success("成功");
	}

//秒杀商品
	public function buynow(){
        !($special_id = I("special_id")) && $this->error("请传入special_id");
        $map['g.is_delete'] = 0;
		$map['sg.special_id'] = 1;
		$goods_number = (int)I("get.goods_number");
		$goods_name = I("get.goods_name");
		$min_date = I("get.min_date");
		$max_date = I("get.max_date");
		$goods_state=I("get.goods_state");
		if(!empty($goods_number)){
			$map['sg.goods_id']=$goods_number;
		}
		if($goods_state==1){
			$map['g.on_sale']=['eq',$goods_state];
		}else if($goods_state==2){
			$map['g.on_sale']=['eq',0];
		}
		if(!empty($goods_name)){
			$map['g.name'] = ['like',"%" . $goods_name . "%"];
		}
		if(!empty($min_date)){
			$map['sg.date_start'] = ['egt', strtotime($min_date)];
		}
		if(!empty($max_date)){
			$map['sg.date_end'] = ['elt', strtotime($max_date)];
		}

		$count=M('special_goods')
		->alias('sg')
		->join("goods g","g.goods_id = sg.goods_id")
		->where($map);

		$count = $count->count();

		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$page = new \com\Page($count, 15);
		$lists = M('special_goods')
		->alias('sg')
		->join("goods g",'g.goods_id = sg.goods_id')
		->field("g.goods_id,g.stock_type,g.on_sale,g.name,g.shop_price, concat('$host', IFNULL(sg.image,g.cover)) as image_url,
				sg.*")
		->where($map)
		->group("g.goods_id")
		->order("sg.date_add desc")
		->limit($page->firstRow . ',' . $page->listRows);

		$lists = $lists->select();

		$this->assign('page',$page->show());
		$this->assign('lists',$lists);
        $this->assign("special_id",$special_id);
        $this->display();
	}
	
	//新增或编辑秒杀商品
	public function specialEdit(){
        !($special_id = I("special_id")) && $this->error("请传入special_id");
        $res = false;
		$special_goods_id = I("special_goods_id");
		if (IS_POST){
//			$data['quantity'] = (int)I("quantity");
			$data['status'] = (int)I("status");
			$data['date_start'] = strtotime(I("date_start"));
			$data['date_end'] = strtotime(I("date_end"));
			$data['max_buy'] = (int)I("max_buy");
            $data['sort'] = (int)I("sort");
            $data['image'] = !(I("image")) ? null : I("image");
			if ($special_goods_id){
//				$quantity = M("special_goods")->alias("sg")->join("goods g",'g.goods_id = sg.goods_id')->where(['sg.special_goods_id' => $special_goods_id])->getField("g.quantity");
//				if ($quantity < $data['quantity']){
//					$this->error("秒杀商品库存不能大于商品实际库存");
//				}
				$res = M("special_goods")->where(['special_goods_id' => $special_goods_id])->save($data);
			}else {
				$data['special_id'] = 1;
				$data['date_add'] = time();
				$data['goods_id'] = I("goods_id");
//				$quantity = M("goods")->where(['goods_id' => $data['goods_id']])->getField("quantity");
//				if ($quantity < $data['quantity']){
//					$this->error("秒杀商品库存不能大于商品实际库存");
//				}
				$res = M("special_goods")->add($data);
			}
			if ($res === false){
				$this->error('操作失败');
			}
			$this->success("操作成功",null,U("goods/buynow",['special_id' => $special_id]));
		}
		if ($special_goods_id){
			$goods = M("special_goods")
			->alias("sg")
			->join("goods g",'g.goods_id = sg.goods_id')
			->join("seller_shopinfo ss","ss.seller_id = g.seller_id","left")
			->where(['sg.special_goods_id' => $special_goods_id])
			->field("g.goods_id,g.name,sg.max_buy,sg.quantity,sg.status,sg.date_start,sg.date_end,ifnull(ss.shop_name,'未找到关联店铺') as shop_name,sg.special_goods_id,sg.image,sg.sort")
			->find();
			
			$this->assign("goods",$goods);
            $this->assign("special_id",$special_id);
            $this->display();
		}else{
			$goods = array(
					"name" => "",
					"quantity" => 0,
					"status" => 1,
					"date_start" => "",
					"date_end" => "",
					"shop_name" => '',
					"special_goods_id" => 0,
			        "max_buy" => 0,
                    "goods_id" => "",
                    "image" => "",
                    "sort" => 0
			);
			$this->assign("goods",$goods);
            $this->assign("special_id",$special_id);
            $this->display();
		}
	}
	
	//关闭秒杀商品
	public function specialoffsale(){
		!($id = I("special_goods_id")) && $this->error("请传入id");
		$res = M("special_goods")->where(['special_goods_id' => $id])->save(['status' => 0]);
		
		if ($res === false){
			$this->error("操作失败");
		}
		
		$this->success("操作成功",null,U("goods/buynow"));
	}
	
	//开启秒杀商品
	public function specialonsale(){
		!($id = I("special_goods_id")) && $this->error("请传入id");
		$res = M("special_goods")->where(['special_goods_id' => $id])->save(['status' => 1]);
		
		if($res === false){
			$this->error("操作失败");
		}
		$this->success("操作成功",null,U("goods/buynow"));
	}
	
	//删除秒杀商品
	public function specialdel(){
		!($id = I("special_goods_id")) && $this->error("请传入id");
		$res = M("special_goods")->where(['special_goods_id' => $id])->delete();
		
		if($res === false){
			$this->error("操作失败");
		}
		$this->success("操作成功",null,U("goods/buynow"));
	}

	public function keyword(){
		$map = [];

		$keyword_id = I("get.keyword_id");
		$keyword = I("get.keyword");
		$status=I("get.status");
		$min_date = I("min_date");
		$max_date = I("max_date");
		if(!empty($keyword_id)){
			$map['k.keyword_id']=$keyword_id;
		}
		if($status==1){
			$map['k.status']=['eq',$status];
		}else if($status==2){
			$map['k.status']=['eq',0];
		}

		if(!empty($keyword)){
			$map['k.keyword'] = ['like',"%" . $keyword . "%"];
		}
		if(!empty($min_date)){

			$map['k.date_add'] = ['egt', strtotime($min_date)];
		}
		if(!empty($max_date)){
			if (!empty($map['k.date_add']) ) {
				$map['k.date_add'][] =  ['elt', strtotime($max_date)];
			}else{
				$map['k.date_start'] = ['elt', strtotime($max_date)];
			}
		}
		$count=M('keyword')
			->alias('k')
			->where($map);

		$count = $count->count();

		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$page = new \com\Page($count, 15);
		$lists = M('keyword')
			->alias('k')
			->field("k.*")
			->where($map)
			->order(" k.sort")
			->limit($page->firstRow . ',' . $page->listRows);

		$lists = $lists->select();

		$this->assign('page',$page->show());
		$this->assign('lists',$lists);
		$this->display();
	}

	public function set_home(){
		(!$goods_id = I("goods_id")) &&$this->error("请传入商品");
		$is_home = (int)I("type");
		$count = M("goods")->where(['goods_id' => ['neq', $goods_id], 'goods_type' => 2 ,'is_home' => 1, 'is_delete' => 0 ,'on_sale' => 1])->count();
		if($count >= 2){
			$this->error("首页仅有2个名额");
		}

		M("goods")->where(['goods_id' => $goods_id])->save(['is_home' => $is_home]);
		S("bear_home_seckill", null);
		$this->success("操作成功");
	}
	
	public function searchGoods(){
		$term = I("term");
		
		$goods = M("goods")
		->alias("g")
		->join("seller_shopinfo ss",'ss.seller_id = g.seller_id')
		->where(['g.name' => ['like', "%$term%"],'g.is_delete' => 0,'g.on_sale' => 1,'g.apply_status' => 2,'g.seller_id' => ['neq',0],'ss.status' => 1])
		->field("g.name, g.goods_id")
		->limit(20)
		->select();
		echo json_encode($goods);
		die();
	}

    public function searchGoodsById(){
        $term = I("term");

        $goods = M("goods")
            ->alias("g")
            ->join("seller_shopinfo ss",'ss.seller_id = g.seller_id')
            ->where(['g.goods_id' => ['like',$term.'%'],'g.is_delete' => 0,'g.on_sale' => 1,'g.apply_status' => 2,'g.seller_id' => ['neq',0],'ss.status' => 1])
            ->field("g.name, g.goods_id")
            ->limit(20)
            ->select();
        echo json_encode($goods);
        die();
    }

	public function claim(){
		$map['g.is_delete'] = 0;
		$map['g.goods_type'] = 3;
		$goods_number = I("get.goods_number");
		$goods_name = I("get.goods_name");
		$min_date = I("get.min_date");
		$max_date = I("get.max_date");
		$goods_state=I("get.goods_state");
		$category_id = (int)I("category_id");
		if(!empty($goods_number)){
			$map['g.sku']=['like',$goods_number."%"];
		}
		if($goods_state==1){
			$map['g.on_sale']=['eq',$goods_state];
		}else if($goods_state==2){
		}
		if(!empty($goods_name)){
            $map['g.on_sale']=['eq',0];
			$map['g.name'] = ['like',"%" . $goods_name . "%"];
		}
		if(!empty($min_date)){

			$map['g.date_add'] = ['egt', strtotime($min_date)];
		}
		if(!empty($max_date)){
			if (!empty($map['g.date_add']) ) {
				$map['g.date_add'][] =  ['elt', strtotime($max_date) ];
			}else{
				$map['g.date_add'] = ['elt', strtotime($max_date) ];
			}
		}

		$count=M('goods')
		->alias('g')
		->where($map);

		$count = $count->count();

		$host = \app\library\SettingHelper::get("bear_image_url");
		$page = new \com\Page($count, 15);
		$lists = M('goods')
		->alias('g')
		->join("goods pg","pg.goods_id = g.pid","LEFT")
		->field("g.*, concat('$host', g.cover) as image_url,pg.name as pname")
		->where($map)
		->group("g.goods_id")
		->order("g.date_add desc")
		->limit($page->firstRow . ',' . $page->listRows);

		$lists = $lists->select();

		$this->assign('page',$page->show());
		$this->assign('lists',$lists);
		$this->display();
	}
	
	public function edit()
	{
		$goods_id = I("goods_id");
		$goods_type = (int)I("goods_type");
		if(IS_POST){
			(!$goods_name = I('post.goods_name')) && $this->error('商品名称不能为空');
			(!$goods_sku = I('post.goods_sku')) && $this->error('商品编号不能为空');
			(!$goods_category = I('post.goods_category')) && $goods_type != 3 && $this->error('产品分类不能为空');
			$goods_desc = h(I('post.goods_desc'));
			$goods_price = (float)I('post.goods_price');
			$market_price = (float)I("post.market_price");
			$virtual_count = (int)I("post.virtual_count");
			$banner = I("banner");
			$stock_type = (int)I("post.stock_type");
			$dispatch_type = (int)I("post.dispatch_type");
			$dispatch_fee = I("post.dispatch_fee");
			$dispatch_id = (int)I("post.dispatch_id");
			$max_integration = I("max_integration");
			$max_shopping_coin = I("max_shopping_coin");
			$max_buy = (int)I("post.max_buy");
			$max_type = (int)I("post.max_type");
			$weight = (double)I("post.weight");
			$time_unit = (int)I("time_unit");
			$time_unit == 0 && $time_unit = 1;
			$time_number = (int)I("time_number");
			$time_number < 0 && $this->error("时间数值必须大于等于0");
			$manage_fee = round(I("manage_fee") / 100,2);
			$pv = round(I("pv") / 100,2);
			I("changed") && $this->error("修改规格参数后，请进行刷新操作");
			$specs = json_decode(I("specs"), true);
			$options = json_decode(I("options"), true);
			$hongfu = I("hongfu");
			$reward_fee = I("reward_fee")/100;
			$integra_fee = I("integra_fee")/100;
			$purchase_fee = I("purchase_fee")/100;
			$address = I("address");
			$pv_self = I("pv_self")/100;
			$pv_superior = I("pv_superior")/100;

			foreach($specs as $s){
				if(!isset($s['name']) ||  empty($s['name'])){
					$this->error("请输入规格名");
				}
				if(!isset($s['items']) || empty($s['items'])){
					$this->error("请输入规格项");
				}
				foreach ($s['items'] as $i){
					if(!isset($i['name']) || empty($i['name'])){
						$this->error("请输入规格项名称");
					}
				}

			}

			foreach ($options as $o){
				if(!isset($o['stock']) || empty($o['stock'])){
					$this->error("规格项中有库存未输入");
				}
				if(!isset($o['sale_price'])){
					$o['sale_price'] = 0;
				}
				if($o['sale_price'] < 0){
					$this->error("请输入销售金额"  );
				}

				if(!isset($o['market_price']) ||  empty($o['market_price']) || $o['market_price'] <= 0){
					$this->error("请输入市场金额");
				}
			}


			$date_start = I("post.date_start");

			$date_end = I("post.date_end");

			$pid = (int)I("post.pid");
			$on_sale = 0;

			if($goods_type == 2){
				empty($date_start) && $this->error("请传入活动起始日期");
				empty($date_end) && $this->error("请传入活动截止日期");
				empty($banner) && $this->error("请传入专场图片");
				$date_start = strtotime($date_start);
				$date_end = strtotime($date_end);
				$on_sale = 1;

			}else{
				$date_start = 0;
				$date_end = 0;
			}



			if($goods_type == 3){
				$goods_price < 0 && $this->error("商品金额不得小于0");
				empty($banner) && $this->error("请传入专场图片");
				$market_price < 0 && $this->error("市场金额不得小于0");
				$pid <= 0 && $this->error("请选择关联商品");
				$goods_category =  M("goods")->where(['goods_id' => $pid]) ->getField("category_id");
				empty($goods_category) && $this->error("找不到关联商品对应分类");
			}else{
				$goods_price < 0 && $this->error("商品金额不得小于0");
				$market_price < 0 && $this->error("市场金额不得小于0");
			}

			//体验商品
			if($goods_type == 4){
				$goods_price < 0 && $this->error("商品金额不得小于0");
				empty($banner) && $this->error("请传入专场图片");
				$market_price < 0 && $this->error("市场金额不得小于0");
				$pid <= 0 && $this->error("请选择关联商品");
				$goods_category =  M("goods")->where(['goods_id' => $pid]) ->getField("category_id");
				empty($goods_category) && $this->error("找不到关联商品对应分类");
			}else{
				$goods_price < 0 && $this->error("商品金额不得小于0");
				$market_price < 0 && $this->error("市场金额不得小于0");
			}			
			
			$dispatch_type == 1 && $dispatch_fee <= 0 && $this->error("请输入正确的运费费用");

			$dispatch_type == 2 && $dispatch_id <= 0 && $this->error("请传入正确的运费方式");

			$sort = (int)I("post.sort");
			$goods_stock = (int)I('post.goods_stock');
			$mini_name = I("post.mini_name");
			$goods_text = htmlspecialchars(I('post.editorValue'), ENT_QUOTES);
			$image_urls = I('post.image_id/a');
			$data = [
				'name'=>$goods_name,
				'cover' => I('post.image'),
				'time_unit' => $time_unit,
				'time_number' => $time_number,
				'weight' => $weight,
				'date_upd'=>time(),
				'banner' =>$banner,
				'sku'=>$goods_sku,'detail'=>$goods_desc,
				'description'=>$goods_text,
				'shop_price'=>$goods_price,
				'stock_type'=>$stock_type,
				'category_id' => $goods_category,
				'quantity'=>$goods_stock,
				'mini_name' => $mini_name,
				'max_integration' => $max_integration,
				'max_shopping_coin' => $max_shopping_coin,
				'max_buy' => $max_buy,
				'max_type' => $max_type,
				'market_price' => $market_price,
				'sort' => $sort,
				'virtual_count' => $virtual_count,
				'dispatch_type' => $dispatch_type,
				'dispatch_fee' => $dispatch_fee,
				'dispatch_id' => $dispatch_id,
				'manage_fee' => $manage_fee,
				'pv' => $pv,
				'reward_fee' => $reward_fee,
				'integra_fee' => $integra_fee,
				'purchase_fee' => $purchase_fee,
				'pv_self' => $pv_self,
				'pv_superior' => $pv_superior,
                'hongfu' => $hongfu,
                'address' => $address,
			];

			if($goods_id){
				M('goods')->where(array("goods_id" => $goods_id))
				->save($data);
				if (empty($address)){
				    M("goods")->execute("update vc_goods set address = null where goods_id = ".$goods_id);
                }
			}else{
				$data['date_add'] = time();
				$data['on_sale'] = $on_sale;
				$data['goods_type'] = $goods_type;
				$data['seller_id'] = 1;
				$goods_id = M("goods")->add($data);
			}

			if($goods_id){
				$categorys = $this->getcategorylevel($goods_category);
				M("category_goods")->where(['goods_id' => $goods_id])->delete();
				foreach ($categorys as $key=>$val) {
					M('category_goods')->add(['category_id'=>$val,'goods_id'=>$goods_id]);
				}
				M("image")->where(['goods_id' => $goods_id])->delete();
				if(count($image_urls)>0){
				    M("goods")->where(['goods_id' => $goods_id])->save(['cover' => $image_urls[0]]);
					foreach($image_urls as $image_url){
						M('image')->add(['goods_id'=>$goods_id,'name'=>$image_url,'cover'=>0]);
					}
				}

				$params = json_decode(I("params"), true);
				$ids = [];
				$sort = 0;
				foreach ($params as $p){
					$data = ['name' => $p['name'], 'value' => $p['value'], 'sort' => $sort ++];
					if($p['id'] > 0){
						M("goods_param")->where(['goods_id' => $goods_id ,'id' => $p['id'] ])
						->save($data);
						$ids[] = $p['id'];
					}else{
						$data['goods_id'] = $goods_id;
						$ids[] = M("goods_param")->add($data);
					}
				}
				M("goods_param")->where(['goods_id' => $goods_id, 'id' => ['not in', join(",", $ids)]])->delete();


				$sepcs_ids = [];

				$sort = 0;
				$items_mapping = [];
				foreach ($specs as $spec){
					$data = ['name' => $spec['name'],'goods_id' => $goods_id, 'sort' => $sort ++];
					$spec_id = $spec['id'];
					if(strlen($spec['id']. "") < 10){
						M("goods_spec")->where(['goods_id' => $goods_id ,'id' => $spec['id'] ])
						->save($data);
						$sepcs_ids[] = $spec['id'];
						$spec_id = $spec['id'];
					}else{
						$spec_id = M("goods_spec")->add($data);
						$sepcs_ids[] = $spec_id;
					}
					$item_sort = 0;
					$item_ids = [];

					foreach ($spec['items'] as $item){
						$data = ['name' => $item['name'], 'spec_id' => $spec_id, 'sort' => $item_sort ++ ];
						$item_id = $item['id'];
						if(strlen($item['id']. "") < 10){
							$data['id'] = $item['id'];
							$res = M("goods_spec_item")->where(['id' => $item['id']])->save($data);
							$item_ids[] = $item_id;
						}else{
							$item_id = M("goods_spec_item")->add($data);

							$item_ids[] = $item_id;
						}
						$data['id'] = $item_id;
						$items_mapping[$item['id'] .""] = $data;
					}
					M("goods_spec_item")->where(['spec_id' => $spec_id,'id' => ['not in', join(",", $item_ids)]])->delete();
					M("goods_spec")->where(['id' => $spec_id])->save(['content' => serialize($item_ids)]);
				}
				M("goods_spec")->where(['id' => ['not in', join(",", $sepcs_ids) ], 'goods_id' => $goods_id])->delete();
				$sort = 0;
				foreach ($options as $k => $option){
					$data = $option;
					$option_specs = explode("_", $data['specs']);
					$name = [];
					$ss = [];
					foreach ($option_specs as $sp){
						if(!isset($items_mapping[$sp])){
							$this->error("规格参数未匹配，请进行查看");
						}
						$ss[] = $items_mapping[$sp]['id'];
						$name[] = $items_mapping[$sp]['name'];
					}
					$data['specs'] = join("_", $ss);
					$data['name'] = join("+", $name);
					$data['goods_id'] = $goods_id;
					$data['sort'] = $sort++;
					$options[$k] = $data;
				}
				$option_ids = [];
				foreach ($options as $option){
					$data = $option;
					unset($data['id']);
					unset($data['items']);
					if($option['id'] > 0){
						M("goods_option")->where(['id' => $option['id']])->save($data);
						$option_ids[] = $option['id'];
					}else{
						$option_ids[] = M("goods_option")->add($data);
					}
				}
				$line = M("goods_option")->where(['goods_id' => $goods_id,'id' => ['not in',join(",", $option_ids)]])->delete();
				$this->log("修改商品".$goods_name .";id:". $goods_id );

                switch ($goods_type){
                    case 4:
                        $url = "goods/experience";
                        break;
                    case 3:
                        $url = "goods/claim";
                        break;
                    case 2:
                        $url = "goods/buynow";
                        break;
                    default:
                        $url = "goods/index";
                        break;
                }

                S("shuaibo_goods_" . $goods_id, null);
                S("shuaibo_goods_recs" , null);
                S("shuaibo_home_seckill", null);

				$this->success('修改成功',null, U($url));
			}
		}
		$info = M("goods")->getEmptyFields();
		$info['image_url'] = '';
		$info['params'] = [];
		$info['specs'] = [];
		$info['options'] = [];
		$info['category_id'] = 0;
		$info['imgs'] = [];
		$category = M('category')->select();
		if($goods_id > 0){
			$info = M('goods')->alias('g')->field('g.*,g.cover as image_url')->where(['g.goods_id'=>$goods_id])->find();
			$info['pv'] = $info['pv'] * 100;
            $info['manage_fee'] = $info['manage_fee'] * 100;
			$imgs = M('image')
			->where(['goods_id'=>$goods_id,'cover'=>0])->select();
			$info['imgs'] = $imgs;
			$info['params'] = M("goods_param")->where(['goods_id' => $goods_id])->order("sort")->select();
			$specs = M("goods_spec")->where(['goods_id' => $goods_id])->order("sort")->select();
			foreach ($specs as &$val){
				$val['items'] = M("goods_spec_item")->where(['spec_id' => $val['id']])->order("sort")->select();
			}
			$info['specs'] = $specs;
			$info['options'] = M("goods_option")->where(['goods_id' => 	$goods_id])->select();
		}
		$goods_type = empty($goods_id) ? $goods_type : $info['goods_type'];
		$array = array();
		foreach($category as $k => $r) {
			$r['category_id']         = $r['category_id'];
			$r['title']      = $r['name'];
			$r['name']       = $r['name'];
			$r['disabled']   = $r['active']==0 ? 'disabled' : '';
			$array[$r['category_id']] = $r;
		}
		$dispatchs = M("express_template")->select();
		$str  = "<option value='\$category_id' \$selected \$disabled >\$spacer \$title</option>";
		$Tree = new \com\Tree();
		$Tree->init($array);
		$select_categorys = $Tree->get_tree(0, $str,(int)$info['category_id']);

		/*$url = $goods_type == 3 ? "goods/claim" : ($goods_type == 2 ? "goods/buynow" : "goods/index");*/

		//新增
		switch ($goods_type){
            case 4:
                $url = "goods/experience";
                break;
            case 3:
                $url = "goods/claim";
                break;
            case 2:
                $url = "goods/buynow";
                break;
            default:
                $url = "goods/index";
                break;
        }

		if($goods_type == 2){
			if(!empty($info['date_start'])){
				$info['date_start'] = date("Y-m-d H:i:s", $info['date_start']);
				$info['date_end'] = date("Y-m-d H:i:s", $info['date_end']);
			}
		}
		if($goods_type == 3){
			$goods = M("goods")->where(['is_delete' => 0,'goods_type' => ['in',"1,2"]])->field("goods_id, name")->select();
			$this->assign("p_goods", $goods);
		}

		//新增
		if($goods_type == 4){
			$goods = M("goods")->where(['is_delete' => 0,'goods_type' => ['in',"1,2"]])->field("goods_id, name")->select();
			$this->assign("p_goods", $goods);
		}
		$this->assign('goods',$info);

		$this->assign('categorys',$select_categorys);
		$this->assign("dispatchs" , $dispatchs);
		$this->assign("goods_type", $goods_type);
		$this->assign("url", U($url));
		$this->display();
	}
    public function buynow_edit()
    {
		if (IS_POST) {
			$goods_id = I('post.goods_id');
			$date_start= I('post.date_start');
			$date_end = I('post.end');
			$goods_name=I('post.goods_name');
			if (empty($goods_id) || empty($date_end) || empty($date_start)) {
				$this->error('信息不完整');
			}
			$data = array(
				'goods_id' => $goods_id,
				'date_end' => $date_end,
				'date_start' => $date_start,
				'date_add' => time(),
			);
			$id = M('special_goods')->data($data)->add();

			$goods_name = I("get.goods_name");
			if(!empty($goods_name)){
				$map['g.name'] = ['like',"%" . $goods_name . "%"];
			}
			$lists = M('special_goods')
				->alias('g')
				->join("goods go","go.goods_id = g.goods_id")
				->field("g.goods_id,go.name");

			if ($id) {
				$this->success('添加成功', U('goods/buynow'));
			} else {

				$this->error('添加失败');
			}
		} else {
			$this->display();
		}
    }

	public function keywordedit()
	{
		if (IS_POST) {
		$keyword = I('post.keyword');
		$status = I("status");
		$sort = I("sort");
		if (empty($keyword)) {
			$this->error('信息不完整');
		}
		$data = array(
			'keyword' => $keyword,
			'date_add' => time(),
			'status' =>$status,
			"sort" => $sort,
			"date_upd" => time()
		);
		$id = M('keyword')->add($data);
		if ($id) {
			$this->success('添加成功',null, U('goods/keyword'));
		} else {

			$this->error('添加失败');
		}
		} else {

		$this->display();
		}
	}

	public function keyword_edit()
	{   $keyword_id=I("keyword_id");
		if (IS_POST) {
			$keyword = I('post.keyword');
			$sort = I("sort");
			$status = I("status");
			if (empty($keyword)) {
				$this->error('信息不完整');
			}
			$data = array(
				'keyword' => $keyword,
				'sort' => $sort,
				'status' => $status
			);
			$id = M('keyword')->where(['keyword_id' => $keyword_id])->save($data);
			if ($id) {
				$this->success('编辑成功',null, U('goods/keyword'));
			} else {

				$this->error('编辑失败');
			}
		} else {
			$keyword=M('keyword')->where(['keyword_id' => $keyword_id])->field("keyword,sort,status")->find();
			$this->assign("keyword",$keyword);
			$this->display();
		}
	}

	public function addstock(){
		(!$goods_id = (int)I("rid")) && $this->error("参数错误");
		(!$addstock = (int)I("addcount")) && $this->error("参数错误");

		D("user_action")->saveAction(session("userid"),8,$goods_id,"商品:".D("goods")->where(['goods_id' => $goods_id])->getField("name")."添加库存".$addstock);
		M('goods')->where(['goods_id' => $goods_id])->setInc("quantity", $addstock) ;
		S("bear_goods_" . $goods_id, null);
		S("bear_goods_recs" , null);
		$this->success("成功") ;

	}


	public function offsale()
	{
		(!$goods_id = (int)I('get.goods_id')) && $this->error('参数错误');
		$this->log("商品:".D("goods")->where(['goods_id' => $goods_id])->getField("name")."下架");
		S("bear_home_seckill", null);
		M('goods')->where(['goods_id'=>$goods_id])->save(['on_sale'=>0]);

		S("bear_goods_" . $goods_id, null);
		S("bear_goods_recs" , null);
		$this->success('成功');
	}

	public function onsale()
	{
		(!$goods_id = (int)I('get.goods_id')) && $this->error('参数错误');
        $this->log("商品:".D("goods")->getField($goods_id)."上架");
		S("bear_home_seckill", null);
		M('goods')->where(['goods_id'=>$goods_id])->save(['on_sale'=>1,'date_on_sale' => time()]) ;
		S("bear_goods_" . $goods_id, null);
		S("bear_goods_recs" , null);
		$this->success('成功');
	}


	public function buynow_offsale()
	{
		(!$goods_id = (int)I('get.goods_id')) && $this->error('参数错误');
		$this->log("商品:".D("special_goods")
				->alias("sg")
				->join("goods g","g.goods_id = sg.goods_id")
				->where(['sg.goods_id' => $goods_id])->getField("g.name")."下架");
		M('special_goods')->where(['goods_id'=>$goods_id])->save(['status'=>0]);

		/*	S("bear_home_seckill", null);
           S("bear_goods_" . $goods_id, null);
           S("bear_goods_recs" , null); */
		$this->success('成功');
	}

	public function buynow_onsale()
	{
		(!$goods_id = (int)I('get.goods_id')) && $this->error('参数错误');
		$this->log("商品:".D("special_goods")
				->alias("sg")
				->join("goods g","g.goods_id = sg.goods_id")
				->where(['sg.goods_id' => $goods_id])
				->getField("g.name")."上架");
		M('special_goods')->where(['goods_id'=>$goods_id])->save(['status'=>1]) ;
		/*S("bear_home_seckill", null);
        S("bear_goods_" . $goods_id, null);
        S("bear_goods_recs" , null);*/
		$this->success('成功');
	}

	public function keyword_offsale()
	{
		(!$keyword_id = (int)I('get.keyword_id')) && $this->error('参数错误');
		$this->log("关键字:".D("keyword")
				->where(['keyword_id' => $keyword_id])->getField("keyword")."下架");
		M('keyword')->where(['keyword_id'=>$keyword_id])->save(['status'=>0]);

		/*	S("bear_home_seckill", null);
           S("bear_goods_" . $goods_id, null);
           S("bear_goods_recs" , null); */
		$this->success('成功');
	}

	public function keyword_onsale()
	{
		(!$keyword_id = (int)I('get.keyword_id')) && $this->error('参数错误');
		$this->log("商品:".D("keyword")->getField($keyword_id)."上架");
		M('keyword')->where(['keyword_id'=>$keyword_id])->save(['status'=>1]) ;
		/*S("bear_home_seckill", null);
        S("bear_goods_" . $goods_id, null);
        S("bear_goods_recs" , null);*/
		$this->success('成功');
	}

	private function getcategorylevel($pid,$data=[])
	{
		if($pid == 0){
			return $data;
		}else{
			$result = M('category')->where(['category_id'=>$pid])->find();
			//print_r($result);
			array_push($data, $result['category_id']);

			return $this->getcategorylevel($result['pid'],$data);
		}
	}

	public function delete(){
		(!$goods_id = (int)I('get.goods_id')) && $this->error('参数错误');
		$res = M("goods")->where(['goods_id' => $goods_id])->save(['is_delete' => 1, 'on_sale' => 0]);
		D("user_action")->saveAction(session("userid"),8,$goods_id,"商品:".D("goods_model")->GetGoodsName($goods_id)."删除");
		S("bear_home_seckill", null);
		S("bear_goods_" . $goods_id, null);
		S("bear_goods_recs" , null);
		$res ? $this->success('成功') : $this->error("失败");
	}
    public function buynow_delete(){
        (!$goods_id = (int)I('get.goods_id')) && $this->error('参数错误');
        $res = M("special_goods")->where(['goods_id' => $goods_id])->delete();
//        D("user_action")->saveAction(session("userid"),8,$goods_id,"商品:".D("special_goods")->GetGoodsName($goods_id)."删除");
//		S("bear_home_seckill", null);
//		S("bear_goods_" . $goods_id, null);
//		S("bear_goods_recs" , null);
		$res ? $this->success('成功') : $this->error("失败");
	}
	public function keyword_delete(){
		(!$keyword_id = (int)I('get.keyword_id')) && $this->error('参数错误');
		$res = M("keyword")->where(['keyword_id' => $keyword_id])->delete();
//        D("user_action")->saveAction(session("userid"),8,$goods_id,"商品:".D("special_goods")->GetGoodsName($goods_id)."删除");
//		S("bear_home_seckill", null);
//		S("bear_goods_" . $goods_id, null);
//		S("bear_goods_recs" , null);
		$res ? $this->success('成功') : $this->error("失败");
	}
	public function points()
	{
		$count=M('goods_point')
		->count();
		$page = new \com\Page($count, 15);
		$lists = M('goods_point')
		->alias('g')
		->field('g.*,i.name as image_url')
		->join('image_point i','g.goods_id=i.goods_id')
		->where('i.cover=1')
		->group("g.goods_id")
		->limit($page->firstRow . ',' . $page->listRows)
		->select();

		$this->assign('page',$page->show());
		$this->assign('lists',$lists);
		$this->display();
	}

	public function ajax_goods_search(){
		$keyword = I("term");
		$result = [];
		if(empty($keyword)){
			die(json_encode($result));
		}
		$result = M("goods")->where(['name' => ['like', '%' . $keyword . '%']])->field("name as goods_name, goods_id")->select();
		die(json_encode($result));
	}

	public function goodsdetail() {
        !($goods_id = I('goods_id')) && $this->error("请传入商品");
        $goods = D("goods_model")->getGoodsInfoShow($goods_id);

        $this->assign("goods",$goods);
        $this->display();
    }

    //  购买协议
    public function protocol() {
        if(IS_POST){
            $protocol = htmlspecialchars(I('post.editorValue'), ENT_QUOTES);
            \app\library\SettingHelper::set("shuaibo_goods_protocol", $protocol);
            $this->success("修改成功");
        }
        $protocol = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_goods_protocol"), ENT_QUOTES) ;
        $this->assign("protocol", $protocol);
        $this->display();
    }
}