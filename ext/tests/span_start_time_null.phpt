--TEST--
OpenCensus Trace: Bug 131: Providing null start time defaults to a current time
--FILE--
<?php

opencensus_trace_begin('foo', ['startTime' => null]);
opencensus_trace_finish();
$spans = opencensus_trace_list();

echo 'Number of spans: ' . count($spans) . PHP_EOL;
$span = $spans[0];
var_dump($span->startTime());

$test = microtime(true) - 1 < $span->startTime();
echo 'Start time just happened: ' . $test;

?>
--EXPECTF--
Number of spans: 1
float(%d.%d)
Start time just happened: 1
