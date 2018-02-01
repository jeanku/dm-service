<?php namespace App\Util;

/**
 * 接口入参的校验类
 * @desc request data validate
 * @package \User
 * @author GaoJian
 * @date 2016-03-07
 */
class Validate
{


    /**
     * 校验的正则
     * @author gaojian291
     * @date 2017-03-23
     */
    private static $aRegex = [                                          //支持参数的校验正则
        'int' => '/^-?[1-9]?[0-9]+$/',                        //有符号的整数 (整形|字符串)
        'uint' => '/^[1-9]?[0-9]+$/',                          //无符号的整数 (整形|字符串)
        'url' => '/^https?:\/\/([a-z0-9-]+\.)+[a-z0-9]{2,4}.*$/',
        'email' => '/^[a-z0-9_+.-]+\@([a-z0-9-]+\.)+[a-z0-9]{2,4}$/i',
        'idcard' => '/^[0-9]{15}$|^[0-9]{17}[a-zA-Z0-9]/',
        'money' => '/^\d+(\.\d{1,2})?$/',
        'mobile' => '/^((1[3-9][0-9])|200)[0-9]{8}$/',               //手机号
        'phone' => '/^(\d{3,4}-?)?\d{7,8}$/',                       //座机号
        'chinese' => '/^[\x{4e00}-\x{9fa5}]+$/u',                     //中文
        'postcode' => '/^[1-9]\d{5}$/',                                //邮编
        'alphabet' => '/^[A-Za-z]+$/',                                 //字母
        'ip' => '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/',                     //IP
        'mac' => '/^[A-Z0-9]{2}(-[A-Z0-9]{2}){5}$/',              //MAC物理地址
        'json' => '/^\{([a-zA-Z\"\']{2,15}(\s)*\:[^\r\n]{0,200}(,)?(\s)*)*\}$/',  //json
        'date' => '/\d{4}-1[0-2]|0?[1-9]-0?[1-9]|[12][0-9]|3[01]/', //日期 2016-04-01
        'numberic' => 'isNumberic',                                    //纯数字字符串
        'string' => 'isString',
        'integer' => 'isInteger',                                     //必须是整形
        'float' => '/^\d+(\.\d+)?$/',                                 //浮点型
        'array' => 'isArray'
    ];


    /**
     * 参数规则
     * @author gaojian291
     * @date 2017-03-23
     */
    private static $rules = [
        'require' => ['require', 'sometime'],
        'type' => '',
        'range' => ['min', 'max', 'in', 'between', 'length'],
    ];


    /**
     * 参数校验的入口方法
     * @author gaojian291
     * @date 2017-03-23
     * @param array $field required 校验规则
     * @param array $param required 参数数组
     * @param array $message required 参数数组
     * @return ValidateResult
     */
    public static function check($field, $param, $message = [])
    {
        $model = new ValidateResult();
        try {
            $returnData = [];
            if (!is_array($field)) {
                throw new \Exception('error:.parameter.field.Error');
            }
            if (!is_array($param)) {
                throw new \Exception('error:.parameter.data.Error');
            }
            foreach ($field as $key => $val) {
                $rules = self::formatRules($key, $val);
                if (is_bool($rules)) {
                    $returnData[$key . '.rules'] = "$key.rules.string required";
                    continue;
                }
                $field[$key] = $rules;
                if (isset($param[$key])) {
                    $field[$key]['value'] = $param[$key];
                }
            }
            $errorMsg = self::handle($field);
            $combineInfo = array_intersect_key($message, $errorMsg);
            $errorMsg = array_merge($errorMsg, $combineInfo);
            if ($errorMsg) {
                $model->setErrorMsg($errorMsg);
            }
        } catch (\Exception $e) {
            $model->setErrorMsg([$e->getMessage()]);
        }
        return $model;
    }


    /**
     * 生成对应的格式化数据
     * @author gaojian291
     * @date 2017-03-23
     * @param string $key required 参数的key
     * @param string $ruleStr required 校验的规则(string)
     * @return array
     */
    public static function formatRules($key, $ruleStr)
    {
        $returnData = [
            'require' => null,
            'type' => null,
            'range' => null,
        ];
        if (is_string($ruleStr)) {
            $data = explode('|', $ruleStr);
            $reqrArray = self::$rules['require'];
            $typeArray = array_keys(self::$aRegex);
            $rangeArray = self::$rules['range'];
            foreach ($data as $val) {
                if (in_array($val, $reqrArray)) {
                    $returnData['require'] = $val === 'require';
                } elseif (in_array($val, $typeArray)) {
                    $returnData['type'] = $val;
                } elseif (strstr($val, ':') >= 0) {
                    list($range, $value) = explode(':', $val);
                    if (in_array($range, $rangeArray) && preg_match_all('/[0-9]+/', $value, $arr)) {
                        $returnData['range'][$range] = array_values(isset($arr[0]) ? $arr[0] : []);
                    }
                }
            }
            return $returnData;
        } else {
            return false;
        }
    }


