--TEST--
OpenCensus Trace: Providing integer as name
--FILE--
<?php

opencensus_trace_begin('foo', ['name' => null]);
opencensus_trace_finish();
$spans = opencensus_trace_list();

echo 'Number of spans: ' . count($spans) . PHP_EOL;
$span = $spans[0];
var_dump($span->name());

?>
--EXPECTF--
Warning: opencensus_trace_begin(): Provided name should be a string in %s on line %d
Number of spans: 1
string(3) "foo"