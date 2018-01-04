<?php
namespace app\library;
abstract class UploadHelper{

	private $config = [];

	protected $path ;

	protected $error;

	protected $cate = "portrait";

	protected $sizes = [];

	protected $compress ;

	protected $save_tmp ;

	public static function getInstance($type = -1){
		$config = \app\library\SettingHelper::get("shuaibo_qiniu",['is_open' => 0]);
		if($config['is_open'] == 1){
			return new image\QiniuHelper($config);
		}

		return new image\MineUploadHelper();
	}

	public function __construct(){
		$this->compress = new image\ImageCompressHelper();
	}

	public function set_thumb_size($width, $height){
		$this->sizes[] = [$width, $height];
		return $this;
	}

	function mkDirs($dir){
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

	/**
	 * 设置分类，用户分出文件夹用
	 * */
	public function set_cate($cate){
		$this->cate = $cate;
		return $this;
	}

    protected function parseFiles(){
        $files = [];
        if(empty($_FILES)){
            return $files;
        }

        foreach ($_FILES as $file){
            if(empty($file['name'])){
                continue;
            }
            if(is_array($file['name'])){
                for($i = 0 ; $i < count($file['name']) ;$i++ ){
                    $file_data = ['name' => "file" . count($files),
                        'type' => $file['type'] [$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error' => $file['error'][$i],
                        'size' => $file['size'][$i]
                    ];
                    $files[] = $file_data;
                }
            }else{
                $file['name'] = "file".count($files);
                $files[] = $file;
            }
        }

        return $files;
    }

	public abstract function upload_image($file = '');

	public function download_image($image_url = ''){
		if(empty($image_url)){
			return ['errcode' => -101, 'message' => '请传入图片名'];
		}
		$filename = time().token() . '.png';

		ini_set('default_socket_timeout', 1);
		$source = null;
		try{
			$source = @file_get_contents($image_url);
		}catch(\Exception $e){
			return ['errcode' => -102 , 'message' => '找不到文件'];
		}

		if(empty($source)){
			return ['errcode' => -102 , 'message' => '找不到文件'];
		}

		$path = $this->path;

		if(!$this->mkDirs($path)){
			return ['errcode' => -102, 'message' => '创建文件失败'];
		}
		$tmpfile = $path . $filename;
		$result = @file_put_contents($tmpfile, $source);


		$res = $this->upload_image($tmpfile);
		@unlink($tmpfile);

		return $res;
	}

	public abstract function getThumbSuffix($width, $height);

	public abstract function upload_mul_image();
}