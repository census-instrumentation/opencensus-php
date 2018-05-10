--TEST--
OpenCensus Trace: Test setting kind to null
--FILE--
<?php

opencensus_trace_begin('root', ['kind' => null]);
opencensus_trace_finish();

$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];
var_dump($span->kind());
?>
--EXPECTF--
Warning: opencensus_trace_begin(): Provided kind should be a string in %s on line %d
Number of traces: 1
string(21) "SPAN_KIND_UNSPECIFIED"