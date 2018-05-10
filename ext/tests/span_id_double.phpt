--TEST--
OpenCensus Trace: Test setting span id to null
--FILE--
<?php

opencensus_trace_begin('root', ['spanId' => 1.23]);
opencensus_trace_finish();

$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];
var_dump($span->spanId());
?>
--EXPECTF--
Warning: opencensus_trace_begin(): Provided spanId should be a hex string in %s on line %d
Number of traces: 1
string(%d) "%s"