<?php

use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', '5672', 'guest', 'guest');
$channel = $connection->channel();
$queue = 'test.queue';
$exchange = 'test.exchange';

$producer = new Producer($connection, $channel, 'producer');
$exchangeOptions = [
    'name'        => $exchange,
    'passive'     => false,
    'durable'     => true,
    'auto_delete' => false,
    'type'        => 'direct',
];
$producer->setExchangeOptions($exchangeOptions);
$queueOptions = [
    'name'        => $queue,
    'passive'     => false,
    'durable'     => true,
    'exclusive'   => false,
    'auto_delete' => false,
];
$producer->setQueueOptions($queueOptions);
$consumer = new Consumer($connection, $channel, 'consumer');
$consumer->setQueueOptions($queueOptions);
$consumer->setExchangeOptions($exchangeOptions);
$consumer->setIdleTimeout(0.5);

return $consumer;
