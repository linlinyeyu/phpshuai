<?php
namespace app\library;
class CertHandler{
	
	public static function saveCertToPath($filename, $target, $type,  $return_str = true){
		if(!file_exists($filename)){
			return false;
		}
		
		$dir = $_SERVER['DOCUMENT_ROOT']. "/cert/" . $type;
		if(!mkDirs($dir)){
			return false;
		}
		$data = file_get_contents($filename);
		if(!file_put_contents($dir . "/" . $target, $data)){
			return false;
		}
		
		$str = "";
		if($return_str){
			$data = trim(nl2br($data));
			$data = explode('<br />', $data);
			$data = array_slice($data, 1 , - 1);
			foreach ($data as $s){
				if(strpos(trim($s), "---") === false){
					$str .= trim($s);
				}
			}
		}
		return ["/cert/" . $type . "/" . $target, $str];
	}
	
	
	
}