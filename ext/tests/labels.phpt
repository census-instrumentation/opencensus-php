--TEST--
OpenCensus Trace: Test setting attributes
--FILE--
<?php

opencensus_trace_begin('root', ['spanId' => '1234']);
opencensus_trace_add_attribute('foo', 'bar');
opencensus_trace_begin('inner', []);
opencensus_trace_add_attribute('asdf', 'qwer', ['spanId' => '1234']);
opencensus_trace_add_attribute('abc', 'def');
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
    [foo] => bar
    [asdf] => qwer
)
Array
(
    [abc] => def
)
