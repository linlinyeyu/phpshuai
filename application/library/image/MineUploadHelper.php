<?php
namespace app\library\image;
use app\library\UploadHelper;
class MineUploadHelper extends UploadHelper{
	
	function __construct(){
		parent::__construct();
		$path = $_SERVER['DOCUMENT_ROOT'] . "/";
		if(!$this->mkDirs($path)){
			$this->error = ['errcode' => -101, 'message' => "创建文件夹失败"];
		}
		$this->path = $path;
	}

	public function upload_image($files = ''){
		if($this->error){
			return $this->error;
		}
		
		if(!empty($files) && is_string($files)){
			$ext = $this->compress->get_ext($files);
			$files = array('file' => [
					'name' => "files",
					'type' => $ext[1],
					'tmp_name' => $files,
					'error' => 0,
					'size' => filesize($files)]);
		}
		if(empty($files)){
			$files = $this->parseFiles();
		}
		if(empty($files)){
			return ['errcode' => -101, 'message' => '没有上传文件'];
		}
		$file = [];
		foreach ($files as $file1){
			$file = $file1;
		}
		
		$ext = $this->compress->get_ext($file['tmp_name']);
		
		$dir = $this->path . "/" ;
		
		$d = date('Y_m_d');
		
		$name = "/upload/" .  $this->cate . "/" . $d . "/" . token() . '.' . $ext[0];
		
		$file_name = $dir . $name;
		
		if(!$this->mkDirs($dir . "/upload/" . $this->cate . "/" . $d . "/")){
			return ['errcode' => -101 ,'message' =>'文件夹创建失败'];
		}
		copy($file['tmp_name'], $file_name);
		
		return ['errcode' => 0 , 'message' => '请求成功', 
				'content' => [
						'name' => $name ,
						'url' => \app\library\SettingHelper::get("shuaibo_image_url") . $name,
						'type' => $ext[1],
				]];
	}
	
	public function upload_mul_image(){
		
		$dir = $this->path . "/" ;
		$d = date('Y_m_d');
		if(!$this->mkDirs($dir . "/upload/" . $this->cate . "/" .$d . "/")){
			return ['errcode' => -101 ,'message' =>'文件夹创建失败'];
		}
		
		$files = $this->parseFiles();
		$compress = $this->compress;
		$values = [];
		
		$image_url = \app\library\SettingHelper::get("shuaibo_image_url");
		foreach ($files as $file){
			$ext = $compress->get_ext($file['tmp_name']);
			$name = "/upload/" .  $this->cate . "/" . $d . "/" .  token() . '.' . $ext[0];
			$filename = $dir . $name;
			$this->compress->set_percent(1);
			
			$this->compress->image_compress($file['tmp_name'], $file_name);
			
			$this->compress->set_percent(null);
			
			$values[] = ['name' => $filename, 'url' => $image_url . $filename,'type' => $ext[1]];
			foreach ($this->sizes as $size){
				$this->compress->setmin_width($size[0]);
				$this->compress->setmin_height($size[1]);
				$new_filename = $file_name . "_w={$size[0]}&h={$size[1]}.jpg" ;
				$this->compress->image_compress($file['tmp_name'], $new_filename);
			}
		}
		foreach ($files as $file){
			@unlink($file['tmp_name']);
		}
		
		if(empty($values)){
			return ['errcode' => -102, 'message' => '请求失败'];
		}
		return ['errcode' => 0 , 'message' => '请求成功',
				'content' => $values];
	}
	
	public function getThumbSuffix($width , $height){
		return "_w={$width}&h={$height}.jpg";
	}
	
}