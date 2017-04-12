<?php

namespace EventSauce\RabbitMQ;

use EventSauce\EventSourcing\Consumer;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQConsumer implements ConsumerInterface
{
    /**
     * @var Consumer
     */
    private $consumer;

    /**
     * @var MessageSerializer
     */
    private $serializer;

    public function __construct(Consumer $consumer, MessageSerializer $serializer)
    {
        $this->consumer = $consumer;
        $this->serializer = $serializer;
    }

    /**
     * @param AMQPMessage $msg The message
     * @return mixed false to reject and requeue, any other value to acknowledge
     */
    public function execute(AMQPMessage $msg)
    {
        $payload = json_decode($msg->getBody(), true);
        $messages = $this->serializer->unserializePayload($payload);

        /** @var Message $message */
        foreach ($messages as $message) {
            $this->consumer->handle($message);
        }
    }
}