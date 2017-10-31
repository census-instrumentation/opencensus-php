--TEST--
OpenCensus Trace: Basic Context Test
--FILE--
<?php

$res = opencensus_trace_set_context('traceid', 1234);
echo "Set context: ${res}\n";

$context = opencensus_trace_context();
$class = get_class($context);
echo "Context class: $class\n";
echo "Trace id: {$context->traceId()}\n";
echo "Span id: {$context->spanId()}\n";
?>
--EXPECT--
Set context: 1
Context class: OpenCensus\Trace\Ext\SpanContext
Trace id: traceid
Span id: 1234
