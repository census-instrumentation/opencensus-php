--TEST--
OpenCensus Trace: Providing string start time should not work
--FILE--
<?php

opencensus_trace_begin('foo', ['startTime' => '' . microtime(true)]);
opencensus_trace_finish();
$spans = opencensus_trace_list();

echo 'Number of spans: ' . count($spans) . PHP_EOL;
$span = $spans[0];
var_dump($span->startTime());

$test = microtime(true) - 1 < $span->startTime();
echo 'Start time just happened: ' . $test;

?>
--EXPECTF--
Warning: opencensus_trace_begin(): Provided startTime should be a float in %s on line %d
Number of spans: 1
float(%d.%d)
Start time just happened: 1
