<?php
namespace App\Services;

use Jeanku\Rabbitmq\Consume;
/**
 * consume message
 * @desc more description
 * @date 2018-04-02
 */
class Log extends Consume
{

    protected $exchange = 'services';
    protected $queue = 'logs';
    protected $route = 'logs';

    /**
     * 处理message方法
     * @param string $mge require message
     * @return array
     */
    public function handle($msg) {
        echo $msg . "\n";
    }
}