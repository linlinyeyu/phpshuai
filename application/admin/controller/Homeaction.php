<?php
namespace app\admin\controller;

use app\admin\Admin;
class Homeaction extends Admin{
	//首页活动
	public function activityIndex(){
		$activities = D("home_model")->getHomeActivity();
		
		$this->assign("activities",$activities);
		$this->display();
	}
	
	//新增首页活动
	public function toEdit(){
		$activity_id = (int)I("home_activity_id");
		$result = [];
		$categories = D("home_model")->getActivity();
		$actions = M("action")->field("action_id,name")->select();
		if($activity_id){
			$result = D("home_model")->getActivityById($activity_id);
		}else {
			$result = array("img" => '','special_id' => '','status' => 1,'home_activity_id' => 0,'action_id' => 0);
		}
		
		$this->assign("activity",$result);
		$this->assign("categories",$categories);
		$this->assign("actions",$actions);
		$this->display();
	}
	
	public function editActivity(){
		$activity_id = (int)I("home_activity_id");
		$data['special_id'] = I("special_id");
		$data['img'] = I("image");
		$data['status'] = I("status");
		!($action_id = I("action_id")) && $this->error("请选择跳转事件");
		$data['action_id'] = $action_id;
		if($activity_id){
			$data['date_upd'] = time();
			$data['home_activity_id'] = $activity_id;
			$this->log("更新首页活动".$data['special_id']);
			$res = D("home_model")->saveActivity($data);
			if ($res === false){
				$this->error("操作失败");
			}
		}else {
			$data['date_add'] = time();
			$data['date_upd'] = time();
			$activity = M("home_activity")->where(['special_id' => $data['special_id']])->find();
			if (!empty($activity)){
				$this->error("该活动已添加");
			}
			$this->log("新增首页活动".$data['special_id']);
			$res = D("home_model")->addActivity($data);
			if ($res === false){
				$this->error("操作失败");
			}
		}
		$this->success("操作成功", null, U('homeaction/activityindex'));
	}
	
	public function isOpen(){
		$activity_id = (int)I("id");
		$status = (int)I("is_open");
		$res = D("home_model")->isOpen($activity_id,$status);
		$res ? $json = array('status'=>1) : $json = array('status'=>0);
    	$this->ajaxReturn($json);
	}
	
	public function toDel(){
		$activity_id = (int)I("home_activity_id");
		$res = D("home_model")->toDel($activity_id);
		if ($res === false){
			$this->error("操作失败");
		}
		$this->log(session("userid"),"删除首页活动".$activity_id);
		$this->success("操作成功");
	}
	
	public function catIndex(){
		$categories = D("home_model")->getHomeCategory();
		
		$this->assign("categories",$categories);
		$this->display();
	}
	
	public function catToEdit(){
		$activity_id = (int)I("home_category_id");
		$result = [];
		$categories = D("home_model")->getCategory();
		if($activity_id){
			$result = D("home_model")->getCategoryById($activity_id);
		}else {
			$result = array("img" => '','category_id' => '','status' => 1,'home_category_id' => 0);
		}
		
		$this->assign("category",$result);
		$this->assign("categories",$categories);
		$this->display();
	}
	
	public function editCategory(){
		$category_id = (int)I("home_category_id");
		$data['category_id'] = I("category_id");
		$data['img'] = I("image");
		$data['status'] = I("status");
		if($category_id){
			$data['date_upd'] = time();
			$data['home_category_id'] = $category_id;
			$this->log("更新首页活动".$data['category_id']);
			$res = D("home_model")->saveCategory($data);
			if ($res === false){
				$this->error("操作失败");
			}
		}else {
			$data['date_add'] = time();
			$data['date_upd'] = time();
			$category = M("home_category")->where(['category_id' => $data['category_id']])->find();
			if (!empty($category)){
				$this->error("该分类已添加");
			}
			$this->log("新增首页活动".$data['category_id']);
			$res = D("home_model")->addCategory($data);
			if ($res === false){
				$this->error("操作失败");
			}
		}
		$this->success("操作成功", null, U('homeaction/catindex'));
	}
	
