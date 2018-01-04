<?php
namespace app\wap\controller;
use think\Controller;
class Goods extends \app\wap\BaseController
{
	
	public function goodsinfo (){
		$goods_id = I("goods_id");
		if(empty($goods_id)){
			$this->error("请传入商品信息",U("home/index"));
		}
		
		$customer_id = $this->customer_id;
		
		$goods = D("goods")->getGoodsInfo($goods_id,$customer_id);
		if(empty($goods)){
			$this->error("找不到相关商品");
		}
		
		$this->assign("goods", $goods);
		$this->assign("is_login", empty($this->customer_id) ? 0 : 1);
		$this->assign("description", $goods['description']);
		$this->display();
	}
	
	
	public function goods_receive(){
		$this->display();
	}
	
	public function seckill_goods(){
		$this->display();
	}

	public function goods_experience(){

		$this->display();
		
	}		
	
	public function description(){
		$goods_id = I("goods_id");
		$description = M("goods")->where(['goods_id' => $goods_id])->getField("description");
		$description = html_entity_decode($description);
		$this->assign("descript", $description); 
		$this->display();
	}

    public function protocol(){
        $this->assign("title","购买协议");
        $protocol = htmlspecialchars_decode(\app\library\SettingHelper::get("shuaibo_goods_protocol"),ENT_QUOTES);
        $this->assign("protocol",$protocol);
        $this->display();
    }
	
}