--TEST--
OpenCensus Trace: Test setting attributes
--FILE--
<?php

opencensus_trace_begin('root', []);
opencensus_trace_add_attribute('int', 1);
opencensus_trace_begin('inner', []);
opencensus_trace_add_attribute('float', 0.1);
opencensus_trace_finish();
opencensus_trace_finish();

$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];
print_r($span->attributes());

$span2 = $traces[1];
print_r($span2->attributes());
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
