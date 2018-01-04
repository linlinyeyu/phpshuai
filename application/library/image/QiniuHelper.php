<?php
namespace app\library\image;
use org\Upload;
use app\library\UploadHelper;
class QiniuHelper extends UploadHelper{
	
	private $qiniu;
	
	public function setName($name){
		$this->name = $name;
		return $this;
	}
	
	function __construct($config){
		parent::__construct();
		$this->qiniu = new Upload($config);
		$this->path = $path = $_SERVER['DOCUMENT_ROOT'] . "/data/pic/";
	}
	
	public function upload_image($file = ''){
		if(!$this->qiniu){
			return ['errcode' => -101 ,'message' => '下载失败'];
		}
		
		
		if(!empty($file) && is_string($file)){
			$ext = $this->compress->get_ext($file);
			$file = array('file' => [
					'name' => "files",
					'type' => $ext[1],
					'tmp_name' => $file,
					'error' => 0,
					'savepath' =>  "upload/" .  $this->cate . "/" . date('Y_m_d') . "/"  ,
					'savename' => isset($this->name) ? $this->name : token() . "." . $ext[0],
					'size' => filesize($file)]);
		}
		
		$files = $this->parseFiles();

		foreach ($files as $v){
			$ext = $this->compress->get_ext($v['tmp_name']);
			$v['savepath'] = "upload/" .  $this->cate . "/" . date('Y_m_d') . "/";
			$v['savename'] =  token() . '.' . $ext[0];
			$file = ['file' => $v];
		}
		$res = $this->qiniu->upload($file);
		$values = [];
		if(!empty($res)){
			foreach ($res as $val){
				$values = $val;
			}
		}
		
		if(empty($values)){
			return ['errcode' => -102, 'message' => '请求失败'];
		}
		return ['errcode' => 0 , 'message' => '请求成功', 
				'content' => [
						'name' => $values['name'] ,
						'url' => $values['url'],
						'type' => $values['type'],
						'size' => $values['size']
				]];
	}
	
	public function upload_mul_image(){
		if(!$this->qiniu){
			return ['errcode' => -101 ,'message' => '下载失败'];
		}
		$path = $this->path;
		
		if(!$this->mkDirs($path)){
			return ['errcode' => -102, 'message' => '创建文件失败'];
		}
		
		$files = $this->parseFiles();
		$compress = $this->compress;
		foreach ($files as $file){
			$ext = $compress->get_ext($file['tmp_name']);
			
			$filename= $path . "/" .  token() . '.' . $ext[0];
				
			if($compress->image_compress($file['tmp_name'], $filename)){
				$file['tmp_name'] = $filename;
				$file['size'] = filesize($filename);
				$file['savepath'] = "upload/" .  $this->cate . "/" . date('Y_m_d') . "/";
				$file['savename'] =  token() . '.' . $ext[0];
			}
		}
		
		$res = $this->qiniu->upload($files);
		$values = [];
		if(!empty($res)){
			foreach ($res as $val){
				$data = ['name' => $val['name'] ,
						'url' => $val['url'],
						'type' => $val['type'],
						'size' => $val['size']];
				$values[] = $data;
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
		return "?imageView2/2/w/{$width}/h/{$height}";
	}
	
}