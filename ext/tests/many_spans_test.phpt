--TEST--
OpenCensus Trace: Testing many spans (previous limit of 64)
--FILE--
<?php

for ($i = 0; $i < 1024; $i++) {
    opencensus_trace_begin("Span $i");
    opencensus_trace_finish();
}
$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];
?>
--EXPECT--
Number of traces: 1024
