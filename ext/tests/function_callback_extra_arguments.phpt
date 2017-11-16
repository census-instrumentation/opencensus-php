--TEST--
OpenCensus Trace: Customize the trace span options for a function with a callback closure that reads extra arguments
--FILE--
<?php

function foo() {
    return 'bar';
}

// 1: Sanity test a simple profile run
opencensus_trace_function('foo', function () {
    $args = func_get_args();
    return ['attributes' => ['argc' => count($args), 'argc2' => func_num_args(), 'arg' => $args[0]]];
});

foo(3);
$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];

print_r($span->attributes());
?>
--EXPECT--
Number of traces: 1
Array
(
    [argc] => 1
    [argc2] => 1
    [arg] => 3
)
