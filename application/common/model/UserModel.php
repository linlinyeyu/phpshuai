<?php
namespace app\common\model;
use think\Model;
class UserModel extends Model{
    // 新增
    public function myAdd($data) {
        return M('customer')->add($data);
    }

    //更新
    public function mySave($condition,$data){
        return M('customer')->where($condition)->save($data);
    }

    //查找
    public function myFind($condtion,$field="*"){
        return M('customer')->field($field)->where($condtion)->find();
    }






	public function getCart($customer_id){
		$orders = M("order")
		->alias("o")
		->join("order_address oa","oa.id = o.address_id")
		->join("order_state os","os.order_state_id = o.order_state")
		->field("oa.name, oa.phone,o.id,o.order_sn,o.order_amount,o.express_amount,o.date_end,os.name as state")
		->where(['o.date_end' => ['gt', time()], 'o.customer_id' => $customer_id,'o.order_state' => 1])
		->order("o.date_add ")
		->select();
		
		$order_ids = [];
		$order_tmp = [];
		foreach ($orders as &$o){
			$order_ids[] = $o['id'];
			$o['goods'] = [];
			$o['goods_count'] = 0;
			$order_tmp[$o['id']] = $o;
		}
		if(count($orders) > 0){
			$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
			$host = \app\library\SettingHelper::get("bear_image_url");
			$order_goods = M("order_goods")
			->alias("og")
			->join("goods g","g.goods_id = og.goods_id")
			->join("goods_option go","go.goods_id = g.goods_id and og.option_id = go.id","LEFT")
			->field("g.name,og.order_id , og.quantity,og.price, ifnull(go.name,'默认') as option_name, ifnull(go.stock,g.quantity) as stock, ".
					"g.on_sale,concat('$host', g.cover, '$suffix') as image")
					->where(['og.order_id' => ['in', join(",", $order_ids) ]])
					->select();
				
			foreach ($order_goods as $g){
				$order_tmp[$g['order_id']]['goods_count'] += $g['quantity'];
				$order_tmp[$g['order_id']]['goods'][] = $g;
			}
			$orders = array_values($order_tmp);
		}
		return $orders;
	}
	public function getUserInfo($customer_id){
		$customer = D("customer_model")->GetUserInfo($customer_id);
        if(empty($customer)){
            return ['errcode' => -100 , 'message' => '未找到该用户'];
        }else{
            $data['account'] = $customer['account'];
            $data['integration'] = $customer['integration'];
            $data['avater'] = $customer['avater'];
            $data['nickname'] = $customer['nickname'];
            $data['realname'] = $customer['realname'];
            $data['sex'] = $customer['sex'];
            $data['shopping_coin'] = $customer['shopping_coin'];
            $data['phone'] = $customer['phone'];
            $data['real_phone'] = $customer['real_phone'];
            $data['birthday'] = $customer['birthday'];
            $data['has_phone'] = $customer['has_phone'];
            $data['has_email'] = $customer['has_email'];
            $data['email'] = $customer['email'];
            $data['access_token'] = $customer['access_token'];
            $data['active'] = $customer['active'];
            $data['last_check_date'] = $customer['last_check_date'];
            $data['cart_num'] = $customer['cart_num'];
            $data['reward_amount'] = $customer['reward_amount'];
            $data['hongfu'] = $customer['hongfu'];
            $data['transfer_amount'] = $customer['transfer_amount'];

            //订单
            $count = D("order_model")->getOrderCount($customer_id);
            $order['wait_pay_count'] = $count[0];
            $order['wait_delivery_count'] = $count[1];
            $order['wait_receive_count'] = $count[2];
            $order['wait_comment_count'] = $count[3];
            $order['wait_refund_count'] = $count[4];
            $data['order'] = $order;

            //收藏
            $collect_count = D("collection_model")->getCollectCount($customer_id);
            $data['collection_count'] = $collect_count;

            //足迹
            $trail = D("trail_model")->getTrailCount($customer_id);
            $data['trail'] = $trail;

            //关注
            $follow = D("follow_model")->getFollowCount($customer_id);
            $data['follow'] = $follow;

            $res['content'] = $data;
        }
        return ['errcode' => 0, 'message' => '获取成功','content' => $data];
	}
	public function qrcode($customer_id){
		
		$customer = M("customer")
		->where(['customer_id' => $customer_id])
		->field("nickname,group_id,qrcode_number ,ticket,avater,qrcode, customer_id , uuid")
		->find();
		if($customer['group_id'] <= 2){
			return ['errcode' => -102 , 'message' => '人员等级不足'];
		}
		$result = \app\library\QrcodeHelper::getInstance()->generate_customer1($customer);
		if($result['errcode'] == 0){
			$result['content']['uuid'] = $customer['uuid'];
		}
		return $result;
	}
	public function getmycustomer($customer_id){
		$map = ['ce.pid' => $customer_id,"cer.state" => ['in','1,3'], 'ce.commission' => ['gt' , 0]];
		
		$total_sql = M("customer")
		->alias("c")
		->join("customer_extend ce" ,"ce.customer_id = c.customer_id ")
		->join("customer_extend_record cer","cer.customer_extend_id = ce.customer_extend_id","LEFT")
		->group("c.customer_id")
		->field("count(1) as count")
		->having("count(c.customer_id) > 0")
		->where($map)->buildSql();
		$map['ce.level'] = 1;
		$level_1_sql = M("customer")
		->alias("c")
		->join("customer_extend ce" ,"ce.customer_id = c.customer_id ")
		->join("customer_extend_record cer","cer.customer_extend_id = ce.customer_extend_id ","LEFT")
		->group("c.customer_id")
		->field("count(1) as count")
		->having("count(c.customer_id) > 0")
		->where($map)
		->buildSql();
		
		$map['ce.level'] = 2;
		$level_2_sql = M("customer")
		->alias("c")
		->join("customer_extend ce" ,"ce.customer_id = c.customer_id ")
		->join("customer_extend_record cer","cer.customer_extend_id = ce.customer_extend_id","LEFT")
		->group("c.customer_id")
		->field("count(1) as count")
		->having("count(c.customer_id) > 0")
		->where($map)
		->buildSql();
		
		$map['ce.level'] = 3;
		$level_3_sql = M("customer")
		->alias("c")
		->join("customer_extend ce" ,"ce.customer_id = c.customer_id ")
		->join("customer_extend_record cer","cer.customer_extend_id = ce.customer_extend_id","LEFT")
		->group("c.customer_id")
		->field("count(1) as count")
		->having("count(c.customer_id) > 0")
		->where($map)
		->buildSql();
		
		$sql = "select ifnull((select count(1) from $total_sql t),0) as total, ifnull((select count(1) from $level_1_sql t),0) as level_1, ifnull((select count(1) from $level_2_sql t) ,0) as level_2, ifnull( (select count(1) from $level_3_sql t),0) as level_3 from dual";
		$level_counts = M()->query($sql);
		$level_counts = $level_counts[0];
		return $level_counts;
	}
	public function getgoalcustomer($customer_id){
		$count = M("customer_extend")->where(['level' => 1,'pid' => $customer_id ,'commission' => 0])->count();
		
		return $count;
	}
	public function changeaddress($customer_id){
		$customer = M("customer")->where(['customer_id' => $customer_id])->field("province,city")->find();
		$hotcity = [
				['province' => '北京市','city' => '北京市'],
				['province' => '上海市','city' => '上海市'],
				['province' => '广东省', 'city' => '广州市'],
				['province' => '浙江省','city' => '宁波市'],
				['province' => '广东省','city' => '深圳市'],
				['province' => '浙江省','city' => '杭州市'],
				['province' => '浙江省','city' => '温州市'],
		];
		$content=[];
		$content['customer']=$customer;
		$content['hotcity']=$hotcity;
		return $content;
	}
	public function changephone($customer_id){
		$customer = M("customer")->where(['customer_id'=>$customer_id])->field("phone,customer_id")->find();
		if(!empty($customer['phone'])){
			$phone = substr_replace($customer['phone'],'******',3,6);
			$content['phone']=$phone;
		}
		$content=[];
		$content['customer']=$customer;
		return $content;
	}

