<?php

use Predis\Client;
use Predis\PubSub\Consumer;

include "vendor/autoload.php";

$clientId = $argv[1];
$pass = $argv[2];

$client = new \Redis();
$client->connect('localhost', 5550);
$client->setOption(\Redis:: OPT_READ_TIMEOUT, -1);
$client->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);

$startTime = 0; //we start after first message
$i = 0;
$consumer = $client->subscribe(['events'], function($redis, $channel, $message) use (&$startTime, &$i, $clientId, $pass) {
    if ($startTime === 0) {
        file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.data.0.txt', $message);
        $startTime = microtime(true);
    }

    if ($i === 5000) {
        file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.data.5000.txt', $message);
    }

    $message = json_decode($message);
    if (++$i > 100000) {
        $redis->unsubscribe(['events']);
    }
});

$timeConsumed = round(microtime(true) - $startTime,3)*1000;
file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.txt', $timeConsumed);