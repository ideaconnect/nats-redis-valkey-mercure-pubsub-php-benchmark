<?php

use Predis\Client;

include "vendor/autoload.php";

$clientId = $argv[1];
$pass = $argv[2];

$client = new Client([
    'scheme' => 'tcp',
    'host' => 'localhost',
    'port' => '5550',
], [ ]);

$object = '[' . file_get_contents('sample.json');

$startTime = microtime(true);
for ($i = 0; $i < 100001 ; $i++) {
    $client->publish('events', $object . ',{ "t": ' . $i . '}]');
}

$timeConsumed = round(microtime(true) - $startTime,3)*1000;
file_put_contents('results/pass.'.$pass.'.sender.'.$clientId.'.txt', $timeConsumed);