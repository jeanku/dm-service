<?php
// Autoload 自动载入
require '../vendor/autoload.php';

//error_reporting(E_ALL & ~E_NOTICE);                     //设置错误级别


define('WEBPATH', dirname(__DIR__));
define('ENV', 'dev');
define('CONFIGPATH',  WEBPATH . '/config/');

//\Jeanku\Database\DatabaseManager::make(WEBPATH . '/config/'. ENV . '/database.php');

// 路由配置
//require '../config/routes.php';



//处理fatal error
register_shutdown_function("error_handler");
function error_handler(){
    $error = error_get_last();
    if($error && ($error["type"]===($error["type"] & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_PARSE)))) {
        responseJson(['code'=>500, 'msg'=>'服务器错误', 'data'=>null]);
    }
}

//run mvc
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
