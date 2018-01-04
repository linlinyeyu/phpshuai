<?php
namespace app\admin;
use think\Controller;
use \org\Rbac;
use think\Response;
use think\Cache;
class Admin extends Controller
{
	protected $image_url;
	
	/** * 过滤emoji表情
	 *  * @param type $str
	 *  * @return type
	 */
	public function replace_emoji($str) {
		$new_str = preg_replace_callback(
				'/./u',
				function (array $match) {
					return strlen($match[0]) >= 4 ? '' : $match[0];
				},
				$str);
	
		return trim($new_str);
	}
	
	public function has_emoji($str){
		global $t ;
		$t = false;
		preg_replace_callback(
				'/./u',
				function (array $match) {
					 if(strlen($match[0]) >= 4){
					 	$t = true;
					 }
				},
				$str);
		return $t;
	}
	
    public function _initialize(){
    	if(isset($_POST['PHPSESSIONID'])){
    		session_id($_POST['PHPSESSIONID']);
    	}
    	
        // 后台用户权限检查
        if (C('USER_AUTH_ON') && !in_array(MODULE_NAME, explode(',', C('NOT_AUTH_MODULE')))) {
            if (!RBAC::AccessDecision()) {
                //检查认证识别号
                if (!session('?'.C('USER_AUTH_KEY'))) {
                    //跳转到认证网关
                    $this->redirect(U("login/index"));
                    exit;
                }
                // 没有权限 抛出错误
                if (C('RBAC_ERROR_PAGE')) {
                    // 定义权限错误页面
                    $this->error(C('RBAC_ERROR_PAGE'));
                } else {
                    if (C('GUEST_AUTH_ON')) {
                        $this->redirect(C('USER_AUTH_GATEWAY'));exit;
                    }
                    $admin_auth = Cache::get("shop_admin_auth");
                    // 提示错误信息
                    if(CONTROLLER_NAME == "panel" && ACTION_NAME == "index"){
                    	if(empty($admin_auth)){
                    		Cache::set("shop_admin_auth", 1,10);
                    	}else{
                    		Cache::rm("shop_admin_auth");
                    		session(null);
                    		$this->redirect(U('login/index'));
                    	}
                    	
                    }
                    $this->error(L('_VALID_ACCESS_'));exit;
                }
            }
        }
        
        $this->image_url = \app\library\SettingHelper::get("shuaibo_image_url");
        $this->assign("img_url" , $this->image_url);
        //print_r($_SESSION);exit;

    }

    public function ajaxReturn($data = '', $type = 'json')
    {
        Response::send($data, $type);
    }

    /**
     * 操作成功跳转的快捷方法
     * @access public
     * @param mixed $msg 提示信息
     * @param mixed $data 返回的数据
     * @param string $url 跳转的URL地址
     * @param integer $wait 跳转等待时间
     * @return mixed
     */
    public function success($msg = '', $data = '', $url = null, $wait = 3)
    {
        $code = 1;
        if (is_numeric($msg)) {
            $code = $msg;
            $msg  = '';
        }
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
            'url'  => is_null($url) && isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : $url,
            'wait' => $wait,
        ];

