<?php
namespace Jeanku\Rabbitmq;

/**
 * product message
 * @desc more description
 * @date 2018-04-02
 */
class Product
{

    /**
     * message push
     * @date 2018-04-11
     * @param string|Closure $message require 信息
     * @param string $exchangeName sometime 交换机key
     * @param string $route sometime 路由key
     * @return array
     */
    public static function push($message, $exchangeName = 'master', $route = '')
    {
        $conn = Connection::getInstance();
        $channel = new \AMQPChannel($conn);
        $exchange = new \AMQPExchange($channel);
        $exchange->setName($exchangeName);
        if (!empty($route)) {
            $exchange->publish($message, $route);
        } else {
            $exchange->publish($message);
        }
        $conn->disconnect();
    }
}