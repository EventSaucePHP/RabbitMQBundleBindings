<?php

namespace EventSauce\RabbitMQ;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

class RabbitMQMessageDispatcher implements MessageDispatcher
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var MessageSerializer
     */
    private $serializer;

    /**
     * @var string
     */
    private $routingKey;

    public function __construct(ProducerInterface $producer, MessageSerializer $serializer, string $routingKey = '')
    {
        $this->producer = $producer;
        $this->serializer = $serializer;
        $this->routingKey = $routingKey;
    }

    public function dispatch(Message ... $messages)
    {
        foreach ($messages as $message) {
            $this->send($message);
        }
    }

    private function send(Message $message)
    {
        $payload = json_encode($this->serializer->serializeMessage($message));
        $this->producer->publish($payload, $this->routingKey);
    }
}