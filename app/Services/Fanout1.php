<?php
namespace App\Services;

use Jeanku\Rabbitmq\Consume;
/**
 * consume message
 * @desc more description
 * @date 2018-04-02
 */
class Fanout1 extends Consume
{

    protected $exchange = 'fanout';
    protected $queue = 'fanout1';
    protected $route = 'fanout1';

    protected $type = AMQP_EX_TYPE_FANOUT;                      //交换机


    /**
     * 处理message方法
     * @param string $mge require message
     * @return array
     */
    public function handle($msg) {
        echo $msg . "\n";
    }
}