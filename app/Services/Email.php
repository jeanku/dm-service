<?php
namespace App\Services;

use Jeanku\Rabbitmq\Consume;
/**
 * consume message
 * @desc more description
 * @date 2018-04-02
 */
class Email extends Consume
{

    protected $exchange = 'services';
    protected $queue = 'email';
    protected $route = 'email';

    /**
     * 处理message方法
     * @param string $mge require message
     * @return array
     */
    public function handle($msg) {
        echo $msg . "\n";
    }
}