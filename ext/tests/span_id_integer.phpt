--TEST--
OpenCensus Trace: Test setting span id to null
--FILE--
<?php

opencensus_trace_begin('root', ['spanId' => 123]);
opencensus_trace_finish();

$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];
var_dump($span->spanId());
?>
--EXPECT--
Number of traces: 1
string(2) "7b"
