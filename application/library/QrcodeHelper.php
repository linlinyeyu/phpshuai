<?php
namespace app\library;
class QrcodeHelper{
	
	public static function generate($url, $logo = ""){
		if(empty($logo)){
			$logo = $_SERVER['DOCUMENT_ROOT'] . "/public/common/image/default-avater.png";
		}
		ini_set('default_socket_timeout', 1);
		$logo = imagecreatefromstring(file_get_contents($logo));
		$logo_width = imagesx($logo);//logo图片宽度
		$logo_height = imagesy($logo);//logo图片高度
		vendor("Qrcode.phpqrcode");
		
		if(empty($url)){
			return;
		}
		$data = urldecode($url);
		
		$level = 'M';
		
		$size = 4;
		
		$dir = $_SERVER['DOCUMENT_ROOT'] . "/img";
		if(!is_dir($dir)){
			mkdir($dir, 0777,1);
		}
		$filename = $dir ."/" . token() . ".png";
		\QRcode::png($data, $filename, $level, $size);
		if(empty($logo)){
			return $filename;
		}
		$img = imagecreatefromstring(file_get_contents($filename));
		$QR_width = imagesx($img);//二维码图片宽度
		$QR_height = imagesy($img);//二维码图片高度
		$logo_qr_width = $QR_width / 5;
		$scale = $logo_width/$logo_qr_width;
		$logo_qr_height = $logo_height/$scale;
		$from_width = ($QR_width - $logo_qr_width) / 2;
		//重新组合图片并调整大小
		imagecopyresampled($img, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
				$logo_qr_height, $logo_width, $logo_height);
		imagepng($img, $filename);
		imagedestroy($img);
		imagedestroy($logo);
		return $filename;
	}
	
	private $name = [];
	
	public static function getInstance(){
		return new QrcodeHelper();
	}
	
