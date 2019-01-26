--TEST--
OpenCensus Trace: Clear Traces
--FILE--
<?php

require_once(__DIR__ . '/common.inc');

// 1: Sanity test a simple profile run
opencensus_trace_function("bar");
bar();
$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
opencensus_trace_clear();
$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";

?>
--EXPECT--
Number of traces: 1
Number of traces: 0
