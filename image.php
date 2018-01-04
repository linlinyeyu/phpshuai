<?php
if(!isset($_GET['w']) || !isset($_GET['h']) || !isset($_GET['dir']) || !isset($_GET['name'])){
	header("HTTP/1.1 404 Not Found");
	header("Status: 404 Not Found");
	exit;
}


//使用缓存
session_start();
header("Cache-Control: private, max-age=10800, pre-check=10800");
header("Pragma: private");
header("Expires: " . date(DATE_RFC822,strtotime(" 2 day")));

if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])){
	// if the browser has a cached version of this image, send 304
	header('Last-Modified: '.$_SERVER['HTTP_IF_MODIFIED_SINCE'],true,304);
	exit;
}





$file_name = $_SERVER['DOCUMENT_ROOT']. "/upload/" . $_GET['dir'] ."/" .$_GET['name'];

$max = 1200;
$width = $_GET['w'] > $max ? $max : $_GET['w'] ;
$height = $_GET['h'] > $max ? $max : $_GET['h'];
$new_file_name = $file_name . "_w={$width}&h={$height}.jpg";

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == filemtime($new_file_name))) {
	// send the last mod time of the file back
	header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($new_file_name)).' GMT',true, 304);
	exit;
}


if(!file_exists($new_file_name)){
	include "application/library/image/ImageCompressHelper.php";
	$helper = new \app\library\image\ImageCompressHelper();
	$helper->setmin_height($height);
	$helper->setmin_width($width);
	$helper->image_compress($file_name, $new_file_name);
}

$size = getimagesize($new_file_name);
$fp = fopen($new_file_name, "rb");
if ($size && $fp) {
	header("Content-type: {$size['mime']}");
	fpassthru($fp);
	exit;
}