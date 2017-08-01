--TEST--
OpenCensus Trace: Test setting labels
--FILE--
<?php

function foo() {
    return 'bar';
}
opencensus_trace_function('foo', function () {
    return ['labels' => ['int' => 1, 'float' => 0.1]];
});

foo();

$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];
print_r($span->labels());
?>
--EXPECT--
Number of traces: 1
Array
(
    [int] => 1
    [float] => 0.1
)
