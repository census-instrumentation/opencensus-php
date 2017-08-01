--TEST--
OpenCensus Trace: Test setting labels
--FILE--
<?php

opencensus_trace_begin('root', []);
opencensus_trace_add_label('int', 1);
opencensus_trace_begin('inner', []);
opencensus_trace_add_label('float', 0.1);
opencensus_trace_finish();
opencensus_trace_finish();

$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];
print_r($span->labels());

$span2 = $traces[1];
print_r($span2->labels());
?>
--EXPECT--
Number of traces: 2
Array
(
    [int] => 1
)
Array
(
    [float] => 0.1
)
