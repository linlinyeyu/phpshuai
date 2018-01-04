<?php
namespace app\library;
class SettingHelper{
		public static function set($key, $value){
			if(is_array($value)){
				$value = serialize($value);
			}
			S($key, $value);
			$count = M("settings")->where(['setting_key' => $key])->count();
			if($count > 0){
				M("settings")->where(['setting_key' => $key])->save(['setting_value' => $value]);
			}else{
				M("settings")->add(['setting_key' => $key, 'setting_value' => $value]);
			}
		}
		public static function get($key, $default = "", $is_array = false){
			if($key == "shuaibo_image_url"){
				$qiniu = self::get("shuaibo_qiniu", ['is_open' => 0]);
				if($qiniu['is_open'] == 0){
					return "http://" . get_domain() ."/";
				}
			}
			
			$value = S($key);
			if(empty($value)){
				$value = M("settings")->where(['setting_key' => $key])->getField("setting_value");
				if(!empty($value)){
					S($key, $value);
				}
			}
			if(empty($value)){
				$value = $default;
			}
			else if($is_array || is_array($default)){
				$value = unserialize($value);
			}
			return $value;
		}
		
		public static function set_pay_params($key, $param){
			$pay = M("pay_type")->where(['pay_id' => $key])->find();
			if(empty($pay)){
				return false;
			}
			$params = $pay['params'];
			if(!empty($params)){
				$param = array_merge(unserialize($params),  $param );
			}
			$data = [];
			if(isset($param['is_open'])){
				$data['active'] = $param['is_open'];
			}
			
			$param = serialize($param);
			$data['params'] = $param;
			M("pay_type")->where(['pay_id' => $key])->save($data);
			S("shuaibo_pay_params_" . $key , $param);
			return true;
		}
		
		public static function get_pay_params($key, $default = null){
			$params = S("shuaibo_pay_params_" . $key);
			if(empty($params)){
				$params = M("pay_type")->where(['pay_id' => $key])->getField("params");
				if (!empty($params)){
					S("shuaibo_pay_params_" . $key, $params);
				}
			}
			if(!empty($params)){
				return unserialize($params);
			}
			return $default;
		}
		
		public static function get_express(){
			$express = S("bear_express");
			if(empty($express)){
				$express = self::set_express();
			}
			return unserialize($express);
		}
		
		public static function set_express(){
			$express=M("express")
			->alias("e")
			->select();
			$express = serialize($express);
			S("bear_express", $express);
			return $express;
		}
		
		
		public static function update_express(){
			$express=M("express")
			->alias("e")
			->field("e.express_name")
			->select();
			S("bear_express", serialize($express));
		}
		
		public static function get_action($id){
			$action = S("bear_action_".$id);
			if(empty($action)){
				$action = serialize(M("action")->where(['action_id' => $id])->find()) ;
				S("bear_action_id".$id , $action);
			}
			return unserialize($action);
		}
		
		public static function get_levels(){
			$levels = S("bear_levels");
			if(empty($levels)){
				$levels = self::set_levels();
			}
			return unserialize($levels);
		}
		
		public static function set_levels(){
			$levels = serialize(M("customer_group")->order("upgrade desc, group_id desc")->select());
			S("bear_levels", $levels);
			return $levels;
		}
		
		public static function get_rank( $type = 0 ){
			$ranks = S("bear_rank_". $type);
			if(empty($ranks)){
				$ranks = self::set_rank($type);
			}
			return unserialize($ranks);
		}
		
		public static function set_rank($type = 0){
			$host = self::get("bear_image_url");
			$suffix = UploadHelper::getInstance()->getThumbSuffix(200, 200);
			$rank = [];
			switch ($type){
				case 0:
					$rank = M("customer_extend_record")
					->alias("cer")
					->join("customer_extend ce","cer.customer_extend_id = ce.customer_extend_id")
					->join("customer c","ce.pid = c.customer_id")
					->field("c.customer_id, c.nickname, avater as image, sum(cer.commission) as commission")
					->group("c.customer_id")
					->order("sum(cer.commission) desc , c.customer_id ")
					->where(['cer.state' => ['in', "1,3"]])
					->limit(1000)
					->select();
					break;
				case 1 :
					$week = date('w');
					if($week == 0){
						$week =  7;
					}
					$last_monday = strtotime( '+'. 1-$week .' days' );
					$last_monday = strtotime(date('Y-m-d', $last_monday));
					$rank = M("customer_extend_record")
					->alias("cer")
					->join("customer_extend ce","cer.customer_extend_id = ce.customer_extend_id")
					->join("customer c","ce.pid = c.customer_id")
					->field("c.customer_id, c.nickname, avater as image, sum(cer.commission) as commission")
					->group("c.customer_id")
					->order("sum(cer.commission) desc , c.customer_id ")
					->where(['cer.state' => ['in','1,3'],'cer.date_add' => [['gt' , $last_monday]]])
					->limit(1000)
					->select();
					break;
				case 2:
					$last_one = strtotime(date("Y-m"));
					$rank = M("customer_extend_record")
					->alias("cer")
					->join("customer_extend ce","cer.customer_extend_id = ce.customer_extend_id")
					->join("customer c","ce.pid = c.customer_id")
					->field("c.customer_id, c.nickname, avater as image, sum(cer.commission) as commission")
					->group("c.customer_id")
					->order("sum(cer.commission) desc, c.customer_id")
					->where(['cer.state'=> ['in', "1,3"], 'cer.date_add' => [['gt' , $last_one]]])
					->limit(1000)
					->select();
					break;
				default: 
					return ['time' => time(), 'rank' => [] ,'total_rank' => []];
						
			}
			
			if(count($rank) < 1000 ){
				$ids = [];
				foreach ($rank as &$r){
					$ids[] = $r['customer_id'];
				}
				$limits = M("customer")
				->alias("c")
				->field("c.customer_id, c.nickname, avater as image, 0 as commission ")
				->order("commission desc")
				->limit(1000 - count($rank))
				->where(['customer_id' => ['not in', join(",", $ids) ]])
				->select();
				$rank = array_merge($rank, $limits);
			}
			foreach ($rank as &$r){
				if($r['image'] && strpos($r['image'], "http")  !== 0){
					$r['image'] = $host . $r['image'] . $suffix;
				}
			}
			unset($r);
			
			$ranklist = array_slice($rank, 0, 100);
			
			foreach ($rank as $k => &$v){
				$v['i'] = $k + 1;
			}
			unset($v);
			$total_rank = array_key_arr($rank, "customer_id");
			$rank = serialize(['time' => time() , 'rank' => $ranklist, 'total_rank' => $total_rank]);
			S("bear_rank_". $type, $rank);
			return $rank;
		}
		
