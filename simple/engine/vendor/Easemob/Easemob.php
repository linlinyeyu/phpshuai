<?PHP
/*
*
* 环信
*/
class Easemob
{

	private static $config = [
		'org_name'  			=> '',
		'app_name'			=> '',
		'client_id'       	=> '',
		'client_secret'   	=> '',
		'grant_type'			=> 'client_credentials',
	];
	protected static $api = 'https://a1.easemob.com/';
	
	public function __construct($config)
	{
		if ($config) 
			self::$config = array_merge(self::$config,$config);
	}

	public function GetToken()
	{
		$url = $this->url('token');
		$params = json_encode(self::$config);
		$header = [
		'Content-Type: application/json',
		];
		return $this->http($url,$params,'POST',$header,true);
	}

	public function AddUser($token,$data)
	{
		$url = $this->url('users');
		$params = json_encode($data);
		$header = [
		'Content-Type: application/json',
		'Authorization: Bearer '.$token,
		];
		return $this->http($url,$params,'POST',$header,true);		
	}

	protected function url($method)
	{
		return self::$api . self::$config['org_name'] .'/'. self::$config['app_name'] .'/'.$method;
	}

    /**
     * 发送HTTP请求方法，目前只支持CURL发送请求
     * @param  string $url    请求URL
     * @param  array  $params 请求参数
     * @param  string $method 请求方法GET/POST
     * @return array  $data   响应数据
     */
    protected function http($url, $params, $method = 'GET', $header = [], $multi = false)
    {
        $opts = [
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => $header,
        ];

        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                //判断是否传输文件
                $params                   = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL]        = $url;
                $opts[CURLOPT_POST]       = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                throw new \Exception('不支持的请求方式！');
        }

        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \Exception('请求发生错误：' . $error);
        }

        return $data;
    }
}