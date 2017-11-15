--TEST--
OpenCensus Trace: Basic Class Static Method Callback
--FILE--
<?php

require_once(__DIR__ . '/common.php');

// Scope should not be available for a static method
opencensus_trace_function("Foo::plus", function ($x, $y) {
    return ['attributes' => ['x' => '' . $x, 'y' => '' . $y]];
});
$output = Foo::plus(2, 3);
echo "2 + 3 = $output\n";
$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];

$test = gettype($span->spanId());
echo "Span id is a $test\n";

echo "Span name is: '{$span->name()}'\n";

$test = gettype($span->startTime()) == 'double';
echo "Span startTime is a double: $test\n";

$test = gettype($span->endTime()) == 'double';
echo "Span endTime is a double: $test\n";

print_r($span->attributes());

?>
--EXPECT--
2 + 3 = 5
Number of traces: 1
Span id is a string
Span name is: 'Foo::plus'
Span startTime is a double: 1
Span endTime is a double: 1
Array
(
    [x] => 2
    [y] => 3
)
