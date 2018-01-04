<?php
namespace app\library\image;

/**
 * @author 
 * 此类用来压缩图片
 * */
class ImageCompressHelper{
	
	
	/**
	 * kb_limit 单位kb，限制压缩后的图片大小
	 * min_width,min_height 最小宽度与最小高度 单位像素
	 * @var unknown*/
	private $kb_limit;
	
	private $min_width;
	
	private $min_height;
	
	private $percent;
	
	const DEFAULT_KB_LIMIT = 500;
	
	public function setmin_width($min_width){
		$this->min_width = $min_width;
	}
	public function setmin_height($min_height){
		$this->min_height=$min_height;
	}
	public function setkb_limit($kb_limit){
		$this->kb_limit=$kb_limit;
	}
	
	public function set_percent($percent){
		$this->percent = $percent;
	}
	
	public function image_compress($file_name,$new_filename){
		
		$pic_scal_arr = getimagesize($file_name);
		$percent = 1;
		//以宽度为基准缩放百分比
		if(!empty($this->min_width) && $pic_scal_arr[0] / $this->min_width > $percent){
			$percent = $pic_scal_arr[0] / $this->min_width;
		}
		
		//以高度为百分比
		if(!empty($this->min_height) && $pic_scal_arr[1] / $this->min_height > $percent ){
			$percent = $pic_scal_arr[1] / $this->min_height;
		}
		
		
		//以图片大小为基准
		$kb_limit=$this->kb_limit;
		if(empty($kb_limit) && empty($this->min_width)){
			$kb_limit = self::DEFAULT_KB_LIMIT;
		}
		$kb_count=$kb_limit*1024;
		
		if(!empty($kb_count)){
			$imageweight=filesize($file_name);
			$percent=sqrt($imageweight/$kb_count);
		}
		
		//如果指定百分比，则使用指定百分比
		if(!empty($this->percent)){
			$percent = $this->percent;
		}
		$pic_creat = '';
		switch($pic_scal_arr['mime']){
			case 'image/jpeg':
				$pic_creat = @imagecreatefromjpeg($file_name);
				break;
			case 'image/gif':
				$pic_creat = @imagecreatefromgif($file_name);
				break;
			case 'image/png':
				$pic_creat = @imagecreatefrompng($file_name);
				break;
			case 'image/wbmp':
				$pic_creat = @imagecreatefromwbmp($file_name);
				break;
			default:
				return false;
		}
		if(!$pic_creat){
			return false;
		}
		
		$re_width = round($pic_scal_arr[0] / $percent);
		$re_height = round($pic_scal_arr[1] / $percent);
		
		//创建空图象
		$new_pic = @imagecreatetruecolor($re_width,$re_height);
			
		if(!$new_pic){
			return false;
		}
		//复制图象
		if(!@imagecopyresampled($new_pic,$pic_creat,0,0,0,0,$re_width,$re_height,$pic_scal_arr[0],$pic_scal_arr[1])){
			return false;
		}
		$dirs = explode("/", $new_filename);
		
		$dirs = array_splice($dirs, 0, count($dirs) - 1);
		
		$dir = implode("/", $dirs);
		$this->mkDirs($dir);
		//输出文件
		switch($pic_scal_arr['mime']){
			case 'image/png':
				$c = imagecolorallocatealpha($new_pic , 0 , 0 , 0 , 127);//拾取一个完全透明的颜色
				imagealphablending($new_pic , true);//关闭混合模式，以便透明颜色能覆盖原画布
				imagefill($new_pic , 0 , 0 , $c);//填充
				imagesavealpha($new_pic , true);//设置保存PNG时保留透明通道信息
				$out_file = @imagepng($new_pic,$new_filename);
				break;
			case 'image/jpeg':
				$out_file = @imagejpeg($new_pic,$new_filename);
				break;
			case 'image/jpg':
				$out_file = @imagejpeg($new_pic,$new_filename);
				break;
			case 'image/gif':
				$out_file = @imagegif($new_pic,$new_filename);
				break;
			case 'image/bmp':
				$out_file = @imagebmp($new_pic,$new_filename);
				break;
			default:
				return false;
				break;
		}
		if($out_file){
			return true;
		}else{
			return false;
		}
	}
	
	public function get_ext($file_name){
		$pic_scal_arr = getimagesize($file_name);
		
		$ext = "jpg";
		
		$mine = "image/jpg";
		
		if(!empty($pic_scal_arr)){
			$mine = $pic_scal_arr['mime'];
			//输出文件
			switch($mine){
				case 'image/png':
					$ext = "png" ;
					break;
				case 'image/jpeg':
					$ext = "jpg";
					break;
				case 'image/jpg':
					$ext = "jpg";
					break;
				case 'image/gif':
					$ext = "gif";
					break;
				case 'image/bmp':
					$ext = "bmp";
					break;
				default:
					$ext = "jpg";
					break;
			}
		}
		return [$ext, $mine];
	}
	
	public function  mkDirs($dir){
		if(!is_dir($dir)){
			if(!mkDirs(dirname($dir))){
				return false;
			}
			if(!mkdir($dir,0777)){
				return false;
			}
		}
		return true;
	}
}