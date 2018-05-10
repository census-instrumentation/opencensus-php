--TEST--
OpenCensus Trace: Default span stackTrace
--FILE--
<?php

function abcd()
{
    return 3;
}

function myFunction()
{
    abcd();
}

opencensus_trace_function('abcd');
myFunction();

$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";

foreach ($traces as $span) {
    var_dump($span->stackTrace());
}
?>
--EXPECTF--
Number of traces: 1
array(2) {
  [0]=>
  array(3) {
    ["file"]=>
    string(%d) "%s/span_stacktrace_default.php"
    ["line"]=>
    int(10)
    ["function"]=>
    string(4) "abcd"
  }
  [1]=>
  array(3) {
    ["file"]=>
    string(%d) "%s/span_stacktrace_default.php"
    ["line"]=>
    int(14)
    ["function"]=>
    string(10) "myFunction"
  }
}
