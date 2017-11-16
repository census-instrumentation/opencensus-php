--TEST--
OpenCensus Trace: Trace Context from provided spanId
--FILE--
<?php

opencensus_trace_set_context("traceid", '12345678');
opencensus_trace_begin('foo', ['spanId' => 'abcd1234']);
$context = opencensus_trace_context();
opencensus_trace_finish();
echo "Context trace id: " . $context->traceId() . PHP_EOL;
echo "Context span id: " . $context->spanId() . PHP_EOL;

$spans = opencensus_trace_list();
if (count($spans) == 1) {
    $span = $spans[0];
    echo "Span id: " . $span->spanId() . PHP_EOL;
    echo "Parent span id: " . $span->parentSpanId() . PHP_EOL;
} else {
    echo "Wrong number of spans. Expected 1, got " . count($spans) . PHP_EOL;
}
?>
--EXPECT--
Context trace id: traceid
Context span id: abcd1234
Span id: abcd1234
Parent span id: 12345678
