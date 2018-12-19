--TEST--
OpenCensus Trace: Span Class Test
--FILE--
<?php

require_once(__DIR__ . '/common.inc');

$span = new OpenCensus\Trace\Ext\Span([
    'spanId' => 1234,
    'name' => 'foo',
    'startTime' => 12345.1,
    'endTime' => 23456.2
]);

echo "Span id: {$span->spanId()}\n";

echo "Span name: {$span->name()}\n";

echo "Span startTime: {$span->startTime()}\n";

echo "Span endTime: {$span->endTime()}\n";

var_dump(gettype($span->attributes()));

var_dump(gettype($span->stackTrace()));

var_dump(gettype($span->links()));

var_dump(gettype($span->timeEvents()));

?>
--EXPECT--
Span id: 1234
Span name: foo
Span startTime: 12345.1
Span endTime: 23456.2
string(5) "array"
string(5) "array"
string(5) "array"
string(5) "array"
