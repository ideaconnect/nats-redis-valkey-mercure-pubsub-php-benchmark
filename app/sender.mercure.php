<?php

use Predis\Client;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\Update;

include "vendor/autoload.php";

$clientId = $argv[1];
$pass = $argv[2];

define('HUB_URL', 'http://localhost:5550/.well-known/mercure');
define('JWT', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdfX0.R970Txa8LbE6QyaMAIgJo0mqsokTuO6XAggLvK-ryEw');

$hub = new Hub(HUB_URL, new StaticTokenProvider(JWT));

$object = '[' . file_get_contents('sample.json');

$startTime = microtime(true);
for ($i = 0; $i < 100001 ; $i++) {
    $hub->publish(new Update('events', $object . ',{ "t": ' . $i . '}]'));
}

$timeConsumed = round(microtime(true) - $startTime,3)*1000;
file_put_contents('results/pass.'.$pass.'.sender.'.$clientId.'.txt', $timeConsumed);