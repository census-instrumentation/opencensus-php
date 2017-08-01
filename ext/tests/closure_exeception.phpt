--TEST--
OpenCensus Trace: Closure exception should not segfault
--FILE--
<?php

require_once(__DIR__ . '/common.php');

opencensus_trace_function("foo", function ($x) {
    // should be an exception
    echo $x->bar();
    return ['name' => 'foo', 'startTime' => 0.1, 'labels' => ['asdf' => 'qwer' . $x, 'zxcv' => 'jkl;']];
});
foo(3);
?>
--EXPECTF--
Fatal error: Uncaught Error: Call to a member function bar() on integer in %s
Stack trace:
#%d %s
#%d %s
  thrown in %s on line %d
