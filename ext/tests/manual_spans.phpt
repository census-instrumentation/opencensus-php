--TEST--
OpenCensus Trace: Customize the trace span options for a function
--FILE--
<?php

require_once(__DIR__ . '/common.php');

opencensus_trace_begin('/', ['startTime' => 0.1, 'labels' => ['asdf' => 'qwer']]);

opencensus_trace_begin('inner-1', ['labels' => ['foo' => 'bar']]);

opencensus_trace_finish();

opencensus_trace_finish();

$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];

$test = gettype($span->spanId());
echo "Span id is a $test\n";

echo "Span name is: '{$span->name()}'\n";

$test = gettype($span->startTime()) == 'double';
echo "Span startTime is a double: $test\n";

echo "Span startTime is: '{$span->startTime()}'\n";

$test = gettype($span->endTime()) == 'double';
echo "Span endTime is a double: $test\n";

print_r($span->labels());

$span = $traces[1];

$test = gettype($span->spanId());
echo "Span id is a $test\n";

echo "Span name is: '{$span->name()}'\n";

$test = gettype($span->startTime()) == 'double';
echo "Span startTime is a double: $test\n";

$test = gettype($span->endTime()) == 'double';
echo "Span endTime is a double: $test\n";

print_r($span->labels());
?>
--EXPECT--
Number of traces: 2
Span id is a integer
Span name is: '/'
Span startTime is a double: 1
Span startTime is: '0.1'
Span endTime is a double: 1
Array
(
    [asdf] => qwer
)
Span id is a integer
Span name is: 'inner-1'
Span startTime is a double: 1
Span endTime is a double: 1
Array
(
    [foo] => bar
)
