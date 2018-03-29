<?php
define('WEBPATH', dirname(dirname(__DIR__)));

require WEBPATH . '/vendor/autoload.php';

error_reporting(E_ALL);                     //设置错误级别
define('ENV', 'dev');

$config = [
    'Controller' => WEBPATH . '/app/Controllers/',
    'Module'    => WEBPATH . '/app/Modules/',
    'Model'     => WEBPATH . '/app/Models/',
];



\Jeanku\Database\DatabaseManager::make(WEBPATH . '/config/'. ENV . '/database.php');
//$model = new \Jeanku\Database\DatabaseManager();
//$databases = $model->select('show create table t_manage_menu');
//echo "<pre>";
//print_r($databases);
//exit;


$request = file_get_contents('php://input');

if ($argv[1] == 'help') {
    echo 'make controller: ' . 'php artisan.php controller::Test' . PHP_EOL;
    echo 'make module: ' . 'php artisan.php module::test' . PHP_EOL;
    echo 'make model: ' . 'php artisan.php model::test' . PHP_EOL;
    exit;
}

$arg = explode('::', $argv[1]);
$type = ucfirst(strtolower($arg[0]));
$name = ucfirst(strtolower($arg[1]));
$option = isset($argv[2]) ? str_split($argv[2]) : [];
switch ($type) {
    case 'Controller':
        $phpFile = $config[$type] . $name . 'Controller.php';
        if (file_exists($phpFile)) {
            if (!in_array('r', $option)) {
                echo 'file already exist';
                exit;
            } else {
                file_put_contents($phpFile, "");                        //文件重写 先清空
            }
        }
        $templatePath = './template/Controller.txt';
        $file = fopen($templatePath, "r");
        while(!feof($file))
        {
            $line = fgets($file);                                       //fgets()函数从文件指针中读取一行
            $line = preg_replace('/{{date}}/', date('Y-m-d'), $line);
            $line = preg_replace('/{{key}}/', $name, $line);
            file_put_contents($phpFile, $line, FILE_APPEND);            //文件追加
        }
        fclose($file);
        echo 'success, file path:' . $phpFile;
        break;
    case 'Module':
        $phpFile = $config[$type] . $name . '.php';
        if (file_exists($phpFile)) {
            if (!in_array('r', $option)) {
                echo 'file already exist';
                exit;
            } else {
                file_put_contents($phpFile, "");                        //文件重写 先清空
            }
        }
        $templatePath = './template/Module.txt';
        $file = fopen($templatePath, "r");
        while(!feof($file))
        {
            $line = fgets($file);                                       //fgets()函数从文件指针中读取一行
            $line = preg_replace('/{{date}}/', date('Y-m-d'), $line);
            $line = preg_replace('/{{key}}/', $name, $line);
            file_put_contents($phpFile, $line, FILE_APPEND);            //文件追加
        }
        fclose($file);
        echo 'success, file path:' . $phpFile;
        break;
        break;
    case 'Model':
        if (empty($argv[3])) {
            echo 'table name not exist' . PHP_EOL;
            echo 'php artisan.php model::{modelname} -t {tablename}' . PHP_EOL;
            exit;
        }
        $model = new \Jeanku\Database\DatabaseManager();
        $databases = $model->select('show create table ' . $argv[3]);
        preg_match_all('/`(\w+)` \w+/', $databases[0]->{'Create Table'}, $column);
        $column = $column[1];
        $phpFile = $config[$type] . $name . 'Model.php';
        if (file_exists($phpFile)) {
            if (!in_array('r', $option)) {
                echo 'file already exist';
                exit;
            } else {
                file_put_contents($phpFile, "");                                //文件重写 先清空
            }
        }
        $templatePath = './template/Model.txt';
        $file = fopen($templatePath, "r");
        while(!feof($file))
        {

            $line = fgets($file);                                               //fgets()函数从文件指针中读取一行
            if (strpos($line, '{{column}}') > -1) {
               foreach ($column as $val) {
                   $temp = "        '$val'," . PHP_EOL;
                   file_put_contents($phpFile, $temp, FILE_APPEND);            //文件追加
               }
            } else {
                $line = preg_replace('/{{table}}/',$argv[3] , $line);
                $line = preg_replace('/{{key}}/', $name, $line);
                file_put_contents($phpFile, $line, FILE_APPEND);                //文件追加
            }

        }
        fclose($file);
        echo 'success, file path:' . $phpFile;
        break;
}

