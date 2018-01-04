<?php 
namespace app\admin\model;

use think\Model;

class Finance extends Model
{
	public function day_statistics($type = 0,$days = 30,$x = 'y', $y = 'count'){
		$date = date("Y-m-d");


		//将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
		$todayTime= strtotime($date);
		$condition = ['date_add' => ['egt', $todayTime - 60 * 60 * 24 * $days]];
		if($type != 0){
			$condition['type'] = $type;
		}
		$finances = $this->where($condition)->group("FROM_UNIXTIME(date_add,'%Y-%m-%d')")
		->field("FROM_UNIXTIME(date_add,'%m-%d') as y , sum(amount) as count")->order("date_add desc")->select();
		$result = [];
		$count = [];
		$keys = [];
		for($i = 0; $i < $days ; $i ++){
			$key = date('m-d',$todayTime - 60 * 60 *24 * $i);
			$keys[] = $key;
			if(count($finances) > 0 && $finances[0]['y'] == $key){
				$count[] = $finances[0]['count'] + 0;
				array_shift($finances);
			}else{
				$count[] = 0;
			}
		}
		$result['yAxis'] = array_reverse($count);
		$result['xAxis'] = array_reverse($keys) ;
		return $result;
	}
	public function day_income_statistics($type = 0,$days = 30,$x = 'y', $y = 'count'){
	    $date = date("Y-m-d");
	    $todayTime=strtotime($date);
	    
	    $condition=['date_add' =>['egt',$todayTime-60*60*24*$days]];
	    if($type >=0){
	        $condition['type']=$type;
	    }
	    $finances=$this->table('__FINANCE_OP__')->where($condition)->group("FROM_UNIXTIME(date_add,'%Y-%m-%d')")
	    ->field("FROM_UNIXTIME(date_add,'%m-%d') as y,sum(abs(amount)) as count")->order("date_add desc")->select();
	    $result = [];
	    $count = [];
	    $keys = [];
	    for($i = 0; $i < $days ; $i ++){
	        $key = date('m-d',$todayTime - 60 * 60 *24 * $i);
	        $keys[] = $key;
	        if(count($finances) > 0 && $finances[0]['y'] == $key){
	            $count[] = $finances[0]['count'] + 0;
	            array_shift($finances);
	        }else{
	            $count[] = 0;
	        }
	    }
	    $result['yAxis'] = array_reverse($count);
	    $result['xAxis'] = array_reverse($keys) ;
	    return $result;
	}
	public function day_salecount_statistics($days = 30,$x = 'y', $y = 'count'){
		$date = date("Y-m-d");
		$todayTime=strtotime($date);
		 
		$condition=['date_add' =>['egt',$todayTime-60*60*24*$days]];
		
		$finances=M('items_customer')->where($condition)->group("FROM_UNIXTIME(date_add,'%Y-%m-%d')")
		->field("FROM_UNIXTIME(date_add,'%m-%d') as y,sum(quantity) as count")->order("date_add desc")->select();
		$result = [];
		$count = [];
		$keys = [];
		for($i = 0; $i < $days ; $i ++){
			$key = date('m-d',$todayTime - 60 * 60 *24 * $i);
			$keys[] = $key;
			if(count($finances) > 0 && $finances[0]['y'] == $key){
				$count[] = $finances[0]['count'] + 0;
				array_shift($finances);
			}else{
				$count[] = 0;
			}
		}
		$result['yAxis'] = array_reverse($count);
		$result['xAxis'] = array_reverse($keys) ;
		return $result;
	}
	
	public function active_statistics($number, $days , $endtime = 0){
		$date = date("Y-m-d");
		
		//将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
		$todayTime= strtotime($date);
		$condition = ['f.date_add' => [['egt', $todayTime - 60 * 60 * 24 * $days]]];
		
		if($endtime != 0){
			$condition['f.date_add'][] = ['elt', $endtime];
		}
		
		$count = $this->alias("f")
		->join("customer c", "c.customer_id = f.customer_id")
		->where($condition)
		->group("f.customer_id")
		->field("distinct c.customer_id")
		->having(" count(f.record_id) >= ".$number)
		->select();
		
		return count($count);
	}
	