        $type = IS_AJAX ? \think\Config::get('default_ajax_return') : \think\Config::get('default_return_type');
        if ('html' == $type) {
            $result = \think\View::instance()->fetch(\think\Config::get('dispatch_success_tmpl'), $result);
        }
        Response::type($type);
        if(IS_AJAX){
            $this->ajaxReturn($result);exit;
        }else{
            return $result;
        }
    }

    /**
     * 操作错误跳转的快捷方法
     * @access public
     * @param mixed $msg 提示信息
     * @param mixed $data 返回的数据
     * @param string $url 跳转的URL地址
     * @param integer $wait 跳转等待时间
     * @return mixed
     */
    public function error($msg = '', $data = '', $url = null, $wait = 3)
    {
        $code = 0;
        if (is_numeric($msg)) {
            $code = $msg;
            $msg  = '';
        }
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
            'url'  => is_null($url) ? '' : $url,
            'wait' => $wait,
        ];

        $type = IS_AJAX ? \think\Config::get('default_ajax_return') : \think\Config::get('default_return_type');

        if ('html' == $type) {
            $result = \think\View::instance()->fetch(\think\Config::get('dispatch_error_tmpl'), $result);
        }
        Response::type($type);
        if(IS_AJAX){
            $this->ajaxReturn($result);exit;
        }else{
        	die();
            return $result;
        }
    }
    
    protected function getExcel2($fileName, $headArr = [], $data = []){
    	vendor("PHPExcel.Classes.PHPExcel");
    	//对数据进行检验
    	if(empty($data) ||  !is_array($data) || empty($data[0])){
    		die("data must be a array");
    	}
    	//检查文件名
    	if(empty($fileName)){
    		exit;
    	}
    	
    	$date = date("Y_m_d_H_i_s",time());
    	$fileName .= "_{$date}.xls";
    	
    	//创建PHPExcel对象，注意，不能少了\
    	$objPHPExcel = new \PHPExcel();
    	$objProps = $objPHPExcel->getProperties();
    	
    	//设置表头
    	$objActSheet = $objPHPExcel->getActiveSheet();
    	
    	$column = 1;
    	for($i = 0; $i < count($headArr); $i++){
    		$arr = $headArr[$i];
    		$key = ord("A");
    		foreach($arr as $v){
    			$colum = chr($key);
    			$objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.$column, $v);
    			$key += 1;
    			$objPHPExcel->getActiveSheet()->getColumnDimension($colum)->setWidth(25);
    		}
    		$column ++;
    		foreach($data[$i] as $key => $rows){ //行写入
    			var_dump($rows);
    			$span = ord("A");
    			foreach($rows as $keyName=>$value){// 列写入
    				$j = chr($span);
    				$objActSheet->setCellValueExplicit($j.$column, $value,\PHPExcel_Cell_DataType::TYPE_STRING);
    				$span++;
    			}
    			$column++;
    		}
    		
    	}
    	
    	
    	$fileName = iconv("utf-8", "gb2312", $fileName);
    	//重命名表
    	// $objPHPExcel->getActiveSheet()->setTitle('test');
    	//设置活动单指数到第一个表,所以Excel打开这是第一个表
    	$objPHPExcel->setActiveSheetIndex(0);
    	ob_end_clean();
    	header('Content-Type: application/vnd.ms-excel');
    	header("Content-Disposition: attachment;filename=\"$fileName\"");
    	header('Cache-Control: max-age=0');
    	 
    	$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    	$objWriter->save('php://output'); //文件通过浏览器下载
    	exit;
    }



    protected function getExcel($fileName,$headArr,$data){
    	vendor("PHPExcel.Classes.PHPExcel");
    	//对数据进行检验
    	if(empty($data) || !is_array($data)){
    		die("data must be a array");
    	}
    	//检查文件名
    	if(empty($fileName)){
    		exit;
    	}
    
    	$date = date("Y_m_d_H_i_s",time());
    	$fileName .= "_{$date}.xls";
    
    	//创建PHPExcel对象，注意，不能少了\
    	$objPHPExcel = new \PHPExcel();
    	$objProps = $objPHPExcel->getProperties();
    
    	//设置表头
    	$key = ord("A");
    	
    	
    	foreach($headArr as $v){
    		$colum = chr($key);
    		$objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.'1', $v);
    		$key += 1;
    		$objPHPExcel->getActiveSheet()->getColumnDimension($colum)->setWidth(25);
    	}
    
    	$column = 2;
    	$objActSheet = $objPHPExcel->getActiveSheet();
    	foreach($data as $key => $rows){ //行写入
    		$span = ord("A");
    		foreach($rows as $keyName=>$value){// 列写入
    			$j = chr($span);
    			$objActSheet->setCellValue($j.$column, $value);
    			$span++;
    		}
    		$column++;
    	}
    
    	$fileName = iconv("utf-8", "gb2312", $fileName);
    	//重命名表
    	// $objPHPExcel->getActiveSheet()->setTitle('test');
    	//设置活动单指数到第一个表,所以Excel打开这是第一个表
    	$objPHPExcel->setActiveSheetIndex(0);
    	ob_end_clean();
    	header('Content-Type: application/vnd.ms-excel');
    	header("Content-Disposition: attachment;filename=\"$fileName\"");
    	header('Cache-Control: max-age=0');
    	
    	$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    	$objWriter->save('php://output'); //文件通过浏览器下载
    	exit;
    }
    
	public function export($fileName,$sql,$header){

		$pdo = new \PDO("mysql:host=".C('database.hostname').";dbname=".C('database.database').";charset=".C("database.charset"), C("database.username"), C("database.password"));
		$pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
		$pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

		$date = date("Y_m_d_H_i_s",time());
		$fileName .= "_{$date}.xls";
		$uresult = $pdo->query($sql);
		if ($uresult) {
			header("Content-type:application/vnd.ms-excel");
			$fileName = iconv("utf-8", "utf-8", $fileName);
			header("Content-Disposition: attachment;filename=\"$fileName\"");
			header('Cache-Control: max-age=0');
			
			if(!empty($header)){
				foreach ($header as $h){
					echo $h ."\t";
				}
				echo PHP_EOL;
			}

			while ($row = $uresult->fetch(\PDO::FETCH_ASSOC)) {
				if(isset($row['nickname'])){
					$row['nickname'] = $this->replace_emoji($row['nickname']);
				}

				if(isset($row['pname'])){
					$row['pname'] = $this->replace_emoji($row['pname']);
				}

				foreach($row as $c){
					$c = str_replace("'", "", $c);
					$c = str_replace('"', "", $c);
                    $c = str_replace(PHP_EOL, "", $c);
                    echo  htmlspecialchars($c)."\t";
				}
				echo PHP_EOL;
			}
			
		}
		$pdo = null;
	}

    public function excel_export($fileName,$sql,$header){
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $pdo = new \PDO("mysql:host=".C('database.hostname').";dbname=".C('database.database').";charset=".C("database.charset"), C("database.username"), C("database.password"));
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $date = date("Y_m_d_H_i_s",time());
        $fileName .= "_{$date}";
        $uresult = $pdo->query($sql);

        if ($uresult) {
            $objPHPExcel->createSheet();
            $sheetHeader = array("A","B","C","D","E","F","G",'H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
            $subObject = $objPHPExcel->getSheet(0);
            foreach ($header as $key => $value) {
                $subObject->setCellValue($sheetHeader[$key]."1",$value);
            }

            $k = 2;
            while ($row = $uresult->fetch(\PDO::FETCH_ASSOC)) {
                if(isset($row['nickname'])){
                    $row['nickname'] = $this->replace_emoji($row['nickname']);
                }

                if(isset($row['pname'])){
                    $row['pname'] = $this->replace_emoji($row['pname']);
                }
                $i = 0;
                foreach ($row as $c) {
                    $c = str_replace("'", "", $c);
                    $c = str_replace('"', "", $c);
                    $c = str_replace(PHP_EOL, "", $c);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($sheetHeader[$i++].$k,$c);
                }
                $k++;
            }

            $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            //多浏览器下兼容中文标题
            $encoded_filename = urlencode($fileName);
            $ua = $_SERVER["HTTP_USER_AGENT"];
            if (preg_match("/MSIE/", $ua)) {
                header('Content-Disposition: attachment; filename="' . $encoded_filename . '.xls"');
            } else if (preg_match("/Firefox/", $ua)) {
                header('Content-Disposition: attachment; filename*="utf8\'\'' . $fileName . '.xls"');
            } else {
                header('Content-Disposition: attachment; filename="' . $fileName . '.xls"');
            }
            
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit;
        }
    }

    /* 
    *处理Excel导出 
    *@param $datas array 设置表格数据 
    *@param $titlename string 设置head 
    *@param $title string 设置表头 
    */ 
    public function excelData($filename,$sql,$titlename){
        $pdo = new \PDO("mysql:host=".C('database.hostname').";dbname=".C('database.database').";charset=".C("database.charset"), C("database.username"), C("database.password"));
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $date = date("Y_m_d_H_i_s",time());
        $filename .= "_{$date}.xls";
        $uresult = $pdo->query($sql);
        if ($uresult) {
            //获取数据
            $datas = $uresult->fetchAll(\PDO::FETCH_ASSOC);
            //设置表头
            $header = "<tr>";
            foreach ($titlename as $value) {
                $header .= "<th>".$value."</th>";
            }
            $header .= "</tr>";

            $str = "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\"\r\nxmlns:x=\"urn:schemas-microsoft-com:office:excel\"\r\nxmlns=\"http://www.w3.org/TR/REC-html40\">\r\n<head>\r\n<meta http-equiv=Content-Type content=\"text/html; charset=utf-8\">\r\n</head>\r\n<body>"; 
            $str .="<table border=1><thead>".$header."</thead>";
            $str .="<tbody>"; 
            foreach ($datas  as $key=> $rt ) 
            { 
                $str .= "<tr>"; 
                foreach ( $rt as $k => $v ) 
                { 
                    $str .= "<td>{$v}</td>"; 
                } 
                $str .= "</tr>\n"; 
            } 
            $str .= "</tbody></table></body></html>"; 
            header( "Content-Type: application/vnd.ms-excel; name='excel'" ); 
            header( "Content-type: application/octet-stream" ); 
            header( "Content-Disposition: attachment; filename=".$filename ); 
            header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" ); 
            header( "Pragma: no-cache" ); 
            header( "Expires: 0" ); 
            exit( $str );   
        } 
    }

	public function log($comment = ''){
		D("user_action")->saveAction(session("userid"),$comment);
	}
}