    /**
     * 获取优惠券
     * @param $customer_id
     * @param $goods
     * @return array
     */
    public function getCoupons($customer_id,$type) {
        $coupons = M("customer_coupon")
            ->alias("cc")
            ->join("coupon c","c.coupon_id = cc.coupon_id AND c.active = 1")
            ->join("coupon_type ct","ct.type_id = c.type")
            ->where(['cc.customer_id' => $customer_id])
            ->field("c.name,c.amount,c.limit,cc.customer_coupon_id as coupon_id,cc.date_start,cc.date_end,cc.state,ct.name as issuer")
            ->order("cc.date_add DESC")
            ->select();
        $content = [
            'usable_coupons' => [],
            'unusable_coupons' => []
        ];
        foreach ($coupons as $coupon) {
            if ($coupon['state'] == 0) {
                $content['usable_coupons'][] = $coupon;
            } else {
                $content['unusable_coupons'][] = $coupon;
            }
        }
        if ($type == 1) {
            $coupons = $content['usable_coupons'];
        } else {
            $coupons = $content['unusable_coupons'];
        }

        return ['errcode' => 0, 'message' =>'成功', 'content' => $coupons];
    }

    /**
     * 余额列表
     * @param $customer_id
     * @return array
     */
    public function balance($customer_id) {
        $customer = M('customer')
            ->where(['customer_id' => $customer_id])
            ->field("account,integration,shopping_coin")
            ->find();
        $customer['detail'] = M('finance')
            ->where(['customer_id' => $customer_id,'finance_type_id' => 3])
            ->field("date_add,comments,title")
            ->order("date_add DESC")
            ->limit(3)
            ->select();
        return ['errcode' => 0, 'message' => '成功', 'content' => $customer];
    }

