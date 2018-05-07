--TEST--
OpenCensus Trace: Customize the trace span options for a function with a callback closure that reads string arguments
--FILE--
<?php

class TestClass
{
    public function func1($n)
    {
        return $n;
    }
}

opencensus_trace_method(TestClass::class, 'func1', function ($obj, $n) {
    return [
        'attributes' => [
            'n' => $n
        ]
    ];
});

$obj = new TestClass();
$result = $obj->func1('hello');
echo "Result: $result" . PHP_EOL;

$spans = opencensus_trace_list();
echo "Number of spans: " . count($spans) . "\n";
$span = $spans[0];

print_r($span->attributes());
?>
--EXPECT--
Result: hello
Number of spans: 1
Array
(
    [n] => hello
)