		private static function load_config(){
			$res = M("settings")->field("code,value")->select();
			foreach ($res as $row)
			{
				$arr[$row['code']] = $row['value'];
			}
			
			/* 对数值型设置处理 */
			$arr['watermark_alpha']      = intval($arr['watermark_alpha']);
			$arr['market_price_rate']    = floatval($arr['market_price_rate']);
			$arr['integral_scale']       = floatval($arr['integral_scale']);
			//$arr['integral_percent']     = floatval($arr['integral_percent']);
			$arr['cache_time']           = intval($arr['cache_time']);
			$arr['thumb_width']          = intval($arr['thumb_width']);
			$arr['thumb_height']         = intval($arr['thumb_height']);
			$arr['image_width']          = intval($arr['image_width']);
			$arr['image_height']         = intval($arr['image_height']);
			$arr['best_number']          = !empty($arr['best_number']) && intval($arr['best_number']) > 0 ? intval($arr['best_number'])     : 3;
			$arr['new_number']           = !empty($arr['new_number']) && intval($arr['new_number']) > 0 ? intval($arr['new_number'])      : 3;
			$arr['hot_number']           = !empty($arr['hot_number']) && intval($arr['hot_number']) > 0 ? intval($arr['hot_number'])      : 3;
			$arr['promote_number']       = !empty($arr['promote_number']) && intval($arr['promote_number']) > 0 ? intval($arr['promote_number'])  : 3;
			$arr['top_number']           = intval($arr['top_number'])      > 0 ? intval($arr['top_number'])      : 10;
			$arr['history_number']       = intval($arr['history_number'])  > 0 ? intval($arr['history_number'])  : 5;
			$arr['comments_number']      = intval($arr['comments_number']) > 0 ? intval($arr['comments_number']) : 5;
			$arr['article_number']       = intval($arr['article_number'])  > 0 ? intval($arr['article_number'])  : 5;
			$arr['page_size']            = intval($arr['page_size'])       > 0 ? intval($arr['page_size'])       : 10;
			$arr['bought_goods']         = intval($arr['bought_goods']);
			$arr['goods_name_length']    = intval($arr['goods_name_length']);
			$arr['top10_time']           = intval($arr['top10_time']);
			$arr['goods_gallery_number'] = intval($arr['goods_gallery_number']) ? intval($arr['goods_gallery_number']) : 5;
			$arr['no_picture']           = !empty($arr['no_picture']) ? str_replace('../', './', $arr['no_picture']) : 'data/static/images/no_picture.gif'; // 修改默认商品图片的路径
			$arr['qq']                   = !empty($arr['qq']) ? $arr['qq'] : '';
			$arr['ww']                   = !empty($arr['ww']) ? $arr['ww'] : '';
			$arr['default_storage']      = isset($arr['default_storage']) ? intval($arr['default_storage']) : 1;
			$arr['min_goods_amount']     = isset($arr['min_goods_amount']) ? floatval($arr['min_goods_amount']) : 0;
			$arr['one_step_buy']         = empty($arr['one_step_buy']) ? 0 : 1;
			$arr['invoice_type']         = empty($arr['invoice_type']) ? array('type' => array(), 'rate' => array()) : unserialize($arr['invoice_type']);
			$arr['show_order_type']      = isset($arr['show_order_type']) ? $arr['show_order_type'] : 0;    // 显示方式默认为列表方式
			$arr['help_open']            = isset($arr['help_open']) ? $arr['help_open'] : 1;
			
			if(S("ecs_version") != null){
				/* 如果没有版本号则默认为2.0.5 */
				$arr['ecs_version'] = 'v2.7.3';
			}
			//限定语言项
			$lang_array = array('zh_cn', 'zh_tw', 'en_us');
			if (empty($arr['lang']) || !in_array($arr['lang'], $lang_array)){
				$arr['lang'] = 'zh_cn'; // 默认语言为简体中文
			}
			
			if (empty($arr['integrate_code'])){
				$arr['integrate_code'] = 'ecshop'; // 默认的会员整合插件为 ecshop
			}
			
			return $arr;
		}
}
