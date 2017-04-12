<?php

namespace EventSauce\RabbitMQ\Tests;

use EventSauce\EventSourcing\Consumer;
use EventSauce\EventSourcing\Message;

class CollectingConsumer implements Consumer
{
    public $message;

    public function handle(Message $message)
    {
        $this->message = $message;
    }
}