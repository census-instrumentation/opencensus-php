--TEST--
OpenCensus Trace: Providing integer as name
--FILE--
<?php

class UncastableObject
{
    private $value = 'my value';
}

$obj = new UncastableObject();
opencensus_trace_begin('foo', ['name' => $obj]);
opencensus_trace_finish();
$spans = opencensus_trace_list();

echo 'Number of spans: ' . count($spans) . PHP_EOL;
$span = $spans[0];
var_dump($span->name());

?>
--EXPECTF--
Fatal error: %s: Object of class UncastableObject could not be converted to string in %s/name_uncastable_object.php:%d
Stack trace:
#0 %s/name_uncastable_object.php(%d): opencensus_trace_begin('foo', Array)
#1 {main}
  thrown in %s/name_uncastable_object.php on line %d
