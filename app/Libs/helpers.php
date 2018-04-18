<?php

if (!function_exists('data_get'))
{
    /**
     * 使用“.”表示法从数组或对象获取项目.
     *
     * @param  mixed        $target
     * @param  string|array $key
     * @param  mixed        $default
     *
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key))
        {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (($segment = array_shift($key)) !== null)
        {
            if ($segment === '*')
            {
                if ($target instanceof Collection)
                {
                    $target = $target->all();
                } elseif (!is_array($target))
                {
                    return value($default);
                }

                $result = Arr::pluck($target, $key);

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (Arr::accessible($target) && Arr::exists($target, $segment))
            {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment}))
            {
                $target = $target->{$segment};
            } else
            {
                return value($default);
            }
        }
        return $target;
    }
}

if (!function_exists('value'))
{
    /**
     * 返回给定值的默认值.
     *
     * @param  mixed $value
     *
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('last'))
{
    /**
     * 从数组中获取最后一个元素.
     *
     * @param  array $array
     *
     * @return mixed
     */
    function last($array)
    {
        return end($array);
    }
}

if (!function_exists('class_basename'))
{
    /**
     * 获取给定对象/类的类“basename”.
     *
     * @param  string|object $class
     *
     * @return string
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('trait_uses_recursive'))
{
    /**
     * 返回特征及其特征所使用的所有特征.
     *
     * @param  string $trait
     *
     * @return array
     */
    function trait_uses_recursive($trait)
    {
        $traits = class_uses($trait);

        foreach ($traits as $trait)
        {
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}

if (!function_exists('class_uses_recursive'))
{
    /**
     * 返回一个类使用的所有traits，它的子类和trait的traits.
     *
     * @param  string $class
     *
     * @return array
     */
    function class_uses_recursive($class)
    {
        $results = [];

        foreach (array_merge([$class => $class], class_parents($class)) as $class)
        {
            $results += trait_uses_recursive($class);
        }

        return array_unique($results);
    }
}

if (!function_exists('with'))
{
    /**
     * 返回给定的对象,用于链接.
     *
     * @param  mixed $object
     *
     * @return mixed
     */
    function with($object)
    {
        return $object;
    }
}

if (!function_exists('array_only'))
{
    /**
     * Get a subset of the items from the given array.
     *
     * @param  array        $array
     * @param  array|string $keys
     *
     * @return array
     */
    function array_only($array, $keys)
    {
        return Arr::only($array, $keys);
    }
}

if (!function_exists('getip'))
{
    /**
     * 获取客户ip
     * @return string
     *             返回IP地址
     *             如果未获取到返回unknown
     */
    function getip()
    {
        static $onlineip = '';
        if ($onlineip) {
            return $onlineip;
        }
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $onlineip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $onlineip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $onlineip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $onlineip = $_SERVER['REMOTE_ADDR'];
        }
        preg_match('/[\d\.]{7,15}/', $onlineip, $onlineipmatches);
        $onlineip = $onlineipmatches[0] ? $onlineipmatches[0] : 'unknown';
        return $onlineip;
    }
}

if (!function_exists('event'))
{
    function event($event, $payload = [], $halt = false)
    {
        return \App\Base\EventProvider::fire($event, $payload, $halt);
    }
}


if (! function_exists('collect')) {
    /**
     * Create a collection from the given value.
     *
     * @param  mixed  $value
     */
    function collect($value = null)
    {
        return new Collection($value);
    }
}

if (! function_exists('dd')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed
     * @return void
     */
    function dd()
    {
        array_map(function ($x) {
            var_dump($x);
        }, func_get_args());

        die(1);
    }
}

if (! function_exists('distance')) {
    /**
     *求两个已知经纬度之间的距离,单位为米
     *@param lng1,lng2 经度
     *@param lat1,lat2 纬度
     *@return float 距离，单位米
     *@author www.phpernote.com
     **/
    function distance($lat1, $lng1, $lat2, $lng2){
        //将角度转为狐度
        $radLat1=deg2rad($lat1);//deg2rad()函数将角度转换为弧度
        $radLat2=deg2rad($lat2);
        $radLng1=deg2rad($lng1);
        $radLng2=deg2rad($lng2);
        $a=$radLat1-$radLat2;
        $b=$radLng1-$radLng2;
        $s=2*asin(sqrt(pow(sin($a/2),2)+cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)))*6378.137*1000;
        return (intval(($s)) * 100) / 100;
    }
}


if (!function_exists('config'))
{
    /**
     * 返回给定值的默认值.
     *
     * @param  mixed $value
     *
     * @return mixed
     */
    function config($key)
    {
        $path = CONFIGPATH . ENV . '/' . $key . '.php';
        if (is_file($path) && is_readable($path)) {
            return require($path);
        }
        return [];
    }
}


if (!function_exists('setenv'))
{
    /**
     * get env config.
     * @param string $key config key
     *
     * @return mixed
     */
    function setenv()
    {
        $path = WEBPATH .  '/' . '.env';
        if (is_file($path) && is_readable($path)) {
            $file = file_get_contents($path);
            preg_match_all('/[A-Z|_]+=[\w\.-_]+/', $file, $column);
            foreach ($column[0] as $enval) {
                putenv($enval);
            }
        }
        return [];
    }
}


if (!function_exists('env'))
{
    /**
     * get env config.
     * @param string $key config key
     *
     * @return mixed
     */
    function env($key)
    {
        return getenv($key);
    }
}