	public function generate_customer1($customer ){
		if(empty($customer)){
			return ['errcode' => -101, 'message' => '找不到相关人员'];
		}
		
		$position = \app\library\SettingHelper::get("bear_qrcode_position",
				[
						'name' => ['x'=> 50,'y' => 24 , 'w' => 23 ,'h' => 50, 'center' => 0],
						'image' => ['x'=> 24,'y' => 24 , 'w' => 18 ,'h' => 97, 'center' => 0],
						'qrcode' => ['x'=> 27,'y' => 39 , 'w' => 50 ,'h' => 100, 'center' => 1],
						'background' => "",
						'number' => '123'
				]);
		if(empty($customer['ticket']) && !empty($customer['uuid']) && !empty($customer['customer_id'])){
			$config = \app\library\SettingHelper::get_pay_params(4);
			if(!empty($config)){
				$client = \app\library\weixin\WeixinClient::getInstance($config);
				$content = $client->create_qrcode($customer['uuid']);
				if(!empty($content) && isset($content['ticket'])){
					$customer['ticket'] = $content['ticket'];
					M("customer")->where(['customer_id' => $customer['customer_id']])->save(['ticket' => $content['ticket']]);
				}
			}
			if(empty($customer['ticket'])){
				return ['errcode' => -101 , 'message' =>'无法获取ticket'];
			}
			
		}
		if(!empty($customer['qrcode']) && $customer['qrcode_number'] == $position['number']){
			return ['errcode' => 0 ,'message' => '无须更新', 'content' => ['name' => $customer['qrcode']]];
		}
		
		global  $ca;
		if(empty($ca)){
			$ca = ['u1F600' => ['tag' => "\ud83d\ude00", "bp" => "1539 0"], 'u1F601' => ['tag' => "\ud83d\ude01", "bp" => "1566 0"], 'u1F602' => ['tag' => "\ud83d\ude02", "bp" => "1593 0"], 'u1F603' => ['tag' => "\ud83d\ude03", "bp" => "1620 0"], 'u1F605' => ['tag' => "\ud83d\ude05", "bp" => "1647 0"], 'u1F606' => ['tag' => "\ud83d\ude06", "bp" => "1674 0"], 'u1F607' => ['tag' => "\ud83d\ude07", "bp" => "1701 0"], 'u1F608' => ['tag' => "\ud83d\ude08", "bp" => "1728 0"], 'u1F609' => ['tag' => "\ud83d\ude09", "bp" => "1755 0"], 'u1F611' => ['tag' => "\ud83d\ude11", "bp" => "1944 0"], 'u1F612' => ['tag' => "\ud83d\ude12", "bp" => "1971 0"], 'u1F613' => ['tag' => "\ud83d\ude13", "bp" => "0 27"], 'u1F614' => ['tag' => "\ud83d\ude14", "bp" => "27 27"], 'u1F615' => ['tag' => "\ud83d\ude15", "bp" => "54 27"], 'u1F616' => ['tag' => "\ud83d\ude16", "bp" => "81 27"], 'u1F618' => ['tag' => "\ud83d\ude18", "bp" => "108 27"], 'u1F621' => ['tag' => "\ud83d\ude21", "bp" => "270 27"], 'u1F622' => ['tag' => "\ud83d\ude22", "bp" => "297 27"], 'u1F623' => ['tag' => "\ud83d\ude23", "bp" => "324 27"], 'u1F624' => ['tag' => "\ud83d\ude24", "bp" => "351 27"], 'u1F628' => ['tag' => "\ud83d\ude28", "bp" => "378 27"], 'u1F629' => ['tag' => "\ud83d\ude29", "bp" => "405 27"], 'u1F630' => ['tag' => "\ud83d\ude30", "bp" => "567 27"], 'u1F631' => ['tag' => "\ud83d\ude31", "bp" => "594 27"], 'u1F632' => ['tag' => "\ud83d\ude32", "bp" => "621 27"], 'u1F633' => ['tag' => "\ud83d\ude33", "bp" => "648 27"], 'u1F634' => ['tag' => "\ud83d\ude34", "bp" => "675 27"], 'u1F635' => ['tag' => "\ud83d\ude35", "bp" => "702 27"], 'u1F636' => ['tag' => "\ud83d\ude36", "bp" => "729 27"], 'u1F637' => ['tag' => "\ud83d\ude37", "bp" => "756 27"], 'u1F3A4' => ['tag' => "\ud83c\udfa4", "bp" => "486 0"], 'u1F3B2' => ['tag' => "\ud83c\udfb2", "bp" => "513 0"], 'u1F3B5' => ['tag' => "\ud83c\udfb5", "bp" => "540 0"], 'u1F3C0' => ['tag' => "\ud83c\udfc0", "bp" => "567 0"], 'u1F3C2' => ['tag' => "\ud83c\udfc2", "bp" => "594 0"], 'u1F3E1' => ['tag' => "\ud83c\udfe1", "bp" => "621 0"], 'u1F004' => ['tag' => "\ud83c\udc04", "bp" => "0 0"], 'u1F4A1' => ['tag' => "\ud83d\udca1", "bp" => "1188 0"], 'u1F4A2' => ['tag' => "\ud83d\udca2", "bp" => "1215 0"], 'u1F4A3' => ['tag' => "\ud83d\udca3", "bp" => "1242 0"], 'u1F4A4' => ['tag' => "\ud83d\udca4", "bp" => "1269 0"], 'u1F4A9' => ['tag' => "\ud83d\udca9", "bp" => "1296 0"], 'u1F4AA' => ['tag' => "\ud83d\udcaa", "bp" => "1323 0"], 'u1F4B0' => ['tag' => "\ud83d\udcb0", "bp" => "1350 0"], 'u1F4DA' => ['tag' => "\ud83d\udcda", "bp" => "1377 0"], 'u1F4DE' => ['tag' => "\ud83d\udcde", "bp" => "1404 0"], 'u1F4E2' => ['tag' => "\ud83d\udce2", "bp" => "1431 0"], 'u1F6AB' => ['tag' => "\ud83d\udeab", "bp" => "918 27"], 'u1F6BF' => ['tag' => "\ud83d\udebf", "bp" => "945 27"], 'u1F30F' => ['tag' => "\ud83c\udf0f", "bp" => "27 0"], 'u1F33B' => ['tag' => "\ud83c\udf3b", "bp" => "135 0"], 'u1F35A' => ['tag' => "\ud83c\udf5a", "bp" => "216 0"], 'u1F36B' => ['tag' => "\ud83c\udf6b", "bp" => "270 0"], 'u1F37B' => ['tag' => "\ud83c\udf7b", "bp" => "324 0"], 'u1F44A' => ['tag' => "\ud83d\udc4a", "bp" => "729 0"], 'u1F44C' => ['tag' => "\ud83d\udc4c", "bp" => "756 0"], 'u1F44D' => ['tag' => "\ud83d\udc4d", "bp" => "783 0"], 'u1F44E' => ['tag' => "\ud83d\udc4e", "bp" => "810 0"], 'u1F44F' => ['tag' => "\ud83d\udc4f", "bp" => "837 0"], 'u1F46A' => ['tag' => "\ud83d\udc6a", "bp" => "891 0"], 'u1F46B' => ['tag' => "\ud83d\udc6b", "bp" => "918 0"], 'u1F47B' => ['tag' => "\ud83d\udc7b", "bp" => "945 0"], 'u1F47C' => ['tag' => "\ud83d\udc7c", "bp" => "972 0"], 'u1F47D' => ['tag' => "\ud83d\udc7d", "bp" => "999 0"], 'u1F47F' => ['tag' => "\ud83d\udc7f", "bp" => "1026 0"], 'u1F48A' => ['tag' => "\ud83d\udc8a", "bp" => "1080 0"], 'u1F48B' => ['tag' => "\ud83d\udc8b", "bp" => "1107 0"], 'u1F48D' => ['tag' => "\ud83d\udc8d", "bp" => "1134 0"], 'u1F52B' => ['tag' => "\ud83d\udd2b", "bp" => "1485 0"], 'u1F60A' => ['tag' => "\ud83d\ude0a", "bp" => "1782 0"], 'u1F60B' => ['tag' => "\ud83d\ude0b", "bp" => "1809 0"], 'u1F60C' => ['tag' => "\ud83d\ude0c", "bp" => "1836 0"], 'u1F60D' => ['tag' => "\ud83d\ude0d", "bp" => "1863 0"], 'u1F60E' => ['tag' => "\ud83d\ude0e", "bp" => "1890 0"], 'u1F60F' => ['tag' => "\ud83d\ude0f", "bp" => "1917 0"], 'u1F61A' => ['tag' => "\ud83d\ude1a", "bp" => "135 27"], 'u1F61C' => ['tag' => "\ud83d\ude1c", "bp" => "162 27"], 'u1F61D' => ['tag' => "\ud83d\ude1d", "bp" => "189 27"], 'u1F61E' => ['tag' => "\ud83d\ude1e", "bp" => "216 27"], 'u1F61F' => ['tag' => "\ud83d\ude1f", "bp" => "243 27"], 'u1F62A' => ['tag' => "\ud83d\ude2a", "bp" => "432 27"], 'u1F62B' => ['tag' => "\ud83d\ude2b", "bp" => "459 27"], 'u1F62C' => ['tag' => "\ud83d\ude2c", "bp" => "486 27"], 'u1F62D' => ['tag' => "\ud83d\ude2d", "bp" => "513 27"], 'u1F62F' => ['tag' => "\ud83d\ude2f", "bp" => "540 27"], 'u1F64A' => ['tag' => "\ud83d\ude4a", "bp" => "837 27"], 'u1F64F' => ['tag' => "\ud83d\ude4f", "bp" => "864 27"], 'u1F319' => ['tag' => "\ud83c\udf19", "bp" => "54 0"], 'u1F332' => ['tag' => "\ud83c\udf32", "bp" => "81 0"], 'u1F339' => ['tag' => "\ud83c\udf39", "bp" => "108 0"], 'u1F349' => ['tag' => "\ud83c\udf49", "bp" => "162 0"], 'u1F356' => ['tag' => "\ud83c\udf56", "bp" => "189 0"], 'u1F366' => ['tag' => "\ud83c\udf66", "bp" => "243 0"], 'u1F377' => ['tag' => "\ud83c\udf77", "bp" => "297 0"], 'u1F381' => ['tag' => "\ud83c\udf81", "bp" => "351 0"], 'u1F382' => ['tag' => "\ud83c\udf82", "bp" => "378 0"], 'u1F384' => ['tag' => "\ud83c\udf84", "bp" => "405 0"], 'u1F389' => ['tag' => "\ud83c\udf89", "bp" => "432 0"], 'u1F393' => ['tag' => "\ud83c\udf93", "bp" => "459 0"], 'u1F434' => ['tag' => "\ud83d\udc34", "bp" => "648 0"], 'u1F436' => ['tag' => "\ud83d\udc36", "bp" => "675 0"], 'u1F437' => ['tag' => "\ud83d\udc37", "bp" => "702 0"], 'u1F451' => ['tag' => "\ud83d\udc51", "bp" => "864 0"], 'u1F484' => ['tag' => "\ud83d\udc84", "bp" => "1053 0"], 'u1F494' => ['tag' => "\ud83d\udc94", "bp" => "1161 0"], 'u1F525' => ['tag' => "\ud83d\udd25", "bp" => "1458 0"], 'u1F556' => ['tag' => "\ud83d\udd56", "bp" => "1512 0"], 'u1F648' => ['tag' => "\ud83d\ude48", "bp" => "783 27"], 'u1F649' => ['tag' => "\ud83d\ude49", "bp" => "810 27"], 'u1F680' => ['tag' => "\ud83d\ude80", "bp" => "891 27"], 'u2B50' => ['tag' => "\u2b50", "bp" => "1431 27"], 'u23F0' => ['tag' => "\u23f0", "bp" => "972 27"], 'u23F3' => ['tag' => "\u23f3", "bp" => "999 27"], 'u26A1' => ['tag' => "\u26a1", "bp" => "1188 27"], 'u26BD' => ['tag' => "\u26bd", "bp" => "1215 27"], 'u26C4' => ['tag' => "\u26c4", "bp" => "1242 27"], 'u26C5' => ['tag' => "\u26c5", "bp" => "1269 27"], 'u261D' => ['tag' => "\u261d", "bp" => "1134 27"], 'u263A' => ['tag' => "\u263a", "bp" => "1161 27"], 'u270A' => ['tag' => "\u270a", "bp" => "1296 27"], 'u270B' => ['tag' => "\u270b", "bp" => "1323 27"], 'u270C' => ['tag' => "\u270c", "bp" => "1350 27"], 'u270F' => ['tag' => "\u270f", "bp" => "1377 27"], 'u2600' => ['tag' => "\u2600", "bp" => "1026 27"], 'u2601' => ['tag' => "\u2601", "bp" => "1053 27"], 'u2614' => ['tag' => "\u2614", "bp" => "1080 27"], 'u2615' => ['tag' => "\u2615", "bp" => "1107 27"], 'u2744' => ['tag' => "\u2744", "bp" => "1404 27"]];;
		}
		$new_str = preg_replace_callback(
				'/./u',
				function (array $match) {
					global  $ca;
					if(strlen($match[0]) >= 4){
						$ma = json_encode($match[0]);
						foreach ($ca as $c){
							if('"' . $c['tag']  .'"' == $ma){
								$this->name[] = "[tsou" . $c['bp'];
								return;
							}
						}
					}
					if(empty($this->name)){
						$this->name[] = $match[0];
					}else{
						$index = count($this->name) - 1;
						$name = $this->name[$index];
						if(strpos($name, "[tsou") === 0){
							$this->name[] = $match[0];
						}else{
							$this->name[$index] .= $match[0];
						}
					}
					return $match[0];
				},
				$customer['nickname']);
		$avater = $customer['avater'];
		$host = \app\library\SettingHelper::get("bear_image_url");
		if(strpos($avater, "http") !== 0){
			$avater =  $host . $customer['avater'];
		}
		$position = \app\library\SettingHelper::get("bear_qrcode_position",
				[
						'name' => ['x'=> 50,'y' => 24 , 'w' => 23 ,'h' => 50, 'center' => 0],
						'image' => ['x'=> 24,'y' => 24 , 'w' => 18 ,'h' => 97, 'center' => 0],
						'qrcode' => ['x'=> 27,'y' => 39 , 'w' => 50 ,'h' => 100, 'center' => 1],
						'background' => "",
						'number' => '123'
				]);
		if(!isset($position['number'])){
			$position['number'] = "123";
		}
		
		$dir = $_SERVER['DOCUMENT_ROOT'] . "/data/img/";
		if(!is_dir($dir)){
			mkdir($dir, 0777,1);
		}
		
		
		$filename = $dir . token() . ".jpg";
		
		$width = 640;
		
		$height = 1008;
		
		$im = imagecreatetruecolor($width, $height);
		
		if(!empty($position['background'])){
			$background = $_SERVER['DOCUMENT_ROOT'] . "/" . $position['background'];
			$result = true;
			if(!file_exists($background)){
				ini_set('default_socket_timeout', 1);
				$compress = new \app\library\image\ImageCompressHelper();
				$compress->setmin_width(600);
				$result = $compress->image_compress($host . $position['background'], $_SERVER['DOCUMENT_ROOT'] . "/" . $position['background']);
			}
			if($result){
				$background = $_SERVER['DOCUMENT_ROOT'] . "/" . $position['background'];
			}else{
				$background = $host . $position['background'];
			}
			try{
				$content = file_get_contents($background);
				$background = imagecreatefromstring($content);
				imagecopyresized($im, $background, 0, 0, 0, 0, $width,
						$height, imagesx($background), imagesy($background));
			}catch (\Exception $e){
			}
			
		}
		
		$image_x = $position['image']['x'] * 2 ;
		$image_y = $position['image']['y'] * 2;
		$image_w = $position['image']['w'] * 2;
		$image_h = $position['image']['h'] * 2;
		
		
		$qrcode_x = $position['qrcode']['x'] * 2 ;
		$qrcode_y = $position['qrcode']['y'] * 2;
		$qrcode_w = $position['qrcode']['w'] * 2;
		$qrcode_h = $position['qrcode']['h'] * 2;
		
		
		ini_set('default_socket_timeout', 1);
		$source = "";
		try{
			$source = file_get_contents($avater);
		}catch (\Exception $e){
			$avater = $_SERVER['DOCUMENT_ROOT'] . "/public/common/image/default-avater.png";
			$source = file_get_contents($avater);
		}
		$logo = "";
		try{
			$logo = imagecreatefromstring($source);
		}catch(\Exception $e){
			$avater = $_SERVER['DOCUMENT_ROOT'] . "/public/common/image/default-avater.png";
			$source = file_get_contents($avater);
			$logo = imagecreatefromstring($source);
		}
		
		imagecopyresampled($im, $logo, $image_x, $image_y, 0, 0, $image_w,
				$image_h, imagesx($logo), imagesy($logo));
		
		$url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=";
		
		$qrcode = $url . urlencode($customer['ticket']);
		
		$qrcode = imagecreatefromstring(file_get_contents($qrcode));
		
		
		imagecopyresampled($im, $qrcode, $qrcode_x, $qrcode_y, 0, 0, $qrcode_w,
				$qrcode_h, imagesx($qrcode), imagesy($qrcode));
		
		
		
		$name_x = $position['name']['x'] * 2;
		$name_y = $position['name']['y'] * 2;
		
		$emoji = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/public/common/image/emoji.png");
		$emoji = imagecreatefromstring($emoji);
		$col = imagecolorallocate($im,0,0,0);
		
		foreach ($this->name as $name){
			if(strpos($name, "[tsou") === 0){
				$x = substr($name, 5);
				$xy = explode(" ", $x);
				imagecopyresampled($im, $emoji, $name_x, $name_y, $xy[0], $xy[1], 22,
						22, 22, 22);
				$name_x += 22;
			}else{
				
				imagettftext($im, 22, 0, $name_x, $name_y + 22,$col , $_SERVER['DOCUMENT_ROOT'] . "/public/common/font/simsun.ttf", $name);
				$length =   (strlen($name) + mb_strlen($name,'UTF8')) / 2;
				$name_x += 22 * $length / 1.5;
			}
		}
		imagejpeg($im, $filename);
		$res = \app\library\UploadHelper::getInstance()->set_cate("qrcode")->upload_image($filename);
		@unlink($filename);
		if($res['errcode'] == 0){
			M("customer")->where(['customer_id' => $customer['customer_id']])->save(['qrcode_number' => $position['number'],'qrcode' => $res['content']['name']]);
		}
 		imagedestroy($emoji);
 		imagedestroy($qrcode);
 		imagedestroy($logo);
		return $res;
		
		
		
		
	}
	