	public function customer_days_statistics($type = 0, $days = 30,$x = 'y', $y = 'count'){
		$date = date("Y-m-d");
		
		
		//将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
		$todayTime= strtotime($date);
		$condition = ['date_add' => ['egt', $todayTime - 60 * 60 * 24 * $days]];
		$condition['islogin'] = 0;
		if($type != 0){
			$condition['type'] = $type;
		}
		$finances = D("user_statistics")->where($condition)->group("FROM_UNIXTIME(date_add,'%Y-%m-%d')")
		->field("FROM_UNIXTIME(date_add,'%m-%d') as y , count(customer_id) as count")->order("date_add desc")->select();
		$result = [];
		$count = [];
		$keys = [];
		for($i = 0; $i < $days ; $i ++){
			$key = date('m-d',$todayTime - 60 * 60 *24 * $i);
			$keys[] = $key;
			if(count($finances) > 0 && $finances[0]['y'] == $key){
				$count[] = $finances[0]['count'] + 0;
				array_shift($finances);
			}else{
				$count[] = 0;
			}
		}
		$result['yAxis'] = array_reverse($count);
		$result['xAxis'] = array_reverse($keys) ;
		return $result;
	}
	
	public function customer_month_statistics($type = 0,$month = 12,$x = 'y', $y = 'count'){
		$date = date("Y-m",strtotime("-".$month ." month"));
	
		//将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
	
		$condition = ['date_add' => ['egt', strtotime($date)]];
		$condition['islogin'] = 0;
		if($type != 0){
			$condition['type'] = $type;
		}
		$finances = D("user_statistics")->where($condition)->group("FROM_UNIXTIME(date_add,'%Y-%m')")
		->field("FROM_UNIXTIME(date_add,'%Y-%m') as y , count(customer_id) as count")->order("date_add desc")->select();
		$result = [];
		$count = [];
		$keys = [];
		for($i = 0; $i < $month ; $i ++){
			$key = date('Y-m',strtotime("-".$i." month"));
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

	public function month_statistics($type = 0,$month = 12,$x = 'y', $y = 'count'){
		$date = date("Y-m",strtotime("-".$month ." month"));

		//将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
		
		$condition = ['date_add' => ['egt', strtotime($date)]];
		if($type != 0){
			$condition['type'] = $type;
		}
		$finances = $this->where($condition)->group("FROM_UNIXTIME(date_add,'%Y-%m')")
		->field("FROM_UNIXTIME(date_add,'%Y-%m') as y , sum(amount) as count")->order("date_add desc")->select();
		$result = [];
		$count = [];
		$keys = [];
		for($i = 0; $i < $month ; $i ++){
			$key = date('Y-m',strtotime("-".$i." month"));
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
	public function month_income_statistics($type = 0,$month = 12,$x = 'y', $y = 'count'){
	    $date = date("Y-m",strtotime("-".$month ." month"));
	
	    //将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
	
	    $condition = ['date_add' => ['egt', strtotime($date)]];
	    if($type != 0){
	        $condition['type'] = $type;
	    }
	    $finances = $this->table("__FINANCE_OP__")->where($condition)->group("FROM_UNIXTIME(date_add,'%Y-%m')")
	    ->field("FROM_UNIXTIME(date_add,'%Y-%m') as y , sum(abs(amount)) as count")->order("date_add desc")->select();
	    $result = [];
	    $count = [];
	    $keys = [];
	    for($i = 0; $i < $month ; $i ++){
	        $key = date('Y-m',strtotime("-".$i." month"));
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
	
	public function month_salecount_statistics($month = 12,$x = 'y', $y = 'count'){
		$date = date("Y-m",strtotime("-".$month ." month"));
	
		//将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
	
		$condition = ['date_add' => ['egt', strtotime($date)]];
		
		$finances = M("items_customer")->where($condition)->group("FROM_UNIXTIME(date_add,'%Y-%m')")
		->field("FROM_UNIXTIME(date_add,'%Y-%m') as y , sum(quantity) as count")->order("date_add desc")->select();
		$result = [];
		$count = [];
		$keys = [];
		for($i = 0; $i < $month ; $i ++){
			$key = date('Y-m',strtotime("-".$i." month"));
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