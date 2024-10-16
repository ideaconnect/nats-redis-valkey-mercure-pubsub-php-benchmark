<?php

include "vendor/autoload.php";

$clientId = $argv[1];
$pass = $argv[2];

use Basis\Nats\Client;
use Basis\Nats\Configuration;

// this is default options, you can override anyone
$configuration = new Configuration([
    'host' => 'localhost',
    'jwt' => null,
    'lang' => 'php',
    'pass' => null,
    'pedantic' => false,
    'port' => 4222,
    'reconnect' => true,
    'timeout' => 5,
    'token' => null,
    'user' => null,
    'nkey' => null,
    'verbose' => false,
    'version' => 'prod',
]);

// default delay mode is constant - first retry be in 1ms, second in 1ms, third in 1ms
$configuration->setDelay(0.001);

// linear delay mode - first retry be in 1ms, second in 2ms, third in 3ms, fourth in 4ms, etc...
$configuration->setDelay(0.001, Configuration::DELAY_LINEAR);

// exponential delay mode - first retry be in 10ms, second in 100ms, third in 1s, fourth if 10 seconds, etc...
$configuration->setDelay(0.01, Configuration::DELAY_EXPONENTIAL);

$client = new Client($configuration);
$client->ping(); // true

$object = '[' . file_get_contents('sample.json');
$startTime = microtime(true);
for ($i = 0; $i < 100001 ; $i++) {
    $client->publish('events', $object . ',{ "t": ' . $i . '}]', 'feed');
}

$timeConsumed = round(microtime(true) - $startTime,3)*1000;
file_put_contents('results/pass.'.$pass.'.sender.'.$clientId.'.txt', $timeConsumed);