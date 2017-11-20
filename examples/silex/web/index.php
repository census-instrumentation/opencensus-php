<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Configure and start the OpenCensus Tracer
$exporter = new OpenCensus\Trace\Exporter\StackdriverExporter();
OpenCensus\Trace\Tracer::start($exporter);

$app = new Silex\Application();

$app->get('/', function () {
    return 'Hello World!';
});

$app->get('/hello/{name}', function ($name) use ($app) {
    return 'Hello ' . $app->escape($name);
});

$app->run();
