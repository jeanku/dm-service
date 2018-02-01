<?php namespace App\Exceptions;


/**
 * 业务异常处理类
 * Class ServiceException
 * @package App\Exception
 */
class ServiceException extends \Exception
{
    public static $aExceptions = NULL;

    /**
     * 异常构造类
     * @param string $sExceptionKey required 异常KEY或者异常信息
     * @param string $sExceptionCode option 异常code ($sExceptionKey为异常信息时才有该字段)
     * @return null
     */
    public function __construct($sExceptionKey,$sExceptionCode = '-1')
    {
        self::setExceptions();
        if (isset(self::$aExceptions[$sExceptionKey])){
            list($message, $code) = self::$aExceptions[$sExceptionKey];
            if(isset($sExceptionCode) && !empty($sExceptionCode)){
                parent::__construct($sExceptionCode, $code);
            }
            parent::__construct($message, $code);
        }else{
            parent::__construct($sExceptionKey, $sExceptionCode);
        }
    }

    /**
     * 读取异常文件
     * @throws Exception
     */
    public static function setExceptions()
    {
        $sFilename = dirname(__FILE__) . '/ExceptionCode.php';
        if (is_readable($sFilename)){
            self::$aExceptions = require($sFilename);
        }else{
            throw new parent('读取异常文件出错', 999999);
        }
    }

}