<?php
namespace app\admin\controller;

use app\admin\Admin;
class Seller extends Admin{
	public function searchShop(){
		$term = I('term');
		$shop = M("seller_shopinfo")->where(["shop_name" => ['like',"%$term%"]])->field("seller_id,shop_name")->limit(20)->select();
		
		echo json_encode($shop);
		die();
	}
	
	public function applyIndex(){
		$map['c.active'] = 1;
		$min_date = I("min_date");
		$max_date = I("max_date");
		$state = I("state");
		$check_id = I("check_id");
		$realname = I("realname");
		
		if (!empty($min_date)){
			$map['sc.date_add'] = ['egt',$min_date];
		}
		
		if (!empty($max_date)){
			if (!empty($min_date)){
				$map['sc.date_add'][] = ['elt',$max_date];
			}else {
				$map['sc.date_add'] = ['elt',$max_date];
			}
		}
		if (in_array($state, [1,2,3])){
			$map['sc.state'] = $state;
		}
		if (!empty($check_id)){
			$map['sc.check_id'] = $check_id;
		}
		if (!empty($realname)){
			$map['c.realname'] = $realname;
		}

		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$count = M("seller_check")
		->alias("sc")
		->join("customer c",'sc.customer_id = c.customer_id')
		->join("zone z",'z.zone_id = sc.province')
		->join("zone z2","z2.zone_id = sc.city")
        ->join("zone z3","z3.zone_id = sc.district")
        ->where($map)
		->count();
		
		$page = new \com\Page($count,15);
		
		
		$shop = M("seller_check")
		->alias("sc")
		->join("customer c",'sc.customer_id = c.customer_id')
        ->join("zone z",'z.zone_id = sc.province')
        ->join("zone z2","z2.zone_id = sc.city")
        ->join("zone z3","z3.zone_id = sc.district")
		->field("sc.check_id,sc.company_name,sc.address,concat('$host',sc.licence) as licence,sc.contact_people_name,sc.phone,sc.shop_name,sc.qq_wx,sc.is_pay,
				concat('$host',sc.card_f) as card_f,concat('$host',sc.card_b) as card_b,sc.state,sc.date_add,c.realname,z.name as province_name,z2.name as city_name,z3.name as district_name,sc.reply_date,sc.reply_content,".
            "sc.is_pay,sc.cash_deposit")
		->where($map)
		->limit($page->firstRow.",".$page->listRows)
		->select();
		
		$this->assign("shop",$shop);
		$this->assign("page",$page->show());
		$this->display();
	}
	
	public function searchApplyShop(){
		$term = I('term');
		$shop = M("seller_check")->where(["shop_name" => ['like',"%$term%"]])->field("check_id,shop_name")->limit(20)->select();
		
		echo json_encode($shop);
		die();
	}
	
	public function check(){
		!($id = I("id")) && $this->error("请传入id");
		$state = I('state');
		$reply_content = I("reply_content");
		!($over_time = strtotime(I("over_time"))) && $this->error("请传入过期时间");
		
		if ($state == 2){
			$shop = M("seller_check")->where(['check_id' => $id])->find();
            $shop_address = M('zone')
                ->alias("z1")
                ->join("zone z2","z2.pid = z1.zone_id")
                ->join("zone z3","z3.pid = z2.zone_id")
                ->where(["z1.zone_id" => $shop['province'], 'z2.zone_id' => $shop['city'], 'z3.zone_id' => $shop['district']])
                ->field("concat(z1.name,z2.name,z3.name) as address")
                ->find();
            $shop['shop_address'] = $shop_address['address'].$shop['address'];
			$data = array(
				'shop_name' => $shop['shop_name'],
				'customer_id' => $shop['customer_id'],
				'province' => $shop['province'],
				'city' => $shop['city'],
				'area_id' => $shop['district'],
				'address' => $shop['address'],
				'shop_address' => $shop['shop_address'],
				'over_time' => $over_time,
				'date_add' => time(),
				'baicheng_apply' => 0,
				'is_baicheng' => 0,
				'is_youxiu' => 0
			);
			$res = M("seller_shopinfo")->add($data);
			if ($res === false){
				$this->error("操作失败");
			}
			$res = M("seller_check")->where(['check_id' => $id])->save(['state' => 2]);
			if ($res === false){
				$this->error("操作失败");
			}
			$this->log("审核店铺".M("seller_check")->where(['check_id' => $id])->getField("shop_name")."通过");
		} elseif ($state == 3){
			$res = M("seller_check")->where(['check_id' => $id])->save(['state' => 3,'reply_content' => $reply_content,'reply_date' => time(),'reply_user_id'=> session("userid")]);
			if ($res === false){
				$this->error("操作失败");
			}
			$this->log("审核拒绝店铺".M("seller_check")->where(['check_id' => $id])->getField("shop_name"));
		}
		$this->success("操作成功",null,U("seller/applyIndex"));
	}
	
