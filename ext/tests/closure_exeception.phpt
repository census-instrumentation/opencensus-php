--TEST--
OpenCensus Trace: Closure exception should not segfault
--FILE--
<?php

require_once(__DIR__ . '/common.inc');

opencensus_trace_function("foo", function ($x) {
    // should be an exception
    echo $x->bar();
    return ['name' => 'foo', 'startTime' => 0.1, 'attributes' => ['asdf' => 'qwer' . $x, 'zxcv' => 'jkl;']];
});
foo(3);
?>
--EXPECTF--
Warning: main(): Exception in trace callback in %s on line %d
