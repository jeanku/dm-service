<?php
// Autoload 自动载入
require '../vendor/autoload.php';


define('WEBPATH', dirname(__DIR__));
define('ENV', 'dev');
define('CONFIGPATH',  WEBPATH . '/config/');

\Jeanku\Database\DatabaseManager::make(WEBPATH . '/config/'. ENV . '/database.php');

// 路由配置
//require '../config/routes.php';

$request = file_get_contents('php://input');
$param = json_decode($request, true);


if (is_array($param)) {
    responseJson(callFunction($param['controller'], $param['function'], $param['param']));
} else {
    exit('参数请求参数格式有误');
}


function callFunction($controller, $function, $param)
{
    $class = '\App\Controllers\\' . $controller;
    $instance = new $class();                                                       //实例化controller对象
    return call_user_func_array(array($instance, $function), $param);
}


/**
 * json输出
 * @date 2017-03-21
 * @param array $data option 需要输出的数组
 * @return array
 */
function responseJson($data)
{
    header('Content-type:text/json');
    exit(json_encode($data));
}
