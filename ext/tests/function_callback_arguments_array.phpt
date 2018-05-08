--TEST--
OpenCensus Trace: Function callback closure that reads array arguments
--FILE--
<?php

function func1($n)
{
    return $n;
}

opencensus_trace_function('func1', function ($n) {
    return [
        'attributes' => [
            'n' => count($n)
        ]
    ];
});

$result = func1([1,2,3]);
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