	public function index(){
	    $host = \app\library\SettingHelper::get("shuaibo_image_url");
	    $seller_id = I("seller_id");
	    $is_overtime = I("is_overtime");
        $status = I("status");
        $is_type = I("is_type");
        
        $map = [];
        $map['ss.is_delete'] = 0;
        if (!empty($seller_id)){
            $map['ss.seller_id'] = $seller_id;
        }
        
        if ($is_overtime == 2 && $is_overtime != null){
            $map['ss.over_time'] = ['gt',time()];
        }elseif ($is_overtime == 1){
            $map['ss.over_time'] = ['lt',time()];
        }
        
        if ($status == 1){
            $map['ss.status'] = 1;
        }elseif ($status == 2 && $status != null){
            $map['ss.status'] = 0;
        }
        
        if ($is_type == 1){
            $map['_string'] = "ss.type is not null";
        }elseif ($is_type == 2){
            $map['_string'] = "ss.type is null";
        }
        
        $count = M("seller_shopinfo")
            ->alias("ss")
            ->where($map)
            ->count();
        
        $page = new \com\Page($count,15);
        
        $seller = M("seller_shopinfo")
            ->alias("ss")
            ->where($map)
            ->join("seller_user su",'su.seller_id = ss.seller_id',"LEFT")
            ->field("ss.seller_id,ss.status,ss.shop_name,concat('$host',ss.shop_logo) as shop_logo,ss.over_time,case when ss.type = 1 then '普通店铺' when ss.type = 2 then '旗舰店' when ss.type = 3 then '自营' else '未设置' end as type,ss.is_baicheng,".
                "IFNULL(su.username,'默认') as username")
            ->limit($page->firstRow.",".$page->listRows)
            ->order("date_add desc")
            ->select();
        
        $this->assign("seller",$seller);
        $this->assign("page",$page->show());
        $this->display();
	}

	public function index_excel() {
        $seller_id = I("seller_id");
        $is_overtime = I("is_overtime");
        $status = I("status");
        $is_type = I("is_type");

        $map = [];
        if (!empty($seller_id)){
            $map['ss.seller_id'] = $seller_id;
        }

        if ($is_overtime == 2 && $is_overtime != null){
            $map['ss.over_time'] = ['gt',time()];
        }elseif ($is_overtime == 1){
            $map['ss.over_time'] = ['lt',time()];
        }

        if ($status == 1){
            $map['ss.status'] = 1;
        }elseif ($status == 2 && $status != null){
            $map['ss.status'] = 0;
        }

        if ($is_type == 1){
            $map['_string'] = "ss.type is not null";
        }elseif ($is_type == 2){
            $map['_string'] = "ss.type is null";
        }

        $lists = M("seller_shopinfo")
            ->alias("ss")
            ->where($map)
            ->join("seller_user su",'su.seller_id = ss.seller_id',"LEFT")
            ->join("seller_check sc","sc.seller_id = ss.seller_id")
            ->field("ss.seller_id,ss.shop_name,su.id as user_id,IFNULL(su.username,'默认') as username,sc.contact_people_name,sc.phone,ss.shop_address,case when ss.type = 1 then '普通店铺' when ss.type = 2 then '旗舰店' when ss.type = 3 then '自营' else '未设置' end as type,case when ss.status = 0 then '关闭' when ss.status = 1 then '开启' else '无' end as status")
            ->group("ss.seller_id")
            ->select();

        $filename="店铺列表";
        $headArr=array("店铺ID","商家名称","入驻商家ID","入驻商家名称","联系人","联系电话","所在地","店铺类型" ,"店铺状态");
        $this->getExcel($filename,$headArr,$lists);
    }
	
