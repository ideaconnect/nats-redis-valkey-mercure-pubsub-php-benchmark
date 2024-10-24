<?php

include "vendor/autoload.php";

$passesExpected = intval($argv[1]);
$runnersPerScenario = [
    1 => 1,
    2 => intval($argv[2])
];
// First let's check if all projects delivered.
$expectedProjects = [
    'valkey',
    'valkey-ext',
    'redis',
    'nats',
    'mercure'
];

$expectedDataAt = [0, 5000, 9999];

$files = glob("app/results/*.data.*.txt");

// First lets validate if output files are okay.
$validProjects = [];
foreach ($expectedDataAt as $i) {
    $expectedFileData = '[' . file_get_contents('sample.json') . ',{ "t": ' . $i . '}]';
    $expectedObjectString = json_encode(json_decode($expectedFileData, true));

    //now for each pass
    for ($pass = 1; $pass <= $passesExpected; $pass++) {
        foreach ($expectedProjects as $project) {
            foreach ($runnersPerScenario as $scenario => $runnersCount) {
                //multi subscribers
                for ($runner = 1; $runner <= $runnersCount; $runner++) {
                    $scenarioResultFile = "results/pass.$pass.client.$project.{$scenario}_$runner.data.$i.txt";
                    if (!file_exists($scenarioResultFile)) {
                        throw new Exception("Missing scenario result: " . $scenarioResultFile);
                    }

                    $actualFileData = '[' . file_get_contents($scenarioResultFile) . ',{ "t": ' . $i . '}]';
                    $actualObjectString = json_encode(json_decode($actualFileData, true));

                    if ($actualObjectString !== $actualObjectString) {
                        throw new Exception("Invalid result, expected different text: " . $scenarioResultFile);
                    }

                    $validProjects[] = [
                        'pass' => $pass,
                        'scenario' => $scenario,
                        'project' => $project,
                        'runner' => $runner,
                        'data' => $i
                    ];
                }
            }
        }
    }
}

$senders = [];
$clients = [];
foreach ($expectedProjects as $project) {
    foreach ($runnersPerScenario as $scenario => $runner) {
        if (!isset($senders[$scenario])) {
            $senders[$scenario] = [];
        }

        if (!isset($clients[$scenario])) {
            $clients[$scenario] = [];
        }
        for ($pass = 1; $pass <= $passesExpected; $pass++) {

            //sender
            $time = intval(file_get_contents("results/pass.$pass.sender.$project.$scenario.txt"));


            if (!isset($senders[$scenario][$project])) {
                $senders[$scenario][$project] = [
                    'min' => $time,
                    'avg' => $time,
                    'max' => $time
                ];
            } else {
                $senders[$scenario][$project] = [
                    'min' => min($senders[$scenario][$project]['min'], $time),
                    'avg' => ($senders[$scenario][$project]['avg'] + $time) / 2,
                    'max' => max($senders[$scenario][$project]['max'], $time)
                ];
            }

            //client
            for ($i = 1; $i <= $runner; $i++) {
                $time = intval(file_get_contents("results/pass.$pass.client.$project.{$scenario}_$runner.txt"));
                if (!isset($clients[$scenario][$project])) {
                    $clients[$scenario][$project] = [
                        'min' => $time,
                        'avg' => $time,
                        'max' => $time
                    ];
                } else {
                    $clients[$scenario][$project] = [
                        'min' => min($clients[$scenario][$project]['min'], $time),
                        'avg' => ($clients[$scenario][$project]['avg'] + $time) / 2,
                        'max' => max($clients[$scenario][$project]['max'], $time)
                    ];
                }
            }
        }
    }
}

foreach (array_keys($runnersPerScenario) as $scenario) {
    uasort($senders[$scenario], fn ($a, $b) => $a['avg'] <=> $b['avg']);
    uasort($clients[$scenario], fn ($a, $b) => $a['avg'] <=> $b['avg']);
}

$loader = new \Twig\Loader\FilesystemLoader('./template');
$twig = new \Twig\Environment($loader);
$data = [
    'clients' => $clients,
    'senders' => $senders
];
$html = $twig->render('results.html.twig', ['passes' => $passesExpected, 'data' => $data, 'scenarios' => $runnersPerScenario]);
file_put_contents('results/report.html', $html);
echo "DONE!" . PHP_EOL;
