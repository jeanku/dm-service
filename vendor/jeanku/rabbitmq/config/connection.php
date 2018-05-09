<?php
return array(
    'host' => env('RABBITMQ_HOST') ? : '',
    'port' => env('RABBITMQ_PORT') ? : '',
    'login' => env('RABBITMQ_USERNAME') ? : '',
    'password' => env('RABBITMQ_PASSWORD') ? : '',
    'vhost'=>'/'
);