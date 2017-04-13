<?php

namespace EventSauce\RabbitMQ\Tests;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\RabbitMQ\NaiveExceptionHandler;
use EventSauce\RabbitMQ\RabbitMQConsumer;
use EventSauce\RabbitMQ\RabbitMQMessageDispatcher;
use EventSauce\Time\TestClock;
use function file_get_contents;
use function json_decode;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PHPUnit\Framework\TestCase;
use function sleep;
use Throwable;
use function usleep;

class IntegrationTest extends TestCase
{
    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var Producer
     */
    private $producer;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var Consumer
     */
    private $consumer;

    /**
     * @before
     */
    public function setupConnection()
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
            'exclusive'   => false,
            'auto_delete' => false,
        ];
        $producer->setQueueOptions($queueOptions);

        $this->producer = $producer;
        $this->connection = $connection;
        $this->channel = $channel;

        $consumer = new Consumer($connection, $channel, 'consumer');
        $consumer->setQueueOptions($queueOptions);
        $consumer->setExchangeOptions($exchangeOptions);
        $consumer->setIdleTimeout(0.5);
        $this->consumer = $consumer;
    }

    /**
     * @after
     */
    public function closeConnection()
    {
        try {
            $this->consumer->stopConsuming();
            $this->consumer->delete();
        } catch (Throwable $ignore) {}

        $this->channel->close();
        $this->connection->close();
    }

    /**
     * @test
     */
    public function it_works()
    {
        $dispatcher = new RabbitMQMessageDispatcher($this->producer, new ConstructingMessageSerializer());
        $event = new TestEvent(AggregateRootId::create(), (new TestClock())->pointInTime());
        $message = new Message($event);
        $dispatcher->dispatch($message);

        $collector = new CollectingConsumer();
        $messageConsumer = new RabbitMQConsumer($collector, new ConstructingMessageSerializer());

        $this->consumer->setCallback([$messageConsumer, 'execute']);
        $this->consumer->consume(1);

        $this->assertEquals($message, $collector->message);
    }

    /**
     * @test
     */
    public function requeue_rejected_messages()
    {
        $serializer = new ConstructingMessageSerializer();
        $dispatcher = new RabbitMQMessageDispatcher($this->producer, $serializer);
        $event = new TestEvent(AggregateRootId::create(), (new TestClock())->pointInTime());
        $message = new Message($event);
        $dispatcher->dispatch($message);

        $exceptionThrower = new ExceptionThrowingConsumer();
        $messageConsumer = new RabbitMQConsumer($exceptionThrower, $serializer);

        $this->consumer->setCallback([$messageConsumer, 'execute']);
        $this->consumer->consume(1);
    }

    /**
     * @test
     */
    public function nacking_messages()
    {
        $serializer = new ConstructingMessageSerializer();
        $dispatcher = new RabbitMQMessageDispatcher($this->producer, $serializer);
        $event = new TestEvent(AggregateRootId::create(), (new TestClock())->pointInTime());
        $message = new Message($event);
        $dispatcher->dispatch($message);

        $exceptionThrower = new ExceptionThrowingConsumer();
        $messageConsumer = new RabbitMQConsumer($exceptionThrower, $serializer, new NaiveExceptionHandler(ConsumerInterface::MSG_REJECT));

        $this->consumer->setCallback([$messageConsumer, 'execute']);

        try {
            $this->consumer->consume(2);
        } catch (AMQPTimeoutException $idledTooLong) {
            // ignore this
        }
        $this->assertEquals(1, $exceptionThrower->counter);
    }
}