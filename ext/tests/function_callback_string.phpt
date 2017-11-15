--TEST--
OpenCensus Trace: Customize the trace span options for a function with a callback string
--FILE--
<?php

require_once(__DIR__ . '/common.php');

function callbackHandle()
{
    return ['name' => 'foo', 'startTime' => 0.1, 'attributes' => ['asdf' => 'qwer', 'zxcv' => 'jkl;']];
}

// 1: Sanity test a simple profile run
opencensus_trace_function("bar", 'callbackHandle');
bar();
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

print_r($span->attributes());
?>
--EXPECT--
Number of traces: 1
Span id is a string
Span name is: 'foo'
Span startTime is a double: 1
Span startTime is: '0.1'
Span endTime is a double: 1
Array
(
    [asdf] => qwer
    [zxcv] => jkl;
)
