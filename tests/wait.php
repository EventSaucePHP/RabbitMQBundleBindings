<?php

$start = time();

while (true) {
    $response = @file_get_contents('http://guest:guest@localhost:15672/api/vhosts');

    if ($response !== false) {
        fwrite(STDOUT, 'Docker container started!');
        exit(0);
    }

    $elapsed = time() - $start;
    if ($elapsed > 30) {
        fwrite(STDERR, 'Docker container did not start in time...' . PHP_EOL);
        exit(1);
    }

    fwrite(STDOUT, 'Waiting for container to start...' . PHP_EOL);
    sleep(1);
};