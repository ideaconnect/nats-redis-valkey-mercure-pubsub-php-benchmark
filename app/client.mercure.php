<?php

use Predis\Client;
use Symfony\Component\HttpClient\Chunk\ServerSentEvent;
use Symfony\Component\HttpClient\EventSourceHttpClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\Update;

include "vendor/autoload.php";

$clientId = $argv[1];
$pass = $argv[2];

define('HUB_URL', 'http://localhost:5550/.well-known/mercure');
define('JWT', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InN1YnNjcmliZSI6WyIqIl19fQ.oWDxhh916xLCLfdyVJPjJ6coro-hPB1sKZFzS10d_8k');

$url = HUB_URL.'?'.http_build_query([
    'topic' => 'events',
]);

$client = HttpClient::create(['headers' => [
    'Authorization' => 'Bearer ' . JWT
]]);

$client = new EventSourceHttpClient($client);
$i = 0;

$startTime = 0;

while (true) {
    $source = $client->connect($url);
    foreach ($client->stream($source, 2) as $r => $chunk) {
        if ($chunk->isTimeout()) {
            // this is not an error, Mercure informs that there is no new message in current stream.
            continue;
        }
        if ($chunk->isLast()) {
            continue 2;
        }

        if (!$chunk instanceof ServerSentEvent) {
            continue;
        }

        $raw = $chunk->getData();

        if ($startTime === 0) {
            //store file for comparison
            file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.data.0.txt', $raw);
            $startTime = microtime(true);
        }

        if ($i === 5000) {
            file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.data.5000.txt', $raw);
        }

        $data = json_decode($raw);

        if (++$i > 100000) {
            break 2;
        }
    }
}

$timeConsumed = round(microtime(true) - $startTime,3)*1000;
file_put_contents('results/pass.'.$pass.'.client.'.$clientId.'.txt', $timeConsumed);