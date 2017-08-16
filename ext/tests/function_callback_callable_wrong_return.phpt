--TEST--
OpenCensus Trace: Callback returning a non-array response should yield a warning.
--FILE--
<?php

require_once(__DIR__ . '/common.php');

function wrong_return($x)
{
    return $x;
}

opencensus_trace_function('foo', 'wrong_return');
foo(3);
?>
--EXPECTF--
Warning: main(): Trace callback should return array in %s on line %d