	public function setType(){
	    !($id = (int)I('post.id')) && $this->error('参数错误!','',U('seller/index'));
	    $type = (int)I('post.type');

	    $res = M("seller_shopinfo")->where(array('seller_id'=>$id))->save(array('type'=>$type));
	    $res ? $json = array('status'=>1) : $json = array('status'=>0);
	    $this->log("更改店铺：".M("seller_shopinfo")->where(['seller_id' => $id])->getField("shop_name")."类型为:".$type);
	    $this->ajaxReturn($json);
	}
	
	public function on(){
	    !($seller_id = I('seller_id')) && $this->error("请传入商家id");
	    $res = M('seller_user')->where(['seller_id' => $seller_id])->save(['status' => 1]);
	    if ($res === false){
	        $this->error("操作失败");
	    }
	    $res = M("seller_shopinfo")->where(['seller_id' => $seller_id])->save(['status' => 1]);
	    if ($res === false){
	        $this->error("操作失败");
	    }
	    $this->log("启用店铺:".M("seller_shopinfo")->where(['seller_id' => $seller_id])->getField("shop_name"));
	    $this->success("操作成功",null,U('Seller/index'));
	}
	
	public function off(){
	    !($seller_id = I('seller_id')) && $this->error("请传入商家id");
	    $res = M('seller_user')->where(['seller_id' => $seller_id])->save(['status' => 0]);
	    if ($res === false){
	        $this->error("操作失败");
	    }
	    $res = M("seller_shopinfo")->where(['seller_id' => $seller_id])->save(['status' => 0]);
	    if ($res === false){
	        $this->error("操作失败");
	    }
	    $this->log("禁用店铺:".M("seller_shopinfo")->where(['seller_id' => $seller_id])->getField("shop_name"));
	    $this->success("操作成功",null,U('Seller/index'));
	}

	public function protocol() {
        if(IS_POST){
            $protocol = htmlspecialchars(I('post.editorValue'), ENT_QUOTES);
            \app\library\SettingHelper::set("shuaibo_seller_protocol", $protocol);
            $this->success("修改成功");
        }
        $protocol = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_seller_protocol"), ENT_QUOTES) ;
        $this->assign("protocol", $protocol);
        $this->display();
    }

    public function bail() {
        !($check_id = I('id')) && $this->error("请传入id");

        // 缴纳保证金成功处理
        M('seller_check')
            ->where(['check_id' => $check_id])
            ->save(['cash_deposit' => 0,'is_pay' => 2]);

        $customer = M('seller_check')
            ->alias("sc")
            ->where(['sc.check_id' => $check_id])
            ->join("customer c","c.customer_id = sc.customer_id")
            ->join("seller_shopinfo ss","ss.customer_id = c.customer_id")
            ->field("c.customer_id,c.nickname,c.phone,ss.seller_id")
            ->find();

        M("seller_user")->add(array('username' => $customer['nickname'],'phone' => $customer['phone'],'seller_id' => $customer['seller_id'],'password' => md5(md5('888888')),'status' => 1,'customer_id' => $customer['customer_id']));
        $this->success("成功");
    }

    /**
     * 重置密码
     */
    public function reset_passwd() {
        $id = I("seller_id");
        $seller = M("seller_user")->where(['seller_id' => $id])->find();
        if (empty($seller)) {
            $this->error("未找到对应商家");
        }
        M('seller_user')->where(['seller_id' => $id])->save(['password' => md5(md5('888888')), 'ec_salt' => NULL]);
        $this->success('成功');
    }

    /**
     * 删除店铺
     */
    public function delete() {
        $id = I("seller_id");
        M("seller_user")->where(['seller_id' => $id])->save(['status' => 0]);
        M('seller_shopinfo')->where(['seller_id' => $id])->save(['status' => 0, 'is_delete' => 1]);
        $this->success('成功');
    }
}