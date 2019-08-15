<?php

include __DIR__.'/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;

$start = time();

while (true) {
    start:
    try {
        /** @var AMQPStreamConnection $connection */
        $connection = include __DIR__.'/setup-connection.php';
        $connection->close();
        fwrite(STDOUT, 'Docker container started!');
        exit(0);
    } catch (AMQPConnectionClosedException $closedException) {
        $elapsed = time() - $start;
        if ($elapsed > 30) {
            fwrite(STDERR, 'Docker container did not start in time...' . PHP_EOL);
            exit(1);
        }
    }

    fwrite(STDOUT, 'Waiting for container to start...' . PHP_EOL);
    sleep(1);
};
