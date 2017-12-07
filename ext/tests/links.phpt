--TEST--
OpenCensus Trace: Test setting links
--FILE--
<?php

opencensus_trace_begin('root', ['spanId' => '1234']);
opencensus_trace_add_link('traceId', 'spanId');
opencensus_trace_begin('inner', []);
opencensus_trace_add_link('traceId', 'spanId', ['spanId' => '1234']);
opencensus_trace_add_link('traceId', 'spanId');
opencensus_trace_finish();
opencensus_trace_finish();

$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];
print_r($span->links());

$span2 = $traces[1];
print_r($span2->links());
?>
--EXPECT--
Number of traces: 2
Array
(
    [0] => OpenCensus\Trace\Ext\Link Object
        (
            [traceId:protected] => traceId
            [spanId:protected] => spanId
            [options:protected] => Array
                (
                )

        )

    [1] => OpenCensus\Trace\Ext\Link Object
        (
            [traceId:protected] => traceId
            [spanId:protected] => spanId
            [options:protected] => Array
                (
                )

        )

)
Array
(
    [0] => OpenCensus\Trace\Ext\Link Object
        (
            [traceId:protected] => traceId
            [spanId:protected] => spanId
            [options:protected] => Array
                (
                )

        )

)
