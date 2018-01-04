<?php 
namespace app\library\message;
class Message{
	
	/**
	 * 推送
	 * */
	const PLATFORM_PUSH = 1;
	
	/**
	 * APP内部消息
	 * */
	const PLATFORM_MESSAGE = 2;
	
	/**
	 * 短信发送
	 * */
	const PLATFORM_SMS = 3;
	
	/**
	 * 微信公众号
	 * */
	const PLATFORM_WEIXIN = 4;
	
	/**
	 * 全部
	 * */
	const PLATFORM_ALL = 5;


	/**
	 * 全体发送
	 */
	const TARGET_ALL = 1;
	/**
	 * 发送给指定的用户们
	 */
	const TARGET_CUSTOMERS = 2;
	/**
	 * 发送给指定的标签组们
	 */
	const TARGET_TAGS = 3;
	
	
	
	/**
	 * 无跳转动作
	 * */
	const ACTION_NONE = 1;
	
	/**
	 * 跳转至网页
	 * */
	const ACTION_URL = 2;
	
	/**
	 * 跳转至商品详情页
	 * */
	const ACTION_GOODS = 3;
	
	/**
	 * 跳转至订单详情页
	 * */
	const ACTION_ORDER = 7;

    /**
     * 物流信息
     */
	const ACTION_EXPRESS = 19;

    /**
     * 优惠券列表
     */
    const ACTION_COUPON_LIST = 18;
	
	/**
	 * 提现记录
	 * */
	const ACTION_WITHDRAW_RECORD = 8;
	
	const ACTION_ORDER_LIST = 9;
	
	private $platforms = [];
	
	private $template;
	
	private $target_type = self::TARGET_CUSTOMERS;
	
	private $target_ids = [];
	
	private $extra = [];
	
	private $push_extra = [];
	
	private $weixin_extra = [];
	
	private $action_id = 1;
	
	private $asyn = false;
	
	private $tags_is_and = false;
	
	public function setTemplate($template){
		$this->template = $template;
		return $this;
	}
	public function getTemplate(){
		return $this->template;
	}
	
	public function setPlatform($platform){
		if (!is_array($platform)){
			$platform = [$platform];
		}
		
		foreach ($platform as $p){
			if(!in_array($p, $this->platforms)){
				$this->platforms[] = $p;
			}
		}
		return $this;
	}
	public function getPlatform(){
		if(in_array(self::PLATFORM_ALL, $this->platforms)){
			return [self::PLATFORM_ALL];
		}
		return $this->platforms;
	}
	
	public function setExtras($extra){
		$this->extra = $extra;
		return $this;
	}
	public function getExtra(){
		return $this->extra;
	}

	public function setPushExtra($extra){
		$this->push_extra = $extra;
		return $this;
	}
	public function getPushExtra(){
		return array_merge($this->extra, $this->push_extra) ;
	}
	
	public function setWeixinExtra($extra){
		$this->weixin_extra = $extra;
		return $this;
	}
	public function getWeixinExtra(){
		return array_merge($this->extra, $this->weixin_extra);
	}

	public function setTargetType($target_type){
		$this->target_type = $target_type;
		return $this;
	}
	public function getTargetType(){
		return $this->target_type;
	}
	
	public function setTargetIds($target_ids = []){
		if (!is_array($target_ids)){
			$target_ids = [$target_ids];
		}
		$this->target_ids = $target_ids;
		return $this;
	}
	public function getTargetIds(){
		return $this->target_ids;
	}
	
	public function setAction($action){
		$this->action_id = $action;
		return $this;
	}
	public function getAction(){
		return $this->action_id;
	}
	
	public function setAsyn($asyn){
		$this->asyn = $asyn;
		return $this;
	}
	public function getIsAsyn(){
		return $this->asyn;
	}
	
	public function setTagsIsAnd($tags_is_and){
		$this->tags_is_and = $tags_is_and;
		return $this;
	}
	public function getTagsIsAnd(){
		return $this->tags_is_and;
	}
}