<?php

namespace EventSauce\RabbitMQ;

use Throwable;

interface ExceptionHandler
{
    public function handle(Throwable $throwable): int;
}