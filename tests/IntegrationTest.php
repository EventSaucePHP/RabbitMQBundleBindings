<?php

namespace EventSauce\RabbitMQ\Tests;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\RabbitMQ\RabbitMQConsumer;
use EventSauce\RabbitMQ\RabbitMQMessageDispatcher;
use EventSauce\Time\TestClock;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    /**
     * @test
     */
    public function it_works()
    {
        $connection = new AMQPStreamConnection(
            'localhost',
            '5672',
            'username',
            'password'
        );

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
            'exclusive'   => true,
            'auto_delete' => false,
        ];
        $producer->setQueueOptions($queueOptions);
        $dispatcher = new RabbitMQMessageDispatcher($producer, new ConstructingMessageSerializer());
        $event = new TestEvent(AggregateRootId::create(), (new TestClock())->pointInTime());
        $message = new Message($event);
        $dispatcher->dispatch($message);

        $collector = new CollectingConsumer();
        $messageConsumer = new RabbitMQConsumer($collector, new ConstructingMessageSerializer());

        $consumer = new Consumer($connection, $channel, 'consumer');
        $consumer->setQueueOptions($queueOptions);
        $consumer->setExchangeOptions($exchangeOptions);
        $consumer->setCallback([$messageConsumer, 'execute']);
        $consumer->consume(1);

        $this->assertEquals($message, $collector->message);

        $channel->close();
        $connection->close();
    }
}