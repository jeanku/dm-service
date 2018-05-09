<?php
namespace Jeanku\Rabbitmq;

/**
 * consume message
 * @desc more description
 * @date 2018-04-02
 */
abstract class Consume
{

    protected $exchange = '';
    protected $queue = '';
    protected $route = '';

    protected $type = AMQP_EX_TYPE_DIRECT;                    //交换机

    protected $wait = 10;                                     //空队列等待时间 默认10秒
    protected $prefetch = 1;                                  //多消费者任务均衡分配

    public final function run()
    {
        $conn = Connection::getInstance();
        $channel = new \AMQPChannel($conn);
        $channel->setPrefetchCount($this->prefetch);          //多消费者任务均衡分配

        //创建交换机
        $exchange = new \AMQPExchange($channel);
        $exchange->setName($this->exchange);
        $exchange->setType($this->type);                      //direct类型
        $exchange->setFlags(AMQP_DURABLE);                    //持久化

        echo 'Exchange Status: ' . $exchange->declareExchange() . "\n";
        //创建队列
        $queue = new \AMQPQueue($channel);
        $queue->setName($this->queue);
        $queue->setFlags(AMQP_DURABLE);                       //持久化
        echo 'Message Total: ' . $queue->declareQueue() . "\n";

        //绑定交换机与队列，并指定路由键
        echo 'Queue Bind: '.$queue->bind($this->exchange, $this->route)."\n";

        //阻塞模式接收消息
        while(True){
            $queue->consume(function(\AMQPEnvelope $e, \AMQPQueue $q) {
                $msg = $e->getBody();
                if ($msg) {
                    $this->handle($msg);
                } else {
                    sleep($this->wait);
                }
            }, AMQP_AUTOACK);
        }
        $conn->disconnect();
    }

    /**
     * handle message
     * @param string $mge require message
     * @return array
     */
    abstract function handle($msg);
}