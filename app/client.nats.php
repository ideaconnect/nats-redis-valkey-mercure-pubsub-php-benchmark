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

// linear delay mode - first retry be in 1ms, second in 2ms, third in 3ms, fourth in 4ms, etc...
$configuration->setDelay(0.001, Configuration::DELAY_LINEAR);

// exponential delay mode - first retry be in 10ms, second in 100ms, third in 1s, fourth if 10 seconds, etc...
$configuration->setDelay(0.01, Configuration::DELAY_EXPONENTIAL);

$client = new Client($configuration);
$client->ping(); // true
$finished = false;

/** @var Queue */
$events = $client->subscribe('events');
while ($msg = $events->next()) {
    $raw = $msg->payload->body;

    if ($startTime === 0) {
        file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.data.0.txt', $raw);
        $startTime = microtime(true);
    }

    if ($i === 5000) {
        file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.data.5000.txt', $raw);
    }

    $msg->ack();
    $message = json_decode($raw, true);
    if (++$i > 100000) {
        $finished = true;
        break;
    }
}

// /** @var Queue */
// $queue = $client->subscribe('events', function ($raw) use (&$startTime, &$i, &$finished, $pass, $clientId) {
//     if ($startTime === 0) {
//         file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.data.txt', $raw);
//         $startTime = microtime(true);
//     }

//     file_put_contents('results/x/'.$i.'.pass.'.$pass.'.client.'.$clientId.'.data.txt', $raw);

//     $message = json_decode($raw);

//     if (++$i > 10000) {
//         $finished = true;
//         return false;
//     }
// });

// while (!$finished) {
//     $client->process(0, false);
// }

$timeConsumed = round(microtime(true) - $startTime,3)*1000;
file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.txt', $timeConsumed);