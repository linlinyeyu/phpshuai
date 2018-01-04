<?php 
namespace fake;
abstract class Validate{
/**
 * 验证方法
 * @var unknown
 */
	protected static $rule = [
        'require'  => '/\S+/',
        'email'    => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
        'url'      => '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(:\d+)?(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
        'currency' => '/^\d+(\.\d+)?$/',
        'number'   => '/^\d+$/',
        'zip'      => '/^\d{6}$/',
        'integer'  => '/^[-\+]?\d+$/',
        'double'   => '/^[-\+]?\d+(\.\d+)?$/',
        'english'  => '/^[A-Za-z]+$/',
    ];

	protected $rules = [];

	protected $message = [];

	protected $errors = [];




	public function check(&$data){
		$errors = [];
		foreach ($this->rules as $key => $val) {
			if (is_numeric($key) && is_array($val)) {
                $key = array_shift($val);
            }

            $value = $this->getDataValue($data,$key);
            $this->checkItem($value,$val, $data);

		}

	}

/**
     * 验证字段规则
     * @access protected
     * @param mixed $value  字段值
     * @param mixed $val  验证规则
     * @param array $data  数据
     * @return string|true
     */
    protected function checkItem($value, $val, &$data)
    {
        $rule    = $val[0];
        $msg     = isset($val[1]) ? $val[1] : '';
        $type    = isset($val[2]) ? $val[2] : 'regex';
        $options = isset($val[3]) ? (array) $val[3] : [];
        if ($rule instanceof \Closure) {
            // 匿名函数验证 支持传入当前字段和所有字段两个数据
            $result = self::callback($value, $rule, $data, $options);
        } else {
            switch ($type) {
                case 'callback':
                    $result = $this->callback($value, $rule, $data, $options);
                    break;
                case 'behavior':
                    // 行为验证
                    $result = $this->behavior($rule, $data);
                    break;
                case 'filter': // 使用filter_var验证
                    $result = $this->filter($value, $rule, $options);
                    break;
                case 'confirm':
                    $result = $this->confirm($value, $rule, $data);
                    break;
                case 'in':
                    $result = self::in($value, $rule);
                    break;
                case 'notin':
                    $result = self::notin($value, $rule);
                    break;
                case 'between': // 验证是否在某个范围
                    $result = self::between($value, $rule);
                    break;
                case 'notbetween': // 验证是否不在某个范围
                    $result = self::notbetween($value, $rule);
                    break;
                case 'expire':
                    $result = self::expire($value, $rule);
                    break;
                case 'length':
                    $result = self::length($value, $rule);
                    break;
                case 'allow_ip':
                    $result = self::allowIp($value, $rule);
                    break;
                case 'deny_ip':
                    $result = self::denyIp($value, $rule);
                    break;
                case 'regex':
                default:
                    $result = self::regex($value, $rule);
                    break;
            }
        }
        // 验证失败返回错误信息
        return (false !== $result) ? $result : $msg;
    }


    /**
     * 验证是否和某个字段的值一致
     * @access public
     * @param mixed $value  字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool
     */
    public static function confirm($value, $rule, $data)
    {
        return self::getDataValue($data, $rule) == $value;
    }

    /**
     * 使用callback方式验证
     * @access public
     * @param mixed $value  字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @param array $params  参数
     * @return mixed
     */
    public static function callback($value, $rule, &$data, $params = [])
    {
        if ($rule instanceof \Closure) {
            return call_user_func_array($rule, [$value, &$data]);
        }
        array_unshift($params, $value);
        return call_user_func_array($rule, $params);
    }

    /**
     * 使用行为类验证
     * @access public
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return mixed
     */
    public static function behavior($rule, $data)
    {
        return Hook::exec($rule, '', $data);
    }

    /**
     * 使用filter_var方式验证
     * @access public
     * @param mixed $value  字段值
     * @param mixed $rule  验证规则
     * @param array $params  参数
     * @return bool
     */
    public static function filter($value, $rule, $params = [])
    {
        return false !== filter_var($value, is_int($rule) ? $rule : filter_id($rule), $params);
    }

    /**
     * 验证是否在范围内
     * @access public
     * @param mixed $value  字段值
     * @param mixed $rule  验证规则
     * @return bool
     */
    public static function in($value, $rule)
    {
        return in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * 验证是否不在某个范围
     * @access public
     * @param mixed $value  字段值
     * @param mixed $rule  验证规则
     * @return bool
     */
    public static function notin($value, $rule)
    {
        return !in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * between验证数据
     * @access public
     * @param mixed $value  字段值
     * @param mixed $rule  验证规则
     * @return mixed
     */
    public static function between($value, $rule)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        list($min, $max) = $rule;
        return $value >= $min && $value <= $max;
    }

    /**
     * 使用notbetween验证数据
     * @access public
     * @param mixed $value  字段值
     * @param mixed $rule  验证规则
     * @return mixed
     */
    public static function notbetween($value, $rule)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        list($min, $max) = $rule;
        return $value < $min || $value > $max;
    }

    /**
     * 验证数据长度
     * @access public
     * @param mixed $value  字段值
     * @param mixed $rule  验证规则
     * @return mixed
     */
    public static function length($value, $rule)
    {
        $length = mb_strlen((string) $value, 'utf-8'); // 当前数据长度
        if (strpos($rule, ',')) {
            // 长度区间
            list($min, $max) = explode(',', $rule);
            return $length >= $min && $length <= $max;
        } else {
            // 指定长度
            return $length == $rule;
        }
    }

    /**
     * 验证有效期
     * @access public
     * @param mixed $value  字段值
     * @param mixed $rule  验证规则
     * @return mixed
     */
    public static function expire($value, $rule)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        list($start, $end) = $rule;
        if (!is_numeric($start)) {
            $start = strtotime($start);
        }

        if (!is_numeric($end)) {
            $end = strtotime($end);
        }
        return NOW_TIME >= $start && NOW_TIME <= $end;
    }

    /**
     * 验证IP许可
     * @access public
     * @param string $value  字段值
     * @param mixed $rule  验证规则
     * @return mixed
     */
    public static function allowIp($value, $rule)
    {
        return in_array($_SERVER['REMOTE_ADDR'], is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * 验证IP禁用
     * @access public
     * @param string $value  字段值
     * @param mixed $rule  验证规则
     * @return mixed
     */
    public static function denyIp($value, $rule)
    {
        return !in_array($_SERVER['REMOTE_ADDR'], is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * 使用正则验证数据
     * @access public
     * @param mixed $value  字段值
     * @param mixed $rule  验证规则
     * @return mixed
     */
    public static function regex($value, $rule)
    {
        if (isset(self::$rule[$rule])) {
            $rule = self::$rule[$rule];
        }
        if (!(0 === strpos($rule, '/') && preg_match('/\/[imsU]{0,4}$/', $rule))) {
            // 不是正则表达式则两端补上/
            $rule = '/^' . $rule . '$/';
        }
        return 1 === preg_match($rule, (string) $value);
    }

	public function getError(){

	}

	protected function getDataValue($data, $key)
    {
        if (strpos($key, '.')) {
            // 支持二维数组验证
            list($name1, $name2) = explode('.', $key);
            $value               = isset($data[$name1][$name2]) ? $data[$name1][$name2] : null;
        } else {
            $value = isset($data[$key]) ? $data[$key] : null;
        }
        return $value;
    }

}