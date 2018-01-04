<?php
namespace app\app\controller;
class UploadAction
{
	public function upload_image(){
		
		$customer_id = get_customer_id();
		
		if(empty($customer_id)){
			return ['errcode' => 99, 'message' => '请重新登录'];
		}
		
		$upload = \app\library\UploadHelper::getInstance();
		$cate = I("cate");
		if($cate){
			$upload->set_cate($cate);
		}
		$res = $upload->upload_image();
		
		return $res;
	}
	
	public function upload_mul_image(){
		$customer_id = get_customer_id();
		
		if(empty($customer_id)){
			return ['errcode' => 99, 'message' => '请重新登录'];
		}
		
		$upload = \app\library\UploadHelper::getInstance();
		$cate = I("cate");
		if($cate){
			$upload->set_cate($cate);
		}
		$res = $upload->upload_mul_image();
		return $res;
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
        die();
    }
	
}
