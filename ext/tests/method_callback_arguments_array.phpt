--TEST--
OpenCensus Trace: Method callback closure that reads array arguments
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
var_dump($n);
    return [
        'attributes' => [
            'n' => count($n)
        ]
    ];
});

$obj = new TestClass();
$result = $obj->func1([1,2,3]);
echo "Result: " . implode(',', $result) . PHP_EOL;

$spans = opencensus_trace_list();
echo "Number of spans: " . count($spans) . "\n";
$span = $spans[0];

print_r($span->attributes());
?>
--EXPECT--
Result: 1,2,3
Number of spans: 1
Array
(
    [n] => 3
)