    /**
     * 资金明细
     * @param $customer_id
     * @param $page
     * @return array
     */
    public function finance($customer_id,$page) {
        $finance = M('finance')
            ->where(['customer_id' => $customer_id,'finance_type_id' => 3,'type' => ['in',"1,2,3,4,5,12,16"]])
            ->field("date_add,comments,title")
            ->order("date_add DESC")
            ->page($page)
            ->limit(10)
            ->select();
        $count = M('finance')
            ->where(['customer_id' => $customer_id,'finance_type_id' => 3])
            ->count();
        $pageSize = $count % 10 > 0 ? intval($count / 10 + 1) : intval($count / 10);
        return ['errcode' => 0, 'message' => '成功', 'content' => $finance, 'pageSize' => $pageSize];
    }

    /**
     * 会员积分列表
     * @param $customer_id
     * @return array
     */
    public function shoppingCoin($customer_id) {
        $customer = M('customer')
            ->where(['customer_id' => $customer_id])
            ->field("shopping_coin")
            ->find();
        $customer['detail'] = M('finance')
            ->where(['customer_id' => $customer_id,'finance_type_id' => 2])
            ->field("date_add,comments,title")
            ->order("date_add DESC")
            ->limit(3)
            ->select();
        return ['errcode' => 0, 'message' => '成功', 'content' => $customer];
    }

    /**
     * 会员积分明细
     * @param $customer_id
     * @param $page
     * @return array
     */
    public function shoppingCoinDetail($customer_id,$page) {
        $finance = M('finance')
            ->where(['customer_id' => $customer_id,'finance_type_id' => 2])
            ->field("date_add,comments,title")
            ->order("date_add DESC")
            ->page($page)
            ->limit(10)
            ->select();
        $count = M('finance')
            ->where(['customer_id' => $customer_id,'finance_type_id' => 2])
            ->count();
        $pageSize = $count % 10 > 0 ? intval($count / 10 + 1) : intval($count / 10);
        return ['errcode' => 0, 'message' => '成功', 'content' => $finance, 'pageSize' => $pageSize];
    }

