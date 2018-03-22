<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Configure and start the OpenCensus Tracer
use OpenCensus\Trace\Tracer;
$exporter = new OpenCensus\Trace\Exporter\EchoExporter();
Tracer::start($exporter);

function fib($n)
{
    return Tracer::inSpan([
        'name' => 'fib',
        'attributes' => [
            'n' => $n
        ]
    ], function () use ($n) {
        if ($n < 3) {
            return $n;
        }
        return fib($n - 1) + fib($n - 2);
    });
}

$app = new Silex\Application();

$app->get('/', function () {
    return 'Hello World!';
});

$app->get('/fib/{n}', function ($n) use ($app) {
    $n = (int) $n;
    $fib = fib($n);
    return sprintf('The %dth Fibonacci number is %d.', $n, $fib);
});

$app->run();
