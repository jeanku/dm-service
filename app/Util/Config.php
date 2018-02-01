<?php namespace App\Util;

/**
 * Config类
 * @desc curl Class support post&get
 * @date 2016-05-18
 */
class Config
{

    protected static $config = [];

    /**
     * get config
     * @date 2018-01-31
     * @param string $name require config name
     * @return array
     */
    public static function get($name)
    {
        if (!isset(self::$config[$name])) {
            $path = CONFIGPATH . ENV . '/' . $name . '.php';
            if (is_file($path) && is_readable($path)) {
                self::$config[$name] = require($path);
            }
        }
        return isset(self::$config[$name]) ? self::$config[$name] : [];
    }

}
