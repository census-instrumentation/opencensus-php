--TEST--
OpenCensus Trace: Customize the trace span options for a function
--FILE--
<?php

require_once(__DIR__ . '/common.inc');

opencensus_trace_begin('/', ['startTime' => 0.1, 'attributes' => ['asdf' => 'qwer']]);

opencensus_trace_begin('inner-1', ['attributes' => ['foo' => 'bar']]);

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

echo "Span kind is: " . $span->kind() . PHP_EOL;

print_r($span->attributes());

$span = $traces[1];

$test = gettype($span->spanId());
echo "Span id is a $test\n";

echo "Span name is: '{$span->name()}'\n";

$test = gettype($span->startTime()) == 'double';
echo "Span startTime is a double: $test\n";

$test = gettype($span->endTime()) == 'double';
echo "Span endTime is a double: $test\n";

echo "Span kind is: " . $span->kind() . PHP_EOL;

print_r($span->attributes());
?>
--EXPECT--
Number of traces: 2
Span id is a string
Span name is: '/'
Span startTime is a double: 1
Span startTime is: '0.1'
Span endTime is a double: 1
Span kind is: SPAN_KIND_UNSPECIFIED
Array
(
    [asdf] => qwer
)
Span id is a string
Span name is: 'inner-1'
Span startTime is a double: 1
Span endTime is a double: 1
Span kind is: SPAN_KIND_UNSPECIFIED
Array
(
    [foo] => bar
)
