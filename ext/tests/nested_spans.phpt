--TEST--
OpenCensus Trace: Nested spans
--FILE--
<?php

require_once(__DIR__ . '/common.inc');

// 1: Sanity test a simple profile run
opencensus_trace_function("foo");
opencensus_trace_function("bar");
$result = foo(2);
$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span1 = $traces[0];
$span2 = $traces[1];
$span3 = $traces[2];

echo "Span 1 name is: '{$span1->name()}'\n";
echo "Span 2 name is: '{$span2->name()}'\n";
echo "Span 3 name is: '{$span3->name()}'\n";

$test = $span1->spanId() == $span2->parentSpanId();
echo "Span 2's parent is span 1: $test\n";

$test = $span1->spanId() == $span3->parentSpanId();
echo "Span 3's parent is span 1: $test\n";

?>
--EXPECT--
Number of traces: 3
Span 1 name is: 'foo'
Span 2 name is: 'bar'
Span 3 name is: 'bar'
Span 2's parent is span 1: 1
Span 3's parent is span 1: 1
