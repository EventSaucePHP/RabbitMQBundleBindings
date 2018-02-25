<?php

namespace EventSauce\RabbitMQ\Tests;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Event;
use EventSauce\EventSourcing\PointInTime;

class TestEvent implements Event
{
    /**
     * @var PointInTime
     */
    private $timeOfRecording;

    public function __construct(PointInTime $timeOfRecording)
    {
        $this->timeOfRecording = $timeOfRecording;
    }


    public function aggregateRootId(): AggregateRootId
    {
        return $this->aggregateRootId;
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function timeOfRecording(): PointInTime
    {
        return $this->timeOfRecording;
    }

    public function toPayload(): array
    {
        return [];
    }

    public static function fromPayload(array $payload, PointInTime $timeOfRecording): Event
    {
        return new TestEvent($timeOfRecording);
    }
}