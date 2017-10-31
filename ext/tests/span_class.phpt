--TEST--
OpenCensus Trace: Span Class Test
--FILE--
<?php

require_once(__DIR__ . '/common.php');

$span = new OpenCensus\Trace\Ext\Span([
    'spanId' => 1234,
    'name' => 'foo',
    'startTime' => 12345.1,
    'endTime' => 23456.2,
    'kind' => 1
]);

echo "Span id: {$span->spanId()}\n";

echo "Span name: {$span->name()}\n";

echo "Span startTime: {$span->startTime()}\n";

echo "Span endTime: {$span->endTime()}\n";

echo "Span kind: {$span->kind()}\n";

?>
--EXPECT--
Span id: 1234
Span name: foo
Span startTime: 12345.1
Span endTime: 23456.2
Span kind: 1
