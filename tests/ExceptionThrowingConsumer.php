<?php

namespace EventSauce\RabbitMQ\Tests;

use EventSauce\EventSourcing\Consumer;
use EventSauce\EventSourcing\Message;
use LogicException;

class ExceptionThrowingConsumer implements Consumer
{
    public $counter = 0;

    public function handle(Message $message)
    {
        $this->counter++;

        throw new LogicException;
    }
}