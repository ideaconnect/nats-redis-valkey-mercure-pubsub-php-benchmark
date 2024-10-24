<?php

use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Basis\Nats\Queue;

include "vendor/autoload.php";

$clientId = $argv[1];
$pass = $argv[2];

$configuration = new Configuration([
    'host' => 'localhost',
    'jwt' => null,
    'lang' => 'php',
    'pass' => null,
    'pedantic' => false,
    'port' => 4222,
    'reconnect' => true,
    'timeout' => 1,
    'token' => null,
    'user' => null,
    'nkey' => null,
    'verbose' => false,
    'version' => 'dev',
]);

$startTime = 0; //we start after first message
$i = 0;

// default delay mode is constant - first retry be in 1ms, second in 1ms, third in 1ms
$configuration->setDelay(0.001);

$client = new Client($configuration);
$client->ping(); // true

/** @var Queue */
$events = $client->subscribe('events');
while ($msg = $events->next()) {
    $raw = $msg->payload->body;

    if ($startTime === 0) {
        file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.data.'.$i.'.txt', $raw);
        $startTime = microtime(true);
    }

    if ($i === 5000) {
        file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.data.'.$i.'.txt', $raw);
    }

    if ($i === 9999) {
        file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.data.'.$i.'.txt', $raw);
    }

    $msg->ack();
    $message = json_decode($raw, true);
    if (++$i > 100000) {
        break;
    }
}

$timeConsumed = round(microtime(true) - $startTime,3)*1000;
file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.txt', $timeConsumed);