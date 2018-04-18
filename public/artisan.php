<?php
// Autoload 自动载入
require '../vendor/autoload.php';
require '../app/Libs/helpers.php';

error_reporting(E_ALL);                                           //设置错误级别

define('WEBPATH', dirname(__DIR__));
define('ENV', 'dev');
define('CONFIGPATH',  WEBPATH . '/config/');
setenv();

$class = 'App\Services\\' . $argv[1];
$instance = new $class();                                        //实例化controller对象
return call_user_func_array(array($instance, 'run'), []);