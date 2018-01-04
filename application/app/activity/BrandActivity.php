<?php
namespace app\app\activity;

class BrandActivity extends Activities{
	public function getActivity(){
		$brand = D("special_model")->getBrand($this->page);
		
		return ['errcode' => 0,'message' => '请求成功','content' => $brand];
	}
}