    /**
     * 数据校验处理类
     * @author gaojian291
     * @date 2017-03-23
     * @param array $data required formatRules() 处理好的数据
     * @return array
     */
    public static function handle($data)
    {
        $returnData = [];
        foreach ($data as $key => $val) {
            if ($val['require'] && !isset($val['value'])) {                       //处理require
                $returnData[$key . '.require'] = self::formatErrMsg('require', $key);
                continue;
            }
            if (!isset($val['value'])) {
                continue;
            }
            $regex = self::$aRegex[$val['type']];
            if (method_exists(__CLASS__, $regex)) {                             //处理type
                if (!self::$regex($val['value'])) {
                    $returnData[$key . '.' . $val['type']] = self::formatErrMsg('type', $key, $val['type']);
                    continue;
                }
            } else {
                if (!preg_match($regex, $val['value'])) {
                    $returnData[$key . '.' . $val['type']] = self::formatErrMsg('type', $key, $val['type']);
                    continue;
                }
            }
            if ($val['range']) {                                                //处理range
                $flag = self::dealRange($val['range'], $val['value']);
                if (!is_bool($flag)) {
                    $returnData[$key . '.' . $flag] = self::formatErrMsg('range', $key, $flag, $val['range']);
                    continue;
                }
            }
        }
        return $returnData;
    }


    /**
     * check value is String
     * @param string $value required
     * @return boolean
     */
    public static function isString($value)
    {
        return is_string($value);
    }


    /**
     * check value is numberic
     * @param string $value required
     * @return boolean
     */
    public static function isNumberic($value)
    {
        return is_numeric($value);
    }


    /**
     * check value is integer
     * @param string $value required
     * @return boolean
     */
    public static function isInteger($value)
    {
        return is_integer($value);
    }

    /**
     * check value is float
     * @param string $value required
     * @return boolean
     */
    public static function isFloat($value)
    {
        return is_float($value);
    }


    /**
     * check value is array
     * @param string $value required
     * @return boolean
     */
    public static function isArray($value)
    {
        return is_array($value);
    }


    /**
     * Enter description here...
     * @author gaojian291
     * @date 2017-03-23
     * @param array $range required range数组
     * @param string $value required 校验的参数值
     * @return boolean|range key
     */
    public static function dealRange($range, $value)
    {

        foreach ($range as $key => $val) {
            $valid = true;
            switch ($key) {
                case 'length' :
                    $length = strlen($value);
                    $valid = $length >= (int)$val[0];
                    if (isset($val[1])) {
                        $valid = $valid && $length <= $val[1];
                    }
                    break;
                case 'min' :
                    $valid = $value >= (int)$val[0];
                    break;
                case 'max' :
                    $valid = $value <= (int)$val[0];
                    break;
                case 'between' :
                    $valid = $value >= (int)$val[0];
                    if (isset($val[1])) {
                        $valid = $valid && $value <= $val[1];
                    }
                    break;
                case 'in' :
                    $valid = in_array($value, $val, false);
                    break;
            }
            if (!$valid) {
                return $key;
            }
        }
        return true;
    }


    /**
     * 处理自定义的异常信息
     * @return null
     */
    public static function formatErrMsg($paramType, $key, $keyType = '')
    {
        $returnMsg = '';
        switch ($paramType) {
            case 'require':
                $returnMsg = sprintf("参数%s不能为空", $key);
                break;
            case 'type':
                $returnMsg = sprintf("参数%s不是正确的%s类型参数", $key, $keyType);
                break;
            case 'range':
                $returnMsg = sprintf("参数%s值不符合%s要求", $key, $keyType);
                break;
            default :
                break;
        }
        return $returnMsg;
    }
}


/**
 * 处理返回的数据类
 * @desc more description
 * @package \User
 * @author gaojian291
 * @date 2017-03-23
 */
class ValidateResult
{

    private $errorFlag = false;

    private $errorMsg = [];

    public function fail()
    {
        return $this->errorFlag;
    }

    public function setErrorMsg($msg)
    {
        $this->errorFlag = true;
        $this->errorMsg = $msg;
    }

    public function getErrorMsg()
    {
        return $this->errorMsg;
    }
}