	public static function generate_customer($customer_id){
		if(empty($customer_id)){
			return ['errcode' => 99, 'message' => '请传入用户信息'];
		}
		$customer = M("customer")
		->where(['customer_id' => $customer_id])
		->field("nickname, avater,uuid")->find();
		if(!empty($customer)){
			$upload = UploadHelper::getInstance();
			$host = \app\library\SettingHelper::get("bear_image_url");
			$qiniu = \app\library\SettingHelper::get("bear_qiniu", ['is_open' => 0]);
			
			$qrcode = \app\library\SettingHelper::get("bear_qrcode",['system_logo' => 0, 'url' => "http://" . get_domain() . "/wap/home/index"]);
			$logo = $_SERVER['DOCUMENT_ROOT']. "/" . $customer['avater'] . $upload->getThumbSuffix(200, 200);
			
			if($qrcode['system_logo'] == 0 || ($qiniu['is_open'] == 0 && !file_exists($logo))){
				$logo = "";
			}
			else if($qiniu['is_open'] == 1){
				$logo = $host . $customer['avater'] .$upload->getThumbSuffix(200, 200);
			}
			
			$url = $qrcode['url'];
			$url = $url . "?uuid=". $customer['uuid'];
			$filename = self::generate($url, $logo);
			
			$result = $upload->set_cate("qrcode")->upload_image($filename);
			@unlink($filename); 
			if($result['errcode'] < 0){
				return $result;
			}
			
			M("customer")->where(['customer_id' => $customer_id])->save(['qrcode' => $result['content']['name']]);
			return ['errcode' => 0 , 'message' => '添加成功'];
		}
		
	}
}