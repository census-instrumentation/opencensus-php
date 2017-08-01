--TEST--
OpenCensus Trace: Context Class Test
--FILE--
<?php

require_once(__DIR__ . '/common.php');

if (class_exists('OpenCensus\Trace\Context')) {
    echo "OpenCensus\\Trace\\Context class is defined.\n";
}

$context = new OpenCensus\Trace\Context([
    'spanId' => 1234,
    'traceId' => 'foo'
]);

echo "Span id: {$context->spanId()}\n";
echo "Trace id: {$context->traceId()}\n";

?>
--EXPECT--
OpenCensus\Trace\Context class is defined.
Span id: 1234
Trace id: foo
