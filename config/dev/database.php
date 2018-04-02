<?php

return [
//    'fetch' => PDO::FETCH_CLASS,
//    'default' => 'jeanku',
//    'connections' => [
//        'jeanku' => [
//            'driver' => 'mysql',
//            'host' => '10.10.1.138',
//            'port' => '3306',
//            'database' => 'chaojijiaolian',
//            'username' => 'root',
//            'password' => 'root',
//            'charset' => 'utf8',
//            'collation' => 'utf8_unicode_ci',
//            'prefix' => '',
//            'strict' => false,
//            'engine' => null,
//        ],
//    ],
//
    'fetch' => PDO::FETCH_CLASS,
    'default' => 'jeanku',
    'connections' => [
        'jeanku' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => '3306',
            'database' => 'jeanku',
            'username' => 'root',
            'password' => '123456',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],
    ],
];
