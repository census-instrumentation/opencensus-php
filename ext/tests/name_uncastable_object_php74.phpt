--TEST--
OpenCensus Trace: Providing integer as name
--SKIPIF--
<?php
if (version_compare(phpversion(), '7.4', '<')) die("skip this test is for PHP versions >= 7.4; see name_uncastable_object_php74.phpt for the PHP 7.4+ equivalent");
?>
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
Fatal error: Uncaught Error: Object of class UncastableObject could not be converted to string in %sname_uncastable_object_php74.php:%d
Stack trace:
#0 %sname_uncastable_object_php74.php(%d): opencensus_trace_begin('foo', Array)
#1 {main}
  thrown in %sname_uncastable_object_php74.php on line %d