    /**
     * 积分列表
     * @param $customer_id
     * @return array
     */
    public function integration($customer_id) {
        $customer = M('customer')
            ->where(['customer_id' => $customer_id])
            ->field("integration")
            ->find();
        $customer['detail'] = M('finance')
            ->where(['customer_id' => $customer_id,'finance_type_id' => 1])
            ->field("date_add,comments,title")
            ->order("date_add DESC")
            ->limit(3)
            ->select();
        return ['errcode' => 0, 'message' => '成功', 'content' => $customer];
    }

    /**
     * 积分明细
     * @param $customer_id
     * @param $page
     * @return array
     */
    public function integrationDetail($customer_id,$page) {
        $finance = M('finance')
            ->where(['customer_id' => $customer_id,'finance_type_id' => 1])
            ->field("date_add,comments,title")
            ->order("date_add DESC")
            ->page($page)
            ->limit(10)
            ->select();
        $count = M('finance')
            ->where(['customer_id' => $customer_id,'finance_type_id' => 1])
            ->count();
        $pageSize = $count % 10 > 0 ? intval($count / 10 + 1) : intval($count / 10);
        return ['errcode' => 0, 'message' => '成功', 'content' => $finance, 'pageSize' => $pageSize];
    }

    /**
     * 收藏列表
     * @param $customer_id
     * @param $page
     * @return array
     */
    public function collection($customer_id,$page) {
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        $collections = M('collection')
            ->alias('c')
            ->where(['c.customer_id' => $customer_id])
            ->join("goods g","g.goods_id = c.goods_id AND g.on_sale = 1 AND g.is_delete = 0")
            ->field("c.collection_id,g.goods_id,g.name,concat('$host',g.cover,'$suffix') as cover,g.shop_price as price,g.sale_count")
            ->order("c.date_add DESC")
            ->page($page)
            ->limit(10)
            ->select();
        $count = M('collection')
            ->alias('c')
            ->where(['c.customer_id' => $customer_id])
            ->join("goods g","g.goods_id = c.goods_id AND g.on_sale = 1 AND g.is_delete = 0")
            ->count();
        $pageSize = $count % 10 > 0 ? intval($count / 10 + 1) : intval($count / 10);
        return ['errcode' => 0, 'message' => '成功', 'content' => $collections, 'pageSize' => $pageSize];
    }

    /**
     * 取消收藏
     * @param $customer_id
     * @param $ids
     * @return array
     */
    public function cancelCollections($customer_id,$ids) {
        $ids = explode(",",$ids);
        M('collection')->where(['customer_id' => $customer_id, 'collection_id' => ['in',$ids]])->delete();
        return ['errcode' => 0, 'message' => '成功'];
    }

