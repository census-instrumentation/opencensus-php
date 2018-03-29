--TEST--
OpenCensus Trace: Set span sameProcessAsParentSpan
--FILE--
<?php
opencensus_trace_begin('/', [
    'startTime' => 0.1,
    'sameProcessAsParentSpan' => true
]);
opencensus_trace_begin('inner-1', [
    'sameProcessAsParentSpan' => false
]);
opencensus_trace_finish();
opencensus_trace_finish();
$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
var_dump($traces[0]->sameProcessAsParentSpan());
var_dump($traces[1]->sameProcessAsParentSpan());
?>
--EXPECT--
Number of traces: 2
bool(true)
bool(false)