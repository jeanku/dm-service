<?php
namespace Jeanku\Rabbitmq;


/**
 * simple description
 * @desc more description
 * @author jeanku
 * @date 2018-04-02
 */
class Connection
{

    private static $instance = null;                     //单例对象

    protected function __construct(){}

    public static function getInstance()                 //获取链接对象
    {
        if(empty(self::$instance)) {
            $obj = new self;
            self::$instance = $obj->getConnection();
        }
        return self::$instance;
    }

    /**
     * get connection
     * @date 2018-04-09
     * @return \AMQPConnection
     * @throws \Exception
     */
    public function getConnection()
    {
        $config = require dirname(dirname(__FILE__)) . '/config/connection.php';
        $conn = new \AMQPConnection($config);
        //创建连接和channel
        if (!$conn->connect()) {
            throw new \Exception("Cannot connect to the broker!", -1);
        }
        return $conn;
    }


    public function disconnect()
    {
        self::$instance->disconnect();
    }
}