<?php
namespace app\web\controller;

class InitAction{
	public function getHomePage(){
		$terminal = (int)I("t");
		$position = (int)I('postion');
		
		//首页轮播图
		$banners = D("banner_model")->getBanners($position,$terminal);
		
		//首页标签图
		$tags = D("home_tag_model")->GetTags($terminal);
		
		return ['errcode' => 0,'message' => '请求成功','content' => ['banners' => $banners,'tags' => $tags]];
	}
	
	public function getNewGoods(){
        $terminal = I("t");
        $new_goods = D("init_model")->getEveryNewGoods(1,$terminal,6);
		
		return ['errcode' => 0,'message' => '请求成功','content' => $new_goods];
	}
	
	//分类
	public function getCategory(){
		$category = S("shuaibo_categories");
		$category = unserialize($category);
		if(empty($category)){
			$category = D("category_model")->getCategory();
			S("shuaibo_categories",serialize($category));
		}
		
		return ['errcode' => 0,'message' => '请求成功','content' => $category];
	}
	
	//活动
	public function getActivity(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 400);
        $suffix_web = \app\library\UploadHelper::getInstance()->getThumbSuffix(1200, 80);
        $activity = null;
        $home_activity = D("init_model")->getHomeActivity();
        $temp = [];
        $i = 0;
        if (!empty($home_activity)){
            foreach ($home_activity as $v){
                $temp[$v['special_id']] = $v;
            }
            if (array_key_exists(1, $temp)){
                $img = $temp[1]['img'].$suffix;
                $img_web = $temp[1]['img'].$suffix_web;
                //一元抢购
                $condition = "s.type = 1 and s.status = 1 and sg.status = 1 "." and g.is_delete = 0 and g.on_sale = 1";
                $fields = "g.goods_id,g.name,concat('$host',IFNULL(sg.image,g.cover),'$suffix') as cover,g.shop_price,sg.date_start,sg.date_end";
                $activity[$i]['goods'] = D('special_model')->getSpecialGoods($condition,$fields,"sg.sort ASC",1,4);
                $activity[$i]['img'] = array("img" => $img,'img_web' => $img_web,'jump' => $temp[1]['web_param']);
                $activity[$i]['type'] = 1;
                $activity[$i]['sort'] = $temp[1]['sort'];
                unset($temp[1]);
                $i++;
            }
            //购物专区
            if (array_key_exists(6, $temp)){
                $img = $temp[6]['img'].$suffix;
                $img_web = $temp[6]['img'].$suffix_web;
                //购物积分
                $activity[$i]['goods'] = D("special_model")->getShoppingGoods(6,1,6);
                $activity[$i]['img'] = array("img" => $img,'img_web' => $img_web,'jump' => $temp[6]['web_param']);
                $activity[$i]['type'] = 6;
                $activity[$i]['sort'] = $temp[6]['sort'];
                unset($temp[6]);
                $i++;
            }
            //奖励商品
            if (array_key_exists(11,$temp)){
                $img = $temp[11]['img'].$suffix;
                $img_web = $temp[11]['img'].$suffix_web;
                $activity[$i]['goods'] = D("goods_model")->getRewardGoods(1,6);
                $activity[$i]['img'] = array("img" => $img,'img_web' => $img_web,'jump' => $temp[11]['web_param']);
                $activity[$i]['type'] = 11;
                $activity[$i]['sort'] = $temp[11]['sort'];
                unset($temp[11]);
                $i++;
            }
            //鸿府积分
            if (array_key_exists(12,$temp)){
                $img = $temp[12]['img'].$suffix;
                $img_web = $temp[12]['img'].$suffix_web;
                $activity[$i]['goods'] = D("goods_model")->getHongfuGoods(1,6);
                $activity[$i]['img'] = array("img" => $img,'img_web' => $img_web,'jump' => $temp[12]['web_param']);
                $activity[$i]['type'] = 12;
                $activity[$i]['sort'] = $temp[12]['sort'];
                unset($temp[12]);
                $i++;
            }
            //线下提货
            if (array_key_exists(13,$temp)){
                $img = $temp[13]['img'].$suffix;
                $img_web = $temp[13]['img'].$suffix_web;
                $activity[$i]['goods'] = D("goods_model")->getOfflineGoods(1,6);
                $activity[$i]['img'] = array("img" => $img,'img_web' => $img_web,'jump' => $temp[13]['web_param']);
                $activity[$i]['type'] = 13;
                $activity[$i]['sort'] = $temp[13]['sort'];
                unset($temp[13]);
                $i++;
            }
            foreach ($temp as $special_id => $value){
                $img = $value['img'].$suffix;
                $img_web = $value['img'].$suffix_web;
                $condition = "s.type = ".$value['type']." and s.status = 1 and sg.status = 1 and g.is_delete = 0 and g.on_sale = 1";
                $fields = "g.goods_id,g.name,concat('$host',IFNULL(sg.image,g.cover),'$suffix') as cover,(g.sale_count+g.virtual_count) as sale_count,g.shop_price";
                $activity[$i]['goods'] = D("special_model")->getSpecialGoods($condition,$fields,"sg.sort ASC",1,6);
                $activity[$i]['img'] = array("img" => $img,'img_web' => $img_web,'jump' => $value['web_param']);
                $activity[$i]['type'] = $value['type'];
                $activity[$i]['sort'] = $value['sort'];
                $i++;
            }
        }
        $activity = $this->array_sort($activity,"sort","asc");
        return $activity;
	}

    private function array_sort($arr,$keys,$type='desc'){
        $keysvalue = $new_array = $new_arr = array();
        foreach ($arr as $k=>$v){
            $keysvalue[$k] = $v[$keys];
        }
        if($type == 'asc'){
            asort($keysvalue);
        }else{
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k=>$v){
            $new_array[$k] = $arr[$k];
        }
        foreach ($new_array as $k => $v) {
            $new_arr[] = $v;
        }
        return $new_arr;
    }

    //分类商品
	public function getCategoryGoods(){
		$host = \app\library\SettingHelper::get("shuaibo_image_url");
		$suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 800);
        $suffix_l = \app\library\UploadHelper::getInstance()->getThumbSuffix(300, 400);
        $category_goods = S("shuaibo_home_category_goods");
		if (empty($category_goods)){
			$categories = D("category_model")->getHomeCategory();
			foreach ($categories as $key => $category){
				$condition = "hcg.home_category_id = ".$category['home_category_id']." and g.on_sale = 1 and g.is_delete = 0";
				$fields="concat('$host',IFNULL(hcg.image,g.cover)) as cover,concat('$host',IFNULL(hcg.image,g.cover)) as cover_l,g.goods_id";
				$category_goods[$key]['goods'] = D("goods_model")->getHomeCategoryGoodsList($condition,$fields,"hcg.sort ASC",1,5);
				$category_goods[$key]['img'] = $host.$category['img'];
			}
			S("shuaibo_home_category_goods",$category_goods);
		}
		return $category_goods;
	}
	
	//关键词列表
	public function getKeyWord(){
		$keyword = S("shuaibo_keyword");
		if (empty($keyword)){
			$keyword = D("init_model")->getKeywordList();
			S("shuaibo_keyword",$keyword);
		}
		return ['errcode' => 0,'message' => '请求成功','content' => $keyword];
	}
	
	//首页广告
	public function getAd(){
		$ad = D('init_model')->getAd();
		
		return ['errcode' => 0,'message' => '请求成功','content' => $ad];
	}

    /**
     * 协议
     * @return array
     */
    public function getBaseData() {
        $res = array(
            'message' => '成功',
            'errcode' => 0
        );
        $content = [];
        $protocol = \app\library\SettingHelper::get("shuaibo_protocol",['service_protocol' => 'http://www.baidu.com', 'law_protocol' => 'http://www.baidu.com']);
        $content['service_protocol'] = $protocol['service_protocol'];
        $content['law_protocol'] = $protocol['law_protocol'];
        $res['content'] = $content;
        return $res;
    }

    /**
     * 客服信息
     * @return array
     */
    public function getKFInfo() {
        $res = array(
            'message' => '成功',
            'errcode' => 0
        );
        $content = [];
        $seller_info = \app\library\SettingHelper::get("shuaibo_seller_info",['address' => '杭州市' ,'qq' => '123456789', 'weixin' => '']);
        $content['address'] = $seller_info['address'];
        $content['qq'] = $seller_info['qq'];
        $content['weixin'] = $seller_info['weixin'];
        $content['tel'] = $seller_info['tel'];
        $res['content'] = $content;
        return $res;
    }

    /**
     * 获取首页优惠资讯
     * @return array
     */
    public function getHomeInformations() {
        $res = array(
            'message' => '成功',
            'errcode' => 0
        );

        $host = \app\library\SettingHelper::get("shuaibo_image_url");

        $infos = M('information')
            ->field("id,name,date_add")
            ->order('date_add DESC')
            ->limit(3)
            ->select();

        $content['infos'] = $infos;
        $res['content'] = $content;
        return $res;
    }

    /**
     * 优惠资讯
     */
    public function getInformations() {
        $res = array(
            'message' => '成功',
            'errcode' => 0
        );

        $page = (int)I('page');
        if ($page <= 0) {
            $page = 1;
        }

        $host = \app\library\SettingHelper::get("shuaibo_image_url");
        $suffix = \app\library\UploadHelper::getInstance()->getThumbSuffix(400, 240);

        $infos = M('information')
            ->field("id,name,concat('$host',image,'$suffix') as image,mini_content,date_add")
            ->order('date_add DESC')
            ->page($page)
            ->limit(15)
            ->select();
        $image = M('information_image')
            ->where(['id' => 1])
            ->field("concat('$host',image) as image")
            ->find();

        $count = M('information')->count();
        $pageSize = ceil($count/15);

        $content['infos'] = $infos;
        $content['image'] = $image['image'];
        $content['pageSize'] = $pageSize;
        $res['content'] = $content;
        return $res;
    }

    /**
     * 资讯详情
     * @return array
     */
    public function getInformationsDetail() {
        $id = I('id');
        if (empty($id)) {
            return ['errcode' => -100, 'message' => '请传入编号'];
        }
        $info = M('information')
            ->where(['id' => $id])
            ->field("id,name,content,date_add")
            ->find();
        return ['errcode' => 0, 'message' => '成功', 'content' => $info];
    }
}