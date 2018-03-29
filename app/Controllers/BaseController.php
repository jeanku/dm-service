<?php
namespace App\Controllers;

use App\Util\Response;
use App\Util\Validate;

/**
* BaseController
*/
class BaseController
{


    /**
     * 参数校验类
     * @date   2017-03-23
     * @param array $field required 校验规则
     * @param array $params required 校验的参数
     * @param boolean $filter option 是否需要过滤掉多余的参数
     * @return array
     * @throws \Exception
     */
    public static function validate($field, $params, $filter = true)
    {
        $model = Validate::check($field, $params);
        if ($model->fail()) {
            try {
                $error = $model->getErrorMsg();
                $msg = reset($error);
            } catch (\Exception $e) {
                $msg = '参数错误';
            }
            throw new \Exception($msg, 999998);
        }
        if($filter){                                                            //是否过滤除了校验规则之外的数据
            $params = array_intersect_key($params, $field);
        }
        return array_map(function($val){                                        //转义特殊字符串
            return is_string($val)?stripslashes($val):$val;
        }, $params);
    }


    /**
     * return success
     * @date 2017-04-18
     * @param array $data required 数组信息
     * @return array
     */
    public static function success($data)
    {
        return Response::success($data);
    }

    /**
     * 错误数组返回
     * @date 2017-04-18
     * @param string $code required 错误code
     * @param string $msg required 错误message
     * @param any|bool $data option data数据
     * @return array
     */
    public function error($code, $msg, $data = false)
    {
        return Response::error($code, $msg, $data);
    }

}