	public function isCatOpen(){
		$activity_id = (int)I("id");
		$status = (int)I("is_open");
		$res = D("home_model")->isCatOpen($activity_id,$status);
		$res ? $json = array('status'=>1) : $json = array('status'=>0);
    	$this->ajaxReturn($json);
	}
	
	public function catToDel(){
		$category_id = (int)I("home_category_id");
		$res = D("home_model")->delCat($category_id);
		if ($res === false){
			$this->error("操作失败");
		}
		$this->log(session("userid"),"删除首页分类".$category_id);
		$this->success("操作成功");
	}
	
	//每日上新商品列表
	public function newIndex(){
        !($special_id = I("special_id")) && $this->error("请传入special_id");
        $goods = D("home_model")->getEveryGoods();
		
		$this->assign("goods",$goods);
        $this->assign("special_id",$special_id);
        $this->display();
	}
	
	//添加每日上新
	public function newToEdit(){
        !($special_id = I("special_id")) && $this->error("请传入special_id");
        $everyday_newgoods_id = I("everyday_newgoods_id");
        if ($everyday_newgoods_id > 0) {
            $goods = M('everyday_newgoods')
                ->alias("en")
                ->join("goods g","g.goods_id = en.goods_id")
                ->join("seller_shopinfo ss","ss.seller_id = g.seller_id")
                ->where(['en.everyday_newgoods_id' => $everyday_newgoods_id])
                ->field("en.everyday_newgoods_id,en.goods_id,en.sort,g.name,ss.shop_name")
                ->find();
        } else {
            $goods = [
                'everyday_newgoods_id' => '',
                'goods_id' => '',
                'sort' => '',
                'name' => '',
                'shop_name' => ''
            ];
        }

        $this->assign("goods",$goods);
        $this->assign("special_id",$special_id);

        $this->display();
	}
	
	public function getShopName(){
		$goods_id = I("goods_id");
		
		$shop_name = M("goods")->alias("g")->join("seller_shopinfo ss",'ss.seller_id = g.seller_id')->where(['g.goods_id' => $goods_id])
		->field("ss.shop_name")->find();
		
		if (empty($shop_name)){
			$shop_name['shop_name'] = "未找到关联店铺";
		}
		
		return json_encode($shop_name);
	}

    public function getGoodsName(){
        $goods_id = I("goods_id");

        $name = M("goods")
            ->alias("g")
            ->where(['g.goods_id' => $goods_id])
            ->field("g.name")
            ->find();

        if (empty($name)){
            $name['name'] = "未找到关联商品";
        }

        return json_encode($name);
    }
	
	public function editNew(){
        !($special_id = I("special_id")) && $this->error("请传入special_id");
        $goods_id = I("goods_id");
		$sort = I('sort');
		$goods = M("goods")->where(["goods_id" => $goods_id])->field("is_delete,on_sale")->find();
		if ($goods['is_delete'] == 1 || $goods['on_sale'] = 0){
			$this->error("该商品未上架或已删除");
		}
		$data = [
		    'goods_id' => $goods_id,
            'date_upd' => time(),
            'status' => 1,
            'sort' => $sort
        ];
        $everyday_newgoods_id = I("everyday_newgoods_id");
        if ($everyday_newgoods_id > 0) {
            // 编辑
            M("everyday_newgoods")
                ->where(['everyday_newgoods_id' => $everyday_newgoods_id])
                ->save($data);
        } else {
            $data['date_add'] = time();
            // 添加
            $res = D("home_model")->addNew($data);
        }

		$this->log("新增每日上新产品,goods_id =".$goods_id);
        $this->success("操作成功",null,U('homeaction/newindex',['special_id' => $special_id]));
	}
	
	public function delNew(){
		!($id = I("everyday_newgoods_id")) && $this->error("请传入id");
		
		$res = D("home_model")->delNew($id);
		
		if ($res === false){
			$this->error("操作失败");
		}
		$this->log("删除每日上新产品,id=".$id);
		$this->success("操作成功",null,U("homeaction/newindex"));
	}
	
	public function appAcIndex(){
		$activities = D("home_model")->getAppHomeActivity();
		
		$this->assign("activities",$activities);
		$this->display();
	}
	
