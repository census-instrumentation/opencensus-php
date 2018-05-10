--TEST--
OpenCensus Trace: Custom span stackTrace
--FILE--
<?php

function foo()
{
  opencensus_trace_begin('main', ['stackTrace' => 'foo']);
  opencensus_trace_finish();
}
foo();

$spans = opencensus_trace_list();
echo "Number of spans: " . count($spans) . PHP_EOL;
var_dump($spans[0]->stackTrace());
?>
--EXPECTF--
Warning: opencensus_trace_begin(): Provided stackTrace should be an array in %s on line %d
Number of spans: 1
array(1) {
  [0]=>
  array(3) {
    ["file"]=>
    string(%d) "%s/span_stacktrace_invalid.php"
    ["line"]=>
    int(%d)
    ["function"]=>
    string(3) "foo"
  }
}