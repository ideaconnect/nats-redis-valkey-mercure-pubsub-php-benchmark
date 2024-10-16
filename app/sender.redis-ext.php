<?php

$clientId = $argv[1];
$pass = $argv[2];

$client = new \Redis();
$client->connect('localhost', 5550);
$client->setOption(\Redis:: OPT_READ_TIMEOUT, -1);
$client->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);

$object = '[' . file_get_contents('sample.json');

$startTime = microtime(true);
for ($i = 0; $i < 100001 ; $i++) {
    $client->publish('events', $object . ',{ "t": ' . $i . '}]');
}

$timeConsumed = round(microtime(true) - $startTime,3)*1000;
file_put_contents('results/pass.'.$pass.'.sender.'.$clientId.'.txt', $timeConsumed);