	//新增首页活动
	public function toAppEdit(){
		$activity_id = (int)I("home_activity_id");
		$result = [];
		$categories = D("home_model")->getActivity();
		$actions = M("action")->field("action_id,name")->select();
		if($activity_id){
			$result = D("home_model")->getAppActivityById($activity_id);
		}else {
			$result = array("img" => '','special_id' => '','status' => 1,'home_activity_id' => 0,'action_id' => 0);
		}
	
		$this->assign("activity",$result);
		$this->assign("categories",$categories);
		$this->assign("actions",$actions);
		$this->display();
	}
	
	public function editAppActivity(){
		$activity_id = (int)I("home_activity_id");
		$data['special_id'] = I("special_id");
		$data['img'] = I("image");
		$data['status'] = I("status");
		!($action_id = I("action_id")) && $this->error("请选择跳转事件");
		$data['action_id'] = $action_id;
		if($activity_id){
			$data['date_upd'] = time();
			$data['home_activity_id'] = $activity_id;
			$this->log("更新首页活动".$data['special_id']);
			$res = D("home_model")->saveAppActivity($data);
			if ($res === false){
				$this->error("操作失败");
			}
		}else {
			$data['date_add'] = time();
			$data['date_upd'] = time();
			$activity = M("app_home_activity")->where(['special_id' => $data['special_id']])->find();
			if (!empty($activity)){
				$this->error("该活动已添加");
			}
			$this->log("新增首页活动".$data['special_id']);
			$res = D("home_model")->addAppActivity($data);
			if ($res === false){
				$this->error("操作失败");
			}
		}
		$this->success("操作成功", null, U('homeaction/appAcIndex'));
	}
	
	public function isAppOpen(){
		$activity_id = (int)I("id");
		$status = (int)I("is_open");
		$res = D("home_model")->isAppOpen($activity_id,$status);
		$res ? $json = array('status'=>1) : $json = array('status'=>0);
		$this->ajaxReturn($json);
	}
	
	public function appToDel(){
		$activity_id = (int)I("home_activity_id");
		$res = D("home_model")->toAppDel($activity_id);
		if ($res === false){
			$this->error("操作失败");
		}
		$this->log(session("userid"),"删除首页活动".$activity_id);
		$this->success("操作成功");
	}

	public function information() {
	    $id = 1;
	    if (IS_POST) {
            !($image = I("image")) && $this->error("请传入图片");

            $data = [
                'image' => $image,
            ];
            if (empty($id)) {
                $data['date_add'] = time();
                $data['date_upd'] = time();
                M('information_image')->add($data);
            } else {
                $data['date_upd'] = time();
                M('information_image')->where(['id' => $id])->save($data);
            }
            $this->success("成功");
        }
        if (empty($id)) {
            $information = [
                'id' => '',
                'image' => ''
            ];
        } else {
            $information = M('information_image')->where(['id' => $id])->find();
        }

        $this->assign("id",$id);
        $this->assign("information",$information);
        $this->display();
    }

	public function information_index() {
        $count = M('information')->count();
        $page = new \com\Page($count, 15);

	    $host = $this->image_url;
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 150);

