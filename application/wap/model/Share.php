<?php 
namespace app\app\model;
use think\Model;
class Share extends Model{

	public function GetShareList($page = 1, $customer_id = null, $goods_id = null){
		$host = C("image_url");
	
		$condition = array();
	    if(!empty($customer_id)){
	      	$condition['c.customer_id'] = $customer_id;
	    }
        if(!empty($goods_id)){
            $condition['g.goods_id'] = $goods_id;
        }

		$shares = $this
        ->alias("s")
        ->join("__WINNING_RECORD__ wr","wr.record_id = s.record_id")
        ->join("__CUSTOMER__ c","c.customer_id = wr.customer_id")
        ->join("__ITEMS__ i"," i.item_id = wr.item_id")
        ->join("__GOODS__ g"," g.goods_id = i.goods_id")
        ->join("__SHARE_IMAGE__ si", "si.share_id = s.share_id")
        ->field("s.content, ifnull(null,concat('$host',c.avater)) as avater, c.nickname,s.date_add,s.record_id,s.share_id,".
            " g.name as goods_name,GROUP_CONCAT(concat('$host',si.image_url)  SEPARATOR ',') as image_urls")
        ->group("s.share_id")
        ->order("s.date_add desc")
        ->limit(10)->page($page)
        ->where($condition)
        ->select();
        foreach ($shares as $key => $value) {
            $urls = $value['image_urls'];
            if(!empty($urls)){
                $shares[$key]['image_urls'] = explode(",", $urls);
            }
        }
        return $shares;
	}

}
