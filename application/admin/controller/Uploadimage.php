<?php
namespace app\admin\controller;
use app\admin\Admin;
use org\Upload;
class Uploadimage extends Admin
{
	public function upload_image(){
		$upload = \app\library\UploadHelper::getInstance();
		$cate = I("cate");
		if($cate){
			$upload->set_cate($cate);
		}
		$res = $upload->upload_image();
		echo json_encode($res);
	}
	
	public function download(){
		$upload = \app\library\UploadHelper::getInstance();
		$cate = I("cate");
		if($cate){
			$upload->set_cate($cate);
		}
		$res = $upload->download_image(I("image"));
		echo json_encode($res);
	}
	
	public function upload_image_baidu(){
		$upload = \app\library\UploadHelper::getInstance();
		$upload->set_cate("baidu");
		$res = $upload->upload_image();
		if($res['errcode'] == 0){
			$content = $res['content'];
			$content['state'] = "SUCCESS";
			$content['originalName'] = $content['name'];
			echo json_encode($content);
		}else{
			echo json_encode(['state' => "FAIL"]);
		}
	}
	public function upload_mul_image(){
		$upload = \app\library\UploadHelper::getInstance();
		$cate = I("cate");
		if($cate){
			$upload->set_cate($cate);
		}
		$res = $upload->upload_mul_image();
		echo json_encode($res);
	}
	
}
