--TEST--
OpenCensus Trace: Customize the trace span options for a function
--FILE--
<?php

require_once(__DIR__ . '/common.inc');

opencensus_trace_begin('/');

opencensus_trace_begin('inner-1');

opencensus_trace_finish();

opencensus_trace_finish();

$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
print_r($traces);
?>
--EXPECTF--
Number of traces: 2
Array
(
    [0] => OpenCensus\Trace\Ext\Span Object
        (
            [name:protected] => /
            [spanId:protected] => %s
            [parentSpanId:protected] =>%s
            [startTime:protected] => %d.%d
            [endTime:protected] => %d.%d
            [attributes:protected] => Array
                (
                )

            [stackTrace:protected] => Array
                (
                )

            [links:protected] => Array
                (
                )

            [timeEvents:protected] => Array
                (
                )

            [kind:protected] => SPAN_KIND_UNSPECIFIED
            [sameProcessAsParentSpan:protected] => 1
        )

    [1] => OpenCensus\Trace\Ext\Span Object
        (
            [name:protected] => inner-1
            [spanId:protected] => %s
            [parentSpanId:protected] => %s
            [startTime:protected] => %d.%d
            [endTime:protected] => %d.%d
            [attributes:protected] => Array
                (
                )

            [stackTrace:protected] => Array
                (
                )

            [links:protected] => Array
                (
                )

            [timeEvents:protected] => Array
                (
                )

            [kind:protected] => SPAN_KIND_UNSPECIFIED
            [sameProcessAsParentSpan:protected] => 1
        )

)
