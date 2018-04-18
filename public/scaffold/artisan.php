<?php

$config = require './config/config.php';
help($argv);

$author = $config['author'] ? : '';
$arg = explode('::', $argv[1]);
$type = ucfirst(strtolower($arg[0]));
$name = ucfirst(strtolower($arg[1]));
$option = isset($argv[2]) ? str_split($argv[2]) : [];
$tableName = isset($argv[3]) ? $argv[3] : '';

switch ($type) {
    //生成controller
    case 'Controller':
        $phpFile = $config[$type] . $name . 'Controller.php';                   //文件路径
        rewrite($phpFile, $option);                                             //是否重写
        $tableSql = getSql($argv[3]);
        preg_match_all('/`(\w+)` (\w+)\((\d+)\)(.*COMMENT [\'"](.+)[\'"])?/', $tableSql, $column);
        $keys = $column[1];                                                     //表字段
        $type = $column[2];                                                     //字段类型
        foreach ($type as $tkey => $val) {
            $type[$tkey] = strpos($val,'int') !== false ? 'int' : 'string';     //字段类型处理 目前非int则string
        }
        $length = $column[3];                                                   //字段长度
        $comment = $column[5];                                                  //字段说明
        $file = fopen('./template/Controller.txt', "r");
        while(!feof($file))
        {
            $line = fgets($file);                                               //fgets()函数从文件指针中读取一行
            if (strpos($line, '{{validate}}') > -1) {
                foreach ($keys as $key => $val) {                               //处理校验规则
                    $lengthMsg = ($type[$key] == 'string' ? sprintf('length:[0,%s]', $length[$key]) : "min:0");
                    $temp = str_pad("                '$val'=>'sometime|$type[$key]|$lengthMsg',", 100);
                    if (!empty($comment[$key])) {                               //添加字段注释
                        $temp = $temp . '//' . $comment[$key] . PHP_EOL;
                    } else {
                        $temp = $temp . PHP_EOL;
                    }
                    file_put_contents($phpFile, $temp, FILE_APPEND);            //写文件
                }
            } else {
                $line = preg_replace('/{{date}}/', date('Y-m-d'), $line);       //日期处理
                $line = preg_replace('/{{key}}/', $name, $line);                //业务模块名称处理
                $line = preg_replace('/{{author}}/', $author, $line);           //作者信息
                file_put_contents($phpFile, $line, FILE_APPEND);                //写文件
            }
        }
        fclose($file);                                                          //关闭文件
        echo 'success, file path:' . $phpFile;                                  //成功信息输出
        break;
    //生成module
    case 'Module':
        $phpFile = $config[$type] . $name . '.php';
        rewrite($phpFile, $option);                                             //是否重写
        $file = fopen('./template/Module.txt', "r");
        while(!feof($file))
        {
            $line = fgets($file);                                               //fgets()函数从文件指针中读取一行
            $line = preg_replace('/{{date}}/', date('Y-m-d'), $line);
            $line = preg_replace('/{{key}}/', $name, $line);
            $line = preg_replace('/{{author}}/', $author, $line);               //作者信息
            file_put_contents($phpFile, $line, FILE_APPEND);                    //文件追加
        }
        fclose($file);
        echo 'success, file path:' . $phpFile;
        break;
    //生成model
    case 'Model':
        $phpFile = $config[$type] . $name . 'Model.php';
        rewrite($phpFile, $option);

        $tableSql = getSql($argv[3]);
        preg_match_all('/`(\w+)` \w+/', $tableSql, $column);
        $column = $column[1];                                                   //表字段
        $file = fopen('./template/Model.txt', "r");
        while(!feof($file))
        {
            $line = fgets($file);                                               //fgets()函数从文件指针中读取一行
            if (strpos($line, '{{column}}') > -1) {
               foreach ($column as $val) {
                   $temp = "        '$val'," . PHP_EOL;
                   file_put_contents($phpFile, $temp, FILE_APPEND);             //文件追加
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

//获取建表信息
function getSql($table, $configKey = 'jeanku')
{
    try {
        if (empty($config)) {
            $databases = require './config/database.php';
            $database = $databases[$configKey];
        }
        $dbh = new PDO(sprintf("mysql:host=%s;dbname=%s",$database['host'],$database['database']), $database['username'], $database['password']);
        $data = $dbh->query('show create table ' . $table);
        foreach($data as $row) {
            $tableSql = $row[1];
        }
        $dbh = null;
        return $tableSql;
    } catch (PDOException $e) {
        print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }
}

//是否重写文件
function rewrite($path, $option){
    if (file_exists($path)) {
        if (!in_array('r', $option)) {
            echo 'file already exist';
            exit;
        } else {
            file_put_contents($path, "");                        //文件重写 先清空
        }
    }
}

//获取帮组信息
function help($param){
    if ((isset($param[1]) && $param[1] == 'help') || empty($param[1])) {
        echo 'Usage:' . PHP_EOL;
        echo '    php artisan.php [type]::[name] [option] [parameter] ' . PHP_EOL;
        echo 'type::name:' . PHP_EOL;
        echo '    Controller  make Controller file' . PHP_EOL;
        echo '    Module      make module file' . PHP_EOL;
        echo '    Model       make model file' . PHP_EOL;
        echo '    name        name of the file' . PHP_EOL;
        echo 'option:' . PHP_EOL;
        echo '    -r        rewrite the file' . PHP_EOL;
        echo '    -t        [parameter] : table name' . PHP_EOL;
        echo 'demo:' . PHP_EOL;
        echo '    Controller  php artisan.php controller::menu -t t_manage_menu' . PHP_EOL;
        echo '    Controller  php artisan.php module::menu' . PHP_EOL;
        echo '    Controller  php artisan.php model::menu -t t_manage_menu' . PHP_EOL;
        echo 'file is exist:' . PHP_EOL;
        echo 'rewrite a existed file:' . PHP_EOL;
        echo '    Controller  php artisan.php model::menu -rt t_manage_menu' . PHP_EOL;
        exit;
    }
}
