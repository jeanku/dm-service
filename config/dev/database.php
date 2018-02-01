<?php

return [
    'fetch' => PDO::FETCH_CLASS,
    'default' => 'chaojijiaolian',
    'connections' => [
        'chaojijiaolian' => [
            'driver' => 'mysql',
            'host' => '10.10.1.138',
            'port' => '3306',
            'database' => 'chaojijiaolian',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'wcard' => [
            'driver' => 'mysql',
            'host' => '10.10.1.138',
            'port' => '3306',
            'database' => 'wcard',
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