    /**
     * 关注
     * @param $customer_id
     * @param $page
     * @return array
     */
    public function attention($customer_id,$page) {
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        $suffix_goods = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
        // 今日上新
        $sql = "(SELECT COUNT(g.goods_id) FROM vc_goods g WHERE g.seller_id = ss.seller_id AND g.date_add >= ".strtotime(date("Y-m-d"),time()).")";
        // 本周上新
        $week_sql = "(SELECT COUNT(g.goods_id) FROM vc_goods g WHERE g.seller_id = ss.seller_id AND g.date_add >= ".strtotime(date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")))).")";
        // 优惠
        $coupon_sql = "(SELECT COUNT(g.goods_id) FROM vc_goods g WHERE g.seller_id = ss.seller_id AND g.shop_price < g.market_price)";
        // 热销
        $hot_sql = "(SELECT COUNT(g.goods_id) FROM vc_goods g WHERE g.seller_id = ss.seller_id AND g.sale_count > 100)";

        $attentions = M('seller_follow')
            ->alias('sf')
            ->where(['sf.user_id' => $customer_id])
            ->join("seller_shopinfo ss","ss.seller_id = sf.seller_id AND ss.status = 1")
            ->field("sf.id as attention_id,ss.seller_id,ss.shop_name,concat('$host',ss.shop_logo,'$suffix') as cover,".
                "ifnull($sql,0) as new_count,IFNULL($week_sql,0) as week_count,IFNULL($coupon_sql,0) as coupon_count,IFNULL($hot_sql,0) as hot_count")
            ->order("sf.date_add DESC")
            ->page($page)
            ->limit(10)
            ->select();

        foreach ($attentions as &$attention) {
            $goods_comment = M("order_comment")
                ->where(['is_deleted' => 0, 'type' => 1,'seller_id' => $attention['seller_id']])
                ->field("avg(score)")
                ->buildSql();

            $service_comment = M("order_comment")
                ->where(['is_deleted' => 0, 'type' => 2,'seller_id' => $attention['seller_id']])
                ->field("avg(service_score)")
                ->buildSql();

            $logistics_comment = M("order_comment")
                ->where(['is_deleted' => 0, 'type' => 2,'seller_id' => $attention['seller_id']])
                ->field("avg(logistics_score)")
                ->buildSql();

            $attention['comment'] = M("order_comment")
                ->alias("c")
                ->where(['seller_id' => $attention['seller_id']])
                ->field("format(ifnull($goods_comment,5),1) as goods_comment, format(ifnull($service_comment,5),1) as service_comment, format(ifnull($logistics_comment,5),1) as logistics_comment")
                ->find();

            $attention['week'] = M('goods')
                ->where(['seller_id' => $attention['seller_id'], 'date_add' => ['egt',strtotime(date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y"))))]])
                ->field("goods_id,concat('$host',cover,'$suffix_goods') as cover,name,shop_price,market_price")
                ->select();

            $condition = [];
            $condition['seller_id'] = $attention['seller_id'];
            $condition['_string'] = "shop_price < market_price";
            $attention['coupon'] = M('goods')
                ->where($condition)
                ->field("goods_id,concat('$host',cover,'$suffix_goods') as cover,name,shop_price,market_price")
                ->select();

            $attention['hot'] = M('goods')
                ->where(['seller_id' => $attention['seller_id'], 'sale_count' => ['gt',100]])
                ->field("goods_id,concat('$host',cover,'$suffix_goods') as cover,name,shop_price,market_price")
                ->select();
        }

        $count = M('seller_follow')
            ->alias('sf')
            ->where(['sf.user_id' => $customer_id])
            ->join("seller_shopinfo ss","ss.seller_id = sf.seller_id AND ss.status = 1")
            ->count();
        $pageSize = $count % 10 > 0 ? intval($count / 10 + 1) : intval($count / 10);
        return ['errcode' => 0, 'message' => '成功', 'content' => $attentions, 'pageSize' => $pageSize];
    }

    /**
     * 取消关注
     * @param $customer_id
     * @param $ids
     * @return array
     */
    public function cancelAttentions($customer_id,$ids) {
        $ids = explode(",",$ids);
        M('seller_follow')->where(['user_id' => $customer_id, 'id' => ['in',$ids]])->delete();
        return ['errcode' => 0, 'message' => '成功'];
    }

    /**
     * 我的足迹
     * @param $customer_id
     * @param $page
     * @return array
     */
    public function footmark($customer_id,$page) {
        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(200, 200);
        $foots = M('trail')
            ->alias('t')
            ->where(['t.user_id' => $customer_id])
            ->join("goods g","g.goods_id = t.goods_id AND g.on_sale = 1 AND g.is_delete = 0")
            ->field("t.id as foot_id,t.date_add,g.goods_id,g.name,concat('$host',g.cover,'$suffix') as cover,g.shop_price as price,g.sale_count")
            ->order("t.date_add DESC")
            ->page($page)
            ->limit(10)
            ->select();
        $count = M('trail')
            ->alias('t')
            ->where(['t.user_id' => $customer_id])
            ->join("goods g","g.goods_id = t.goods_id AND g.on_sale = 1 AND g.is_delete = 0")
            ->count();
        $pageSize = $count % 10 > 0 ? intval($count / 10 + 1) : intval($count / 10);
        return ['errcode' => 0, 'message' => '成功', 'content' => $foots, 'pageSize' => $pageSize];
    }

    /**
     * 删除足迹
     * @param $customer_id
     * @param $ids
     * @return array
     */
    public function delFoots($customer_id,$ids) {
        $ids = explode(",",$ids);
        M('trail')->where(['user_id' => $customer_id, 'id' => ['in',$ids]])->delete();
        return ['errcode' => 0, 'message' => '成功'];
    }

    /**
     * 投诉
     * @param $customer_id
     * @param $title
     * @param $content
     * @return array
     */
    public function complain($customer_id,$title,$content) {
        $data = [
            'customer_id' => $customer_id,
            'title' => $title,
            'content' => $content,
            'date_add' => time()
        ];
        $id = M('complain')->add($data);
        if (empty($id)) {
            return ['errcode' => -200, 'message' => '添加失败'];
        }
        return ['errcode' => 0, 'message' => '添加成功'];
    }

    /**
     * 店铺佣金
     * @param $customer_id
     * @return array
     */
    public function commission($customer_id) {
        $seller_user = M("seller_user")->where(['customer_id' => $customer_id, 'status' => 1])->find();
        if (empty($seller_user)) {
            $check = M('seller_check')->where(['customer_id' => $customer_id,'state' => ['neq',3]])->field("state,is_pay")->find();
            if (empty($check)) {
                $bail = \app\library\SettingHelper::get("shuaibo_bail",['url' => "http://" . get_domain() ."/wap/home/protocol"]);
                return ['errcode' => 0, 'message' => '尚未申请店铺入驻', 'content' => ['errcode' => -100, 'message' => '尚未申请店铺入驻', 'url' => $bail['url']]];
            }
            if ($check['state'] == 1) {
                return ['errcode' => 0, 'message' => '审核中', 'content' => ['errcode' => -101, 'message' => '审核中']];
            }
            if ($check['state'] == 3) {
                $bail = \app\library\SettingHelper::get("shuaibo_bail",['url' => "http://" . get_domain() ."/wap/home/protocol"]);
                return ['errcode' => 0, 'message' => '审核拒绝', 'content' => ['errcode' => -102, 'message' => '审核拒绝', 'url' => $bail['url']]];
            }
            if ($check['is_pay'] == 1) {
                $bail = \app\library\SettingHelper::get("shuaibo_bail",['money' => 10000,'url' => "http://" . get_domain() ."/wap/home/protocol"]);
                return ['errcode' => 0, 'message' => '未缴纳保证金', 'content' => ['errcode' => -103, 'message' => '未缴纳保证金', 'bail' => $bail]];
            }
        }

        // 总佣金
        $total = M('finance_op')
            ->where(['customer_id' => $customer_id,'finance_type' => 1, 'is_minus' => 2])
            ->field("IFNULL(SUM(real_amount),0) as total")
            ->find();
        $content['commission'] = $total['total'];

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
            ->where(['ss.seller_id' => $seller_user['seller_id']])
            ->field("ifnull($sql1,0) as amount")
            ->group("fo.customer_id")
            ->find();

        // 可提现佣金
        $content['withdraw_commission'] = $commission['amount'];

        $content['detail'] = M('finance_op')
            ->where(['customer_id' => $customer_id])
            ->field("title,comments,date_add")
            ->order('date_add DESC')
            ->limit(3)
            ->select();
        return ['errcode' => 0, 'message' => '成功', 'content' => $content];
    }

    /**
     * 佣金明细
     * @param $customer_id
     * @param $page
     * @return array
     */
    public function commissionDetail($customer_id,$page) {
        $finance = M('finance_op')
            ->where(['customer_id' => $customer_id])
            ->field("date_add,comments,title")
            ->order("date_add DESC")
            ->page($page)
            ->limit(10)
            ->select();
        $count = M('finance_op')
            ->where(['customer_id' => $customer_id,'finance_type' => 1])
            ->count();
        $pageSize = $count % 10 > 0 ? intval($count / 10 + 1) : intval($count / 10);
        return ['errcode' => 0, 'message' => '成功', 'content' => $finance,'pageSize' => $pageSize];
    }

    /**
     * 缴纳保证金
     * @param $customer_id
     * @param $type
     * @param $sum
     * @return array
     */
    public function bail($customer_id,$type,$sum) {

        $helper = \app\library\order\OrderHelper::getInstance($type);

        if(empty($helper)){
            return ['errcode' => -101 , 'message' => '没有对应的支付方式'];
        }
        if(is_string($helper)){
            return ['errcode' => -102 , 'message' => $helper];
        }

        $pay_order_sn = createNo();

        $subject = "保证金" . $sum . "元";

        $helper->setOrderNumber($pay_order_sn)
            ->setOrderType(3)
            ->setBody($subject)
            ->setSubject($subject);

        $res = $helper->get_init_order($customer_id,$sum);

        if($res['errcode'] < 0){
            return $res;
        }
        $order = $helper->getOrder();

        M("order_info")->add($order);

        $return = $helper->getReturn();

        if($order['pay_id'] == 1){
            $notify = new \app\library\order\OrderNotify($order['order_sn'], $return['count'], $order['pay_id']);

            $notify->notify();
        }

        return ['errcode' => 0, 'message'=>'请求成功', 'content' => $return];
    }

    /**
     * 充值
     * @param $customer_id
     * @param $type
     * @param $sum
     * @return array
     */
    public function recharge($customer_id,$type,$sum) {
        $helper = \app\library\order\OrderHelper::getInstance($type);

        if(empty($helper)){
            return ['errcode' => -101 , 'message' => '没有对应的支付方式'];
        }
        if(is_string($helper)){
            return ['errcode' => -102 , 'message' => $helper];
        }

        $pay_order_sn = createNo();

        $subject = "充值" . $sum . "元";

        $helper->setOrderNumber($pay_order_sn)
            ->setOrderType(2)
            ->setBody($subject)
            ->setSubject($subject);

        $res = $helper->get_init_order($customer_id,$sum);

        if($res['errcode'] < 0){
            return $res;
        }
        $order = $helper->getOrder();

        M("order_info")->add($order);

        $return = $helper->getReturn();

        if($order['pay_id'] == 1){
            $notify = new \app\library\order\OrderNotify($order['order_sn'], $return['count'], $order['pay_id']);

            $notify->notify();
        }

        return ['errcode' => 0, 'message'=>'请求成功', 'content' => $return];
    }

    /**
    *转账
    **/
    public function transfer($customer_id,$amount,$transfer_id,$user){
        $res = ['errcode' => 0,'message' => '转账成功'];
        $reward = M("customer")->where(['customer_id' => $customer_id])->getField("reward_amount");
        if ($amount > $reward) {
            return ['errcode' => -202,'message' => '余额不足'];
        }

        $order_sn = createNo("order_info",'order_sn',"PO");
        $data = array(
            'order_sn' => $order_sn,
            'order_type' => 7,
            'customer_id' => $customer_id,
            'pay_id' => 6,
            'pay_name' => '余额宝支付',
            'state' => 1,
            'order_amount' => $amount,
            'goods_amount' => $amount,
            'date_add' => time(),
            'transfer_id' => $transfer_id
        );

        M("order_info")->add($data);
        M("customer")->where(['customer_id' => $customer_id])->save(['reward_amount' => ['exp','reward_amount - '.$amount]]);
        M("customer")->where(['customer_id' => $transfer_id])->save(['transfer_amount' => ['exp','transfer_amount + '.$amount]]);
        M("order_info")->where(['order_sn' => $order_sn])->save(['state' => 2,'date_pay' => time()]);
        
        $data = array(
            array(
                'customer_id' => $customer_id,
                'finance_type_id' => 3,
                'type' => 7,
                'amount' => $amount,
                'order_sn' => $order_sn,
                'date_add' => time(),
                'comments' => "转账给用户".$user.":".$amount,
                'title' => '-'.$amount,
                'is_minus' => 1
            ),
            array(
                'customer_id' => $transfer_id,
                'finance_type_id' => 3,
                'type' => 7,
                'amount' => $amount,
                'order_sn' => $order_sn,
                'date_add' => time(),
                'comments' => $user."向你转账".$amount,
                'title' => "+".$amount,
                'is_minus' => 2
            )

        );
        M("finance")->addAll($data);

        return $res;
    }

    /**
    *余额宝提现
    **/
    public function yuebaoWithdraw($customer_id,$account,$amount,$realname,$type,$subbranch,$invoice){
        if (empty($account)) {
            return ['errcode' => -201,'message' => '请传入账户'];
        }

        if (empty($realname)) {
            return ['errcode' => -201,'message' => '请输入姓名'];
        }
        $withdraw_amount = 0;
        if ($invoice != null){
            $withdraw_fee = \app\library\SettingHelper::get("shuaibo_withdraw_fee");
            bcscale(2);
            $reward = M("customer")->where(['customer_id' => $customer_id])->getField("reward_amount");
            if ($reward < $amount) {
                return ['errcode' => -201,'message' => '余额不足'];
            }

            $withdraw_amount = bcmul($amount,bcsub(1,$withdraw_fee));
        }else{
            $withdraw_amount = $amount;
        }

        $order_sn = createNo("customer_withdraw","order_sn","CA");
        $data = array(
            'order_sn' => $order_sn,
            'customer_id' => $customer_id,
            'money' => $amount,
            'state' => 1,
            'date_add' => time(),
            'type' => $type,
            'account' => $account,
            'realname' => $realname,
            'real_money' => $withdraw_amount,
            'subbranch' => $subbranch,
            'invoice' => $invoice,
            'style' => 3
        );

        //记录
        M("customer_withdraw")->add($data);
        M("customer")->where(['customer_id' => $customer_id])->save(['reward_amount' => ['exp','reward_amount -'.$amount]]);

        return ['errcode' => 0,'message' => '提现申请成功'];
    }

    /**
    *余额总明细
    **/
    public function yuebaoDetail($customer_id,$page){
        $count = M("finance")
        ->where(['customer_id' => $customer_id,'finance_type_id' => 3,'type' => ['in',"6,7,8,9,10,11,14,15"]])
        ->count();

        $detail = M("finance")
        ->where(['customer_id' => $customer_id,'finance_type_id' => 3,'type' => ['in',"6,7,8,9,10,11,14,15"]])
        ->field("comments,title,date_add")
        ->limit(10)
        ->page($page)
        ->order("date_add desc")
        ->select();

        $pageSize = ceil($count/10);

        return ['record' => $detail,'pageSize' => $pageSize];
    }

    public function singelYueDetail($customer_id,$page){
        $count = M("finance")
            ->where(['customer_id' => $customer_id,'finance_type_id' => 3,'type' => ['in',"6,8,10,11,14"]])
            ->count();

        $detail = M("finance")
        ->where(['customer_id' => $customer_id,'finance_type_id' => 3,'type' => ['in',"6,8,10,11,14"]])
        ->field("comments,title,date_add")
        ->limit(10)
        ->page($page)
        ->order("date_add desc")
        ->select();
        $pagesize = ceil($count/10);

        return ['record' => $detail,'pageSize' => $pagesize];
    }

    public function transferRecord($customer_id,$page){
        $count = M("finance")
        ->where(['customer_id' => $customer_id,'type' => ['in','15,7']])
        ->count();

        $record = M("finance")
        ->where(['customer_id' => $customer_id,'type' => ['in','7,15']])
        ->field("comments,title,date_add")
        ->limit(10)
        ->page($page)
        ->order("date_add desc")
        ->select();

        $pagesize = ceil($count/10);

        return ['record'=> $record,'pageSize' => $pagesize];
    }

    public function hongfuDetail($customer_id,$page){
        $count = M("finance")
        ->where(['customer_id' => $customer_id,'finance_type_id' => 4])
        ->count();

        $detail = M("finance")
        ->where(['customer_id' => $customer_id,'finance_type_id' => 4])
        ->field("comments,title,date_add")
        ->limit(10)
        ->page($page)
        ->order("date_add desc")
        ->select();

        $pagesize = ceil($count/10);

        return ['record' => $detail,'pageSize' => $pagesize];
    }
}