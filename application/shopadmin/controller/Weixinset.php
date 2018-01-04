<?php
namespace app\shopadmin\controller;
use app\shopadmin\Admin;
class Weixinset extends Admin 
{
	
	public function settings(){
		
		if(IS_POST){
			!($appid = I("post.appid")) && $this->error("请传入appid");
			!($appsecret = I("post.app_secret")) && $this->error("请传入app_secret");
			!($token = I("post.token")) && $this->error("请传入token");
			\app\library\SettingHelper::set("bear_weixin_gz_config", ['appid' => $appid, 'app_secret' => $appsecret, 'token' => $token]);
			$this->success("操作成功");
		}
		$config = \app\library\SettingHelper::get("bear_weixin_gz_config",['appid' => '','app_secret' => '','token' => '']);
		
		$this->assign("url", "http://". get_domain(). "/app/weixin/api");
		$this->assign("config", $config);
		$this->display();
	}
	
	public function menu(){
		if(IS_POST){
			!($menu = I("post.menu")) && $this->error("请传入菜单");
			$menu = json_decode($menu, true);
			\app\library\SettingHelper::set("bear_weixin_menu", $menu);
			
			$config = \app\library\SettingHelper::get("bear_weixin_gz_config",['appid' => '']);
			empty($config['appid']) && $this->error("请先设置公众号参数",null,U("weixinset/settings"));
			S("bear_weixin_access_token_" . $config['appid'], null);
			$client = \app\library\weixin\WeixinClient::getInstance($config);
			$content = $client->createMenu($menu);
			$content['errcode'] == 0 ? $this->success("操作成功") : $this->error($content['errmsg']);
		}
		$config = \app\library\SettingHelper::get("bear_weixin_gz_config",['appid' => '']);
		empty($config['appid']) && $this->error("请先设置公众号参数",null,U("weixinset/settings"));
		
		$menu = \app\library\SettingHelper::get("bear_weixin_menu",[]);
		$this->assign("menu", $menu);
		
		$this->display();
	}
	
	public function template()
	{
		if(IS_POST){
			$templates = I("");
			$templates = $templates['template'];
			\app\library\SettingHelper::set("bear_weixin_templates", $templates);
			$this->success("操作成功");
		}
		$templates = \app\library\SettingHelper::get("bear_weixin_templates",['is_open' => 0]);
		$this->assign("templates", $templates);
		$this->display();
	}

	public function reply_rule_edit()
	{
		$group_id = (int)I("group_id");
		if(IS_POST){
			$group_name = I("group_name");
			$content = I("content");
			$keywords = json_decode(I("params"), true);


			if (empty($group_id)){
				$data = ['group_name' => $group_name, 'content' => $content,'reply_type' => 1];
				$group_id = M("auto_reply")->add($data);

			}else{
				$data = ['group_name' => $group_name, 'content' => $content,'reply_type' => 1];
				M("auto_reply")
					->where(['id' => $group_id ])
					->save($data);
			}

			$ids = [];
			foreach ($keywords as $w){
				$w['keyword'] =  trim($w['keyword']);
				$data = ['keyword' => $w['keyword'], 'match_rule' => $w['match_rule'] ];
				if($w['id'] > 0){
					if ($w['keyword']) {
						M("auto_reply_keyword")->where(['group_id' => $group_id ,'id' => $w['id'] ])
							->save($data);
					}
					$ids[] = $w['id'];
				}else{
					$data['group_id'] = $group_id;
					if ($w['keyword']) {
						$ids[] = M("auto_reply_keyword")->add($data);
					}

				}

			}
			M("auto_reply_keyword")->where(['group_id' => $group_id,'id' => ['not in', join(",", $ids)]])->delete();


			$this->success("操作成功",U("weixinset/reply_list"));


		}
		$info['group_name'] = "";
		$info['keyword_group'] = [];
		$info['reply_type'] = 1;
		$info['content'] = "";
		if($group_id > 0){
			$info = M("auto_reply")->alias("ar")->where(['ar.id'=>$group_id])->find();
			$keywords = M('auto_reply_keyword')
				->where(['group_id'=>$group_id])->select();
			$info['keyword_group'] = $keywords;
		}

		$this->assign('info',$info);
		$this->display();

	}
	public function reply_list()
	{
		$groups=M("auto_reply")
			->alias("ar")
			->join("auto_reply_keyword ark","ar.id = ark.group_id","left")
			->group("ar.id")
			->field("ar.id")
			->select();
		$count = count($groups);
		$page = new \com\Page($count, 15);
		$lists = M("auto_reply")
			->alias("ar")
			->join("auto_reply_keyword ark","ar.id = ark.group_id","left")
			->order("match_rule asc")
			->group("ar.id")
			->order("ar.id desc")
			->field("ar.id as id,ar.content,ar.reply_type,ar.group_name,group_concat(ark.keyword) as keyword_group,group_concat(ark.match_rule) as rule_group")
			->limit($page->firstRow . ',' . $page->listRows)
			->select();

		foreach ($lists as &$l) {

			$keywords=array();
			if(!empty($l['keyword_group'])) {
				$keywords = explode(",",$l['keyword_group']);
			}

			$match_rules = explode(",",$l['rule_group']);
			$a=array();
			foreach($keywords as $key => $value)
			{
				$temp['keyword'] = $value;
				$rule = $match_rules[$key];
				if($rule == "1") {
					$rule = "精确匹配";
				}
				if($rule == "2") {
					$rule = "正则匹配";
				}
				if($rule == "3") {
					$rule = "模糊匹配";
				}
				$temp['match_rule'] = $rule;

				$a[]=$temp;

			}

			$l['keyword_group']=$a;
		}
		
		$this->assign('page',$page->show());
		$this->assign('lists',$lists);
		$this->display();

	}


	public function delete_reply_rule()
	{
		$group_id = I('group_id');
		if($group_id>0){
			M("auto_reply")->where(['id' => $group_id])->delete();
			M("auto_reply_keyword")->where(['group_id' => $group_id])->delete();
			$this->success("删除成功");
		}
		$this->error("组不存在");
	}



}