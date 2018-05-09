<?php

//database.php demo just as laravel database.php
return [
    'default' => 'database1',
    'connections' => [
        'database1' => [
            'driver' => 'mysql',
            'host' => '0.0.0.0',
            'port' => '3306',
            'database' => 'database1',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],
        'database2' => [
            'driver' => 'mysql',
            'host' => '0.0.0.0',
            'port' => '3306',
            'database' => 'database2',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],
    ],
];
