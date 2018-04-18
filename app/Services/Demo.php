<?php
namespace App\Services;

use Jeanku\Rabbitmq\Consume;

/**
 * consume message
 * @desc more description
 * @date 2018-04-02
 */
class Demo extends Consume
{
    //exchange name
    protected $exchange = 'demo';
    //queue name
    protected $queue = 'email';
    //route key
    protected $route = 'email';
    //default direct
    protected $type = AMQP_EX_TYPE_DIRECT;
    //空队列等待时间 默认10秒
    protected $wait = 10;
    //多消费者任务均衡分配 默认一个
    protected $prefetch = 1;

    /**
     * your business code
     * @param string $mge require the message you get from queue
     * @return array
     */
    public function handle($msg) {
        //todo
    }
}