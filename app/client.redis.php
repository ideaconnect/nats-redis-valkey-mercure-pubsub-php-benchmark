<?php

use Predis\Client;
use Predis\PubSub\Consumer;

include "vendor/autoload.php";

$clientId = $argv[1];
$pass = $argv[2];

$options = [

];

$client = new Client([
    'scheme' => 'tcp',
    'host' => 'localhost',
    'port' => '5550',
], $options);

$startTime = 0; //we start after first message
$i = 0;
$consumer = $client->pubSubLoop(['subscribe' => 'events'], function (Consumer $l, $eventData) use (&$startTime, &$i, $pass, $clientId) {
    if ('message' !== $eventData->kind) {
        return;
    }

    if ($startTime === 0) {
        file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.data.'.$i.'.txt', $eventData->payload);
        $startTime = microtime(true);
    }

    if ($i === 5000) {
        file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.data.'.$i.'.txt', $eventData->payload);
    }

    if ($i === 9999) {
        file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.data.'.$i.'.txt', $eventData->payload);
    }


    $message = json_decode($eventData->payload);

    if (++$i > 100000) {
        return false;
    }
});

$timeConsumed = round(microtime(true) - $startTime,3)*1000;
file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.txt', $timeConsumed);