	    $information = M('information')
            ->field("*,concat('$host',image,'$suffix') as image")
            ->order("date_add DESC")
            ->limit($page->firstRow ."," . $page->listRows)
            ->select();
        $this->assign("information",$information);
        $this->assign("page", $page->show());
        $this->display();
    }

	public function information_add() {
	    $id = I('id');
        if(IS_POST){
            !($name = I('name')) && $this->error("请输入资讯名称");
            !($image = I("image")) && $this->error("请传入图片");
            !($mini_content = I("mini_content")) && $this->error("请传入简介");
            $content = htmlspecialchars(I('post.editorValue'),ENT_QUOTES);

            $data = [
                'name' => $name,
                'image' => $image,
                'mini_content' => $mini_content,
                'content' => $content,
            ];
            if (empty($id)) {
                $data['date_add'] = time();
                $data['date_upd'] = time();
                M('information')->add($data);
                $this->success("添加成功");
            } else {
                $data['date_upd'] = time();
                M('information')->where(['id' => $id])->save($data);
                $this->success("修改成功");
            }
        }
        if (!empty($id)) {
            $infomation = M('information')->where(['id' => $id])->find();
        } else {
            $infomation = M('information')->getEmptyFields();
        }
        $this->assign("infomation",$infomation);
        $this->display();
    }

    public function information_del() {
        !($id = I('id')) && $this->error("参数错误");
        M("information")->where(['id' => $id])->delete();
        $this->success("操作成功");
    }

    public function goods(){
        !($home_category_id = I("home_category_id")) && $this->error("请传入home_category_id");
        $map = ['hcg.home_category_id' => $home_category_id,'g.is_delete' => 0,'g.on_sale' => 1];
        $goods_name = I("goods_name");
        $min_date = I("min_date");
        $max_date = I("max_date");
        $goods_state = (int)I("goods_state");
        $seller_id = I("seller_id");
        if (!empty($goods_name)){
            $map['g.name'] = ['like','%'.$goods_name.'%'];
        }
        if (!empty($min_date)){
            $map['hcg.date_add'] = ['egt',strtotime($min_date)];
        }
        if(!empty($max_date)){
            if (!empty($min_date)){
                $map['hcg.date_add'][] = ['elt',strtotime($max_date)];
            }else {
                $map['hcg.date_add'] = ['elt',strtotime($max_date)];
            }
        }
        if ($goods_state == 1){
            $map['hcg.status'] = 1;
        }elseif ($goods_state == 2){
            $map['hcg.status'] = 0;
        }
        if (!empty($seller_id)){
            $map['g.seller_id'] = $seller_id;
        }
        $goods = D("special")->getHomeCategoryGoods($home_category_id,$map);

        $this->assign("goods",$goods['goods']);
        $this->assign('page',$goods['page']->show());
        $this->assign("home_category_id",$home_category_id);
        $this->display();
    }

    public function goodsAdd(){
        !($home_category_id = I("home_category_id")) && $this->error("请传入home_category_id");
        $home_category_goods_id = I("home_category_goods_id");
        if (IS_POST){
            !($data['goods_id'] = I("goods_id")) && $this->error("请传入商品");

            $data['sort'] = (int)I("sort");
            $data['image'] = !(I("image")) ? null : I("image");
            if ($home_category_goods_id){
                $data['date_upd'] = time();
                $res = M("home_category_goods")->where(['home_category_goods_id' => $home_category_goods_id])->save($data);
                if ($res === false){
                    $this->error("操作失败");
                }
                $this->log("更新首页分类_".$home_category_id."商品_".$data['goods_id']);
                $this->success("操作成功",null,U("homeaction/goods",['home_category_id' => $home_category_id]));
            }else {
                $data['date_add'] = time();
                $data['date_upd'] = time();
                $data['home_category_id'] = $home_category_id;
                $res = M("home_category_goods")->add($data);
                if($res === false){
                    $this->error("操作失败");
                }
                $this->log("添加专题_".$home_category_id."商品_".$data['goods_id']);
                $this->success("操作成功",null,U("homeaction/goods",['home_category_id' => $home_category_id]));
            }
        }else {
            if ($home_category_goods_id){
                $goods = M("home_category_goods")
                    ->alias("hcg")
                    ->join("goods g",'g.goods_id = hcg.goods_id')
                    ->join("seller_shopinfo ss",'ss.seller_id = g.seller_id',"left")
                    ->where(['hcg.home_category_goods_id' => $home_category_goods_id])
                    ->field("g.goods_id,g.name,ifnull(ss.shop_name,'未找到关联店铺') as shop_name,hcg.*")
                    ->find();

                $this->assign("goods",$goods);
                $this->assign("home_category_id",$home_category_id);
                $this->display();
            }else {
                $goods = array(
                    'name' => '',
                    'shop_name' => '',
                    'goods_id' => '',
                    'quantity' => '',
                    'home_category_goods_id' => 0,
                    "image" => "",
                    "sort" => 0
                );
                $this->assign("goods",$goods);
                $this->assign("home_category_id",$home_category_id);
                $this->display();
            }
        }
    }

    public function goodsDel(){
        !($home_category_goods_id = I("home_category_goods_id")) && $this->error("请传入id");

        $res = M("home_category_goods")->where(['home_category_goods_id' => $home_category_goods_id])->delete();

        if ($res === false){
            $this->error("操作失败");
        }

        $this->log("分类商品，home_category_goods_id".$home_category_goods_id."被删除");
        $this->success("成功");
    }

    public function appgoods(){
        !($home_activity_id = I("home_activity_id")) && $this->error("请传入home_activity_id");
        $map = ['ahag.home_activity_id' => $home_activity_id,'g.is_delete' => 0,'g.on_sale' => 1];
        $goods_name = I("goods_name");
        $min_date = I("min_date");
        $max_date = I("max_date");
        $goods_state = (int)I("goods_state");
        $seller_id = I("seller_id");
        if (!empty($goods_name)){
            $map['g.name'] = ['like','%'.$goods_name.'%'];
        }
        if (!empty($min_date)){
            $map['ahag.date_add'] = ['egt',strtotime($min_date)];
        }
        if(!empty($max_date)){
            if (!empty($min_date)){
                $map['ahag.date_add'][] = ['elt',strtotime($max_date)];
            }else {
                $map['ahag.date_add'] = ['elt',strtotime($max_date)];
            }
        }
        if ($goods_state == 1){
            $map['ahag.status'] = 1;
        }elseif ($goods_state == 2){
            $map['ahag.status'] = 0;
        }
        if (!empty($seller_id)){
            $map['g.seller_id'] = $seller_id;
        }
        $goods = D("special")->getAppHomeActivityGoods($home_activity_id,$map);

        $this->assign("goods",$goods['goods']);
        $this->assign('page',$goods['page']->show());
        $this->assign("home_activity_id",$home_activity_id);
        $this->display();
    }

    public function appgoodsAdd(){
        !($home_activity_id = I("home_activity_id")) && $this->error("请传入home_activity_id");
        $home_activity_goods_id = I("home_activity_goods_id");
        if (IS_POST){
            !($data['goods_id'] = I("goods_id")) && $this->error("请传入商品");

            $data['sort'] = (int)I("sort");
            $data['image'] = !(I("image")) ? null : I("image");
            if ($home_activity_goods_id){
                $data['date_upd'] = time();
                $res = M("app_home_activity_goods")->where(['home_activity_goods_id' => $home_activity_goods_id])->save($data);
                if ($res === false){
                    $this->error("操作失败");
                }
                $this->log("更新首页分类_".$home_activity_id."商品_".$data['goods_id']);
                $this->success("操作成功",null,U("homeaction/appgoods",['home_activity_id' => $home_activity_id]));
            }else {
                $data['date_add'] = time();
                $data['date_upd'] = time();
                $data['home_activity_id'] = $home_activity_id;
                $res = M("app_home_activity_goods")->add($data);
                if($res === false){
                    $this->error("操作失败");
                }
                $this->log("添加专题_".$home_activity_id."商品_".$data['goods_id']);
                $this->success("操作成功",null,U("homeaction/appgoods",['home_activity_id' => $home_activity_id]));
            }
        }else {
            if ($home_activity_goods_id){
                $goods = M("app_home_activity_goods")
                    ->alias("ahag")
                    ->join("goods g",'g.goods_id = ahag.goods_id')
                    ->join("seller_shopinfo ss",'ss.seller_id = g.seller_id',"left")
                    ->where(['ahag.home_activity_goods_id' => $home_activity_goods_id])
                    ->field("g.goods_id,g.name,ifnull(ss.shop_name,'未找到关联店铺') as shop_name,ahag.*")
                    ->find();

                $this->assign("goods",$goods);
                $this->assign("home_activity_id",$home_activity_id);
                $this->display();
            }else {
                $goods = array(
                    'name' => '',
                    'shop_name' => '',
                    'goods_id' => '',
                    'quantity' => '',
                    'home_activity_goods_id' => 0,
                    "image" => "",
                    "sort" => 0
                );
                $this->assign("goods",$goods);
                $this->assign("home_activity_id",$home_activity_id);
                $this->display();
            }
        }
    }

    public function appgoodsDel(){
        !($home_activity_goods_id = I("home_activity_goods_id")) && $this->error("请传入id");

        $res = M("app_home_activity_goods")->where(['home_activity_goods_id' => $home_activity_goods_id])->delete();

        if ($res === false){
            $this->error("操作失败");
        }

        $this->log("分类商品，home_category_goods_id".$home_activity_goods_id."被删除");
        $this->success("成功");
    }
}