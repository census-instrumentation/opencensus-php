--TEST--
OpenCensus Trace: Providing integer as name
--FILE--
<?php

opencensus_trace_begin('foo', ['name' => 1.23]);
opencensus_trace_finish();
$spans = opencensus_trace_list();

echo 'Number of spans: ' . count($spans) . PHP_EOL;
$span = $spans[0];
var_dump($span->name());

?>
--EXPECTF--
Number of spans: 1
string(4) "1.23"