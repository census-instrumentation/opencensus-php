--TEST--
OpenCensus Trace: Callback returning a non-array response should yield a warning.
--FILE--
<?php

require_once(__DIR__ . '/common.php');

opencensus_trace_function("foo", function ($x) {
    return $x;
});
foo(3);
?>
--EXPECTF--
Warning: main(): Trace callback should return array in %s on line %d
