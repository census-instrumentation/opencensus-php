--TEST--
OpenCensus Trace: Set span kind
--FILE--
<?php

require_once(__DIR__ . '/common.php');

opencensus_trace_begin('/', [
    'startTime' => 0.1,
    'kind' => 'SERVER'
]);

opencensus_trace_begin('inner-1', [
    'kind' => 'CLIENT'
]);

opencensus_trace_finish();

opencensus_trace_finish();

$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
echo "Span kind is " . $traces[0]->kind() . PHP_EOL;
echo "Span kind is " . $traces[1]->kind() . PHP_EOL;

?>
--EXPECT--
Number of traces: 2
Span kind is SERVER
Span kind is CLIENT