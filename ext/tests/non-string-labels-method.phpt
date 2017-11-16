--TEST--
OpenCensus Trace: Test setting attributes
--FILE--
<?php

class FooClass
{
    function foo() {
        return 'bar';
    }
}
opencensus_trace_method('FooClass', 'foo', ['attributes' => ['int' => 1, 'float' => 0.1]]);
$foo = new FooClass();
$foo->foo();

$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];
print_r($span->attributes());
?>
--EXPECT--
Number of traces: 1
Array
(
    [int] => 1
    [float] => 0.1
)
