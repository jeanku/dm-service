<?php
namespace App\Util;


/**
 * 接口限速器
 * @desc more description
 * @package \SpeedLimiter
 * @date 2017-06-06
 */
class SpeedLimiter
{

    public $redis = null;


    /**
     * redis对象初始化
     * @date 2017-06-06
     * @param Object|Redis $redis required redis对象
     * @return SpeedLimiter
     */
    public static function init($redis)
    {
        $model = new self;
        $model->redis = $redis;
        return $model;
    }


    /**
     * 接口限速器
     * @date 2017-06-06
     * @param string $key required redis key
     * @param int $time required 单位时间 单位：秒
     * @param int $count required 单位时间调用次数上限
     * @return array
     * @throws \Supercoach\Exceptions\ServiceException
     * @demo \App\Util\SpeedLimiter::init($redis)->check($key, 300, 10)
     */
    public function check($key, $time, $count)
    {
        $redis = $this->redis;
        $value = $redis->get($key);
        if ($value) {
            if ((int)$value >= (int)$count) {
                throw new \Supercoach\Exceptions\ServiceException('REQUEST_TOO_FREQUENT_EXCEPTION');
            } else {
                $redis->incr($key);
            }
        } else {
            $redis->set($key, 1);
            $redis->expire($key, $time);
        }
        return true;
    }

}