<?php

namespace EventSauce\RabbitMQ;

use EventSauce\EventSourcing\Consumer;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use Generator;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

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

    /**
     * @var ExceptionHandler
     */
    private $exceptionHandler;

    public function __construct(Consumer $consumer, MessageSerializer $serializer, ExceptionHandler $exceptionHandler = null)
    {
        $this->consumer = $consumer;
        $this->serializer = $serializer;
        $this->exceptionHandler = $exceptionHandler ?: new NaiveExceptionHandler(ConsumerInterface::MSG_REJECT_REQUEUE);
    }

    public function execute(AMQPMessage $msg)
    {
        $payload = json_decode($msg->getBody(), true);
        $messages = $this->serializer->unserializePayload($payload);

        try {
            $this->handleMessages($messages);
        } catch (Throwable $throwable) {
            return $this->exceptionHandler->handle($throwable);
        }

        return ConsumerInterface::MSG_ACK;
    }


    private function handleMessages(Generator $messages)
    {
        /** @var Message $message */
        foreach ($messages as $message) {
            $this->consumer->handle($message);
        }
    }
}