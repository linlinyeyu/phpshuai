<?php 
namespace app\admin\model;

use think\Model;

class Customer extends Model
{

	public function getCustomerName($id){
		$customer = $this->where(['customer_id'=>$id])->field("nickname")->find();
		if(!empty($customer)){
			return $customer['nickname'];
		}
		return "";
	}
	
	public function day_statistics($type = 0,$days = 30,$x = 'y', $y = 'count'){
		$date = date("Y-m-d");
	
		//将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
		$todayTime= strtotime($date);
		$condition = ['date_add' => ['egt', $todayTime - 60 * 60 * 24 * $days]];
		if($type != 0){
			$condition['active'] = $type;
		}
		$customer = $this->where($condition)->group("FROM_UNIXTIME(date_add,'%Y-%m-%d')")
		->field("FROM_UNIXTIME(date_add,'%m-%d') as y, count(1) as count")->order("date_add desc")->select();
		$result = [];
		$count = [];
		$keys = [];
		for($i = 0; $i < $days ; $i ++){
			$key = date('m-d',$todayTime - 60 * 60 *24 * $i);
			$keys[] = $key;
			if(count($customer) > 0 && $customer[0]['y'] == $key){
				$count[] = $customer[0]['count'] + 0;
				array_shift($customer);
			}else{
				$count[] = 0;
			}
		}
		$result['yAxis'] = array_reverse($count);
		$result['xAxis'] = array_reverse($keys) ;
		return $result;
	}
	
	
	public function month_statistics($year = 2016,$month = 12,$x = 'y', $y = 'count'){
		
		$start_time = mktime(0,0,0,$month , 1, $year);
		
		$end_time = mktime(0,0,0,$month + 1,1, $year);
		
		//将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
	
		$condition = ['date_add' => [['egt', $start_time],['elt', $end_time]]];
		
		$finances = $this->where($condition)->group("FROM_UNIXTIME(date_add,'%Y-%m-%d')")
		->field("FROM_UNIXTIME(date_add,'%m-%d') as y , count(1) as count")->order("date_add desc")->select();
		$result = [];
		$count = [];
		$keys = [];
		$days = (int) (($end_time - $start_time ) / (24 * 60 * 60));
		for($i = 0; $i < $days ; $i ++){
			$key = date('m-d', mktime(0,0,0, $month , $days - $i, $year));
			$keys[] = $key;
			if(count($finances) > 0 && $finances[0]['y'] == $key){
				$count[] = $finances[0]['count'] + 0;
				array_shift($finances);
			}else{
				$count[] = 0;
			}
		}
		$result['yAxis'] = array_reverse($count) ;
		$result['xAxis'] = array_reverse($keys);
		return $result;
	}
	
	public function year_statistics($year, $x = 'y' , $y = 'count'){
		$start_time = mktime(0,0,0,1 , 1, $year);
		
		$end_time = mktime(0,0,0,1,1, $year + 1);
		
		//将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
		
		$condition = ['date_add' => [['egt', $start_time],['elt', $end_time]]];
		
		$finances = $this->where($condition)->group("FROM_UNIXTIME(date_add,'%Y-%m')")
		->field("FROM_UNIXTIME(date_add,'%m') as y , count(1) as count")->order("date_add desc")->select();
		$result = [];
		$count = [];
		$keys = [];
		$months = 12;
		for($i = 0; $i < $months ; $i ++){
			$key = date('m', mktime(0,0,0, $months - $i , 1, $year));
			$keys[] = $key;
			if(count($finances) > 0 && $finances[0]['y'] == $key){
				$count[] = $finances[0]['count'] + 0;
				array_shift($finances);
			}else{
				$count[] = 0;
			}
		}
		$result['yAxis'] = array_reverse($count) ;
		$result['xAxis'] = array_reverse($keys);
		return $result;
	}
	
	
	
}