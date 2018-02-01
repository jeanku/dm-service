<?php
namespace App\Util;


class Response {

    /**
     * return success
     * @date 2017-04-18
     * @param array $data required 数组信息
     * @return array
     */
    public static function success($data)
    {
        return ['code' => 0, 'msg' => 'ok', 'data' => $data];
    }


    /**
     * 错误数组返回
     * @date 2017-04-18
     * @param string $code required 错误code
     * @param string $msg required 错误message
     * @param any|bool $data option data数据
     * @return array
     */
    public static function error($code, $msg, $data = null)
    {
        return ['code' => $code, 'msg' => $msg, 'data' => $data];
    }
}