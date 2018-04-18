<?php
namespace App\Controllers;

use App\Util\Log;
use App\Modules\Menu;


class HomeController extends BaseController
{


    public function home($param)
    {
        $redis= new \Redis();
        $redis->pconnect('127.0.0.1', 6379);
        $redis->select(1);
        echo 'success';
        echo "<pre>";
        print_r($redis->get('key33'));
//        print_r($redis->set('key32', 23));
//        print_r($redis->set('key33', 33));
        exit;
//        sleep(10);

//        $res = Rabbitmq::push('e_linvo', 'key_1', function(){
//            return json_encode(['123123'=>'412322']);
//        });
//        $res = Product::push('email test', 'services', 'logs');
        return $this->success([]);
    }
}