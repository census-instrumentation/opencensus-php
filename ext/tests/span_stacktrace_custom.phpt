--TEST--
OpenCensus Trace: Custom span stackTrace
--FILE--
<?php

$stackTrace = [
  [
    'file' => 'foo.php',
    'line' => 10,
    'function' => 'bar'
  ]
];
opencensus_trace_begin('main', ['stackTrace' => $stackTrace]);
opencensus_trace_finish();

$spans = opencensus_trace_list();
echo "Number of spans: " . count($spans) . PHP_EOL;
var_dump($spans[0]->stackTrace());
?>
--EXPECTF--
Number of spans: 1
array(1) {
  [0]=>
  array(3) {
    ["file"]=>
    string(7) "foo.php"
    ["line"]=>
    int(10)
    ["function"]=>
    string(3) "bar"
  }
}
