## Install

Via Composer

``` bash
 composer require jeanku/rabbitmq:dev-master
```


# initialization
you can set database config at entrance file(index.php) as follow:
``` bash
.env file config:
RABBITMQ_HOST=127.0.0.1             #rabbitmq host
RABBITMQ_USERNAME=rabbitmq          #rabbitmq user
RABBITMQ_PASSWORD=123456            #rabbitmq password
RABBITMQ_PORT=5672                  #rabbit port
```

# push message
``` bash
 Jeanku\Rabbitmq\Product::push($content, $exchangeKey, $routeKey);
```

# consume message
create your Class and Extends Jeanku\Rabbitmq\Consume;
``` bash
<?php
 
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
 ```

 
	


 
