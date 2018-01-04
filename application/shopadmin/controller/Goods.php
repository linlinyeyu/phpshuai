<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Goods extends Admin
{
	public function index()
	{
	    $map['g.is_delete'] = 0;
	    $map['g.goods_type'] = 1;
	    $map['g.seller_id'] = session("sellerid");
	    $goods_number = I("get.goods_number");
	    $goods_name = I("get.goods_name");
	    $min_date = I("get.min_date");
	    $max_date = I("get.max_date");
	    $goods_state=I("get.goods_state");
	    $category_id = (int)I("seller_cat_id");
	    $pcategory_id = (int)I("category_id");
	    $apply_status = (int)I("apply_status");
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
	    
	    if ($apply_status == 1){
	    	$map['g.apply_status'] = 1;
	    }elseif ($apply_status == 2){
	    	$map['g.apply_status'] = 2;
	    }elseif ($apply_status == 3){
	    	$map['g.apply_status'] = 3;
	    }

		$count=M('goods')
		->alias('g')
		->join("category c",'c.category_id = g.category_id')
		->join("seller_category sc",'sc.seller_cat_id = g.seller_cat_id')
		->where($map);
		if($category_id > 0){
			$count = $count->join("seller_category_goods scg","scg.goods_id = g.goods_id and scg.seller_cat_id = $category_id");
		}
		if($pcategory_id > 0){
			$count = $count->join("category_goods cg","cg.goods_id = g.goods_id and cg.category_id = $pcategory_id");
		}
		$count = $count->count();

		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$page = new \com\Page($count, 15);
		$lists = M('goods')
		->alias('g')
		->join("category c",'c.category_id = g.category_id')
		->join("seller_category sc",'sc.seller_cat_id = g.seller_cat_id')
		->field("g.*, concat('$host', g.cover) as image_url,ifnull(c.name,'未找到分类') as category_name,ifnull(sc.cat_name,'未找到分类') as seller_cat_name")
		->where($map)
		->group("g.goods_id")
		->order("g.date_add desc")
		->limit($page->firstRow . ',' . $page->listRows);
		if($category_id > 0){
			$lists = $lists->join("seller_category_goods scg","scg.goods_id = g.goods_id and scg.seller_cat_id = $category_id");
		}
		if($pcategory_id > 0){
			$lists = $lists->join("category_goods cg","cg.goods_id = g.goods_id and cg.category_id = $pcategory_id");
		}
		$lists = $lists->select();
		$Node = M("seller_category")->where(["seller_id" => session("sellerid"),'is_show' => 1])->select();
		$array = array();
		foreach($Node as $k => $r) {
			$r['id']         = $r['seller_cat_id'];
			$r['title']      = $r['cat_name'];
			$r['name']       = $r['cat_name'];
			$r['disabled'] = false;
			$array[$r['seller_cat_id']] = $r;
		}
		$str  = "<option value='\$id' \$selected \$disabled >\$spacer \$title</option>";
		$Tree = new \com\Tree();
		$Tree->init($array);
		$select_categorys = $Tree->get_tree(0, $str, $category_id);
		
		$Node = M("category")->where(['active' => 1])->select();
		$array = array();
		foreach ($Node as $k => $r){
			$r['id'] = $r['category_id'];
			$r['title'] = $r['name'];
			$r['name'] = $r['name'];
			$r['disabled'] = false;
			$array[$r['category_id']] = $r;
		}
		$str  = "<option value='\$id' \$selected \$disabled >\$spacer \$title</option>";
		$Tree = new \com\Tree();
		$Tree->init($array);
		$categories = $Tree->get_tree(0, $str, $pcategory_id);
		
		$this->assign('select_categorys',$select_categorys);
		$this->assign("categories",$categories);
		$this->assign('page',$page->show());

		$this->assign('lists',$lists);
		$this->display();
	}
	
	public function searchGoods(){
		$term = I("term");
	
		$goods = M("goods")->where(['name' => ['like', "%$term%"],'is_delete' => 0,'on_sale' => 1,'apply_status' => 2,"seller_id" => session("sellerid")])->field("name, goods_id")->limit(20)->select();
		echo json_encode($goods);
		die();
	}

	public function edit()
	{
		$goods_id = I("goods_id");
		$goods_type = (int)I("goods_type");
        $seller_id = session("sellerid");
		if ($goods_type <= 0) {
		    $goods_type = 1;
        }
		if(IS_POST){
			(!$goods_name = I('post.goods_name')) && $this->error('商品名称不能为空');
			(!$goods_sku = I('post.goods_sku')) && $this->error('商品编号不能为空');
			(!$goods_category = I('post.goods_category')) && $goods_type != 3 && $this->error('产品分类不能为空');
			(!$pcategory = I('post.category_id')) && $this->error('平台分类不能为空');
			$goods_desc = h(I('post.goods_desc'));
			$goods_price = (float)I('post.goods_price');
			$market_price = (float)I("post.market_price");
			$banner = I("banner");
			$stock_type = (int)I("post.stock_type");
			$dispatch_type = (int)I("post.dispatch_type");
			$dispatch_fee = I("post.dispatch_fee");
			$dispatch_id = (int)I("post.dispatch_id");
			$max_integration = I("max_integration");
			$max_buy = (int)I("post.max_buy");
			$max_type = (int)I("post.max_type");
			$weight = (double)I("post.weight");
			$time_unit = (int)I("time_unit");
			$time_unit == 0 && $time_unit = 1;
			$time_number = (int)I("time_number");
			$time_number < 0 && $this->error("时间数值必须大于等于0");
			$time_limit = ($time_unit == 1 ? 24 * 60 * 60 : 60 * 60 ) * $time_number;
			I("changed") && $this->error("修改规格参数后，请进行刷新操作");
			$specs = json_decode(I("specs"), true);
			$options = json_decode(I("options"), true);
			$is_recommend = I("is_recommend");

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
				'seller_cat_id' => $goods_category,
				'category_id' => $pcategory,
				'quantity'=>$goods_stock,
				'mini_name' => $mini_name,
				'max_integration' => $max_integration,
				'max_buy' => $max_buy,
				'max_type' => $max_type,
				'market_price' => $market_price,
				'sort' => $sort,
				'dispatch_type' => $dispatch_type,
				'dispatch_fee' => $dispatch_fee,
				'dispatch_id' => $dispatch_id,
				'seller_id' => session("sellerid"),
				'is_recommend' => $is_recommend
			];

			if($goods_id){
                $data['apply_status'] = 1;
                M('goods')->where(array("goods_id" => $goods_id))
				->save($data);
			}else{

                // 判断是否已添加相同商品名
                $goodsinfo = M('goods')->where(['name' => $goods_name])->find();
                if (!empty($goodsinfo)) {
                    $this->error("商品名不能重复");
                }

				$data['apply_status'] = 1;
				$data['date_add'] = time();
				$data['on_sale'] = $on_sale;
				$data['goods_type'] = $goods_type;
				$goods_id = M("goods")->add($data);
			}

			if($goods_id){
				$categorys = $this->getcategorylevel($goods_category);
				M("seller_category_goods")->where(['goods_id' => $goods_id])->delete();
				foreach ($categorys as $key=>$val) {
					M('seller_category_goods')->add(['seller_cat_id'=>$val,'goods_id'=>$goods_id]);
				}
				$pcategorys = $this->getPcategorylevel($pcategory);
				M("category_goods")->where(['goods_id' => $goods_id])->delete();
				foreach ($pcategorys as $key=>$val) {
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
		$info['seller_cat_id'] = 0;
		$info['category_id'] = 0;
		$info['imgs'] = [];
		$category = M('seller_category')->where(['seller_id' => session("sellerid"),'is_show' => 1])->select();
		if($goods_id > 0){
			$info = M('goods')->alias('g')->field('g.*,g.cover as image_url')->where(['g.goods_id'=>$goods_id])->find();
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
			$r['category_id']         = $r['seller_cat_id'];
			$r['title']      = $r['cat_name'];
			$r['name']       = $r['cat_name'];
			$r['disabled']   = $r['is_show']==0 ? 'disabled' : '';
			$array[$r['category_id']] = $r;
		}
		$dispatchs = M("express_template")->where(['seller_id' => $seller_id])->select();
		$str  = "<option value='\$category_id' \$selected \$disabled >\$spacer \$title</option>";
		$Tree = new \com\Tree();
		$Tree->init($array);
		$select_categorys = $Tree->get_tree(0, $str,(int)$info['seller_cat_id']);
		
		$pcategory = M("category")->where(['active' => 1])->select();
		$array = array();
		foreach ($pcategory as $k => $r){
			$r['id'] = $r['category_id'];
			$r['title'] = $r['name'];
			$r['name'] = $r['name'];
			$r['disabled'] = false;
			$array[$r['category_id']] = $r;
		}
		$str  = "<option value='\$id' \$selected \$disabled >\$spacer \$title</option>";
		$Tree = new \com\Tree();
		$Tree->init($array);
		$categories = $Tree->get_tree(0, $str,(int)$info['category_id']);

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
		$this->assign("pcategories",$categories);
		$this->assign("dispatchs" , $dispatchs);
		$this->assign("goods_type", $goods_type);
		$this->assign("url", U($url));
		$this->display();
	}

	public function addstock(){
		(!$goods_id = (int)I("rid")) && $this->error("参数错误");
		(!$addstock = (int)I("addcount")) && $this->error("参数错误");

		D("seller_action")->saveAction(session("id"),8,$goods_id,"商品:".D("goods_model")->where(['goods_id' => $goods_id])->getField("name")."添加库存".$addstock);
		M('goods')->where(['goods_id' => $goods_id])->setInc("quantity", $addstock) ;
		S("bear_goods_" . $goods_id, null);
		S("bear_goods_recs" , null);
		$this->success("成功") ;

	}


	public function offsale()
	{
		(!$goods_id = (int)I('get.goods_id')) && $this->error('参数错误');
		$this->log("商品:".M("goods")->where(['goods_id' => $goods_id])->getField("name")."下架");
		S("bear_home_seckill", null);
		M('goods')->where(['goods_id'=>$goods_id])->save(['on_sale'=>0]);

		S("bear_goods_" . $goods_id, null);
		S("bear_goods_recs" , null);
		$this->success('成功');
	}

	public function onsale()
	{
		(!$goods_id = (int)I('get.goods_id')) && $this->error('参数错误');
        $this->log("商品:".M("goods")->where(['goods_id' => $goods_id])->getField("name")."上架");
		S("bear_home_seckill", null);
		M('goods')->where(['goods_id'=>$goods_id])->save(['on_sale'=>1,'date_on_sale' => time()]) ;
		S("bear_goods_" . $goods_id, null);
		S("bear_goods_recs" , null);
		$this->success('成功');
	}
	
	public function recommend(){
		(!$goods_id = (int)I('get.goods_id')) && $this->error('参数错误');
		$this->log("设置商品:".M("goods")->where(['goods_id' => $goods_id])->getField("name")."推荐");
		S("bear_home_seckill", null);
		M('goods')->where(['goods_id'=>$goods_id])->save(['is_recommend'=>1]) ;
		S("bear_goods_" . $goods_id, null);
		S("bear_goods_recs" , null);
		$this->success('成功');
	}
	
	public function unrecommend(){
		(!$goods_id = (int)I('get.goods_id')) && $this->error('参数错误');
		$this->log("设置商品:".M("goods")->where(['goods_id' => $goods_id])->getField("name")."不推荐");
		S("bear_home_seckill", null);
		M('goods')->where(['goods_id'=>$goods_id])->save(['is_recommend'=>0]) ;
		S("bear_goods_" . $goods_id, null);
		S("bear_goods_recs" , null);
		$this->success('成功');
	}

	private function getcategorylevel($pid,$data=[])
	{
		if($pid == 0){
			return $data;
		}else{
			$result = M('seller_category')->where(['seller_cat_id'=>$pid])->find();
			//print_r($result);
			array_push($data, $result['seller_cat_id']);

			return $this->getcategorylevel($result['pid'],$data);
		}
	}
	
	private function getPcategorylevel($pid,$data=[]){
		if($pid == 0){
			return $data;
		}else{
			$result = M('category')->where(['category_id'=>$pid])->find();
			//print_r($result);
			array_push($data, $result['category_id']);
		
			return $this->getPcategorylevel($result['pid'],$data);
		}
	}

	public function delete(){
		(!$goods_id = (int)I('get.goods_id')) && $this->error('参数错误');
		$res = M("goods")->where(['goods_id' => $goods_id])->save(['is_delete' => 1, 'on_sale' => 0]);
		D("seller_action")->saveAction(session("id"),8,$goods_id,"商品:".D("goods_model")->GetGoodsName($goods_id)."删除");
		S("bear_home_seckill", null);
		S("bear_goods_" . $goods_id, null);
		S("bear_goods_recs" , null);
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

	public function point_add()
	{

	}

	public function point_edit()
	{

	}

	public function point_offsale()
	{

	}

	public function point_onsale()
	{

	}

    /**
     * 重新审核
     */
    public function apply(){
        (!$goods_id = (int)I('get.goods_id')) && $this->error('参数错误');
        $res = M("goods")->where(['goods_id' => $goods_id])->save(['apply_status' => 1]);
        D("seller_action")->saveAction(session("id"),8,$goods_id,"商品:".D("goods_model")->GetGoodsName($goods_id)."重新提交审核");
        S("bear_home_seckill", null);
        S("bear_goods_" . $goods_id, null);
        S("bear_goods_recs" , null);
        $res ? $this->success('成功') : $this->error("失败");
    }

    public function goodsdetail() {
        !($goods_id = I('goods_id')) && $this->error("请传入商品");
        $goods = D("goods_model")->getGoodsInfoShow($goods_id);

        $this->assign("goods",$goods);
        $this->display();
    }
}