<?php

namespace EventSauce\RabbitMQ;

use Throwable;

class NaiveExceptionHandler implements ExceptionHandler
{
    /**
     * @var int
     */
    private $response;

    public function __construct(int $response)
    {
        $this->response = $response;
    }

    public function handle(Throwable $throwable): int
    {
        return $this->response;
    }
}