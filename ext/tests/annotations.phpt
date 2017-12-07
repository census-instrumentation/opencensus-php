--TEST--
OpenCensus Trace: Test setting annotations
--FILE--
<?php

opencensus_trace_begin('root', ['spanId' => '1234']);
opencensus_trace_add_annotation('foo');
opencensus_trace_begin('inner', []);
opencensus_trace_add_annotation('asdf', ['spanId' => '1234']);
opencensus_trace_add_annotation('abc');
opencensus_trace_finish();
opencensus_trace_finish();

$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];
print_r($span->timeEvents());

$span2 = $traces[1];
print_r($span2->timeEvents());
?>
--EXPECTF--
Number of traces: 2
Array
(
    [0] => OpenCensus\Trace\Ext\Annotation Object
        (
            [description:protected] => foo
            [time:protected] => %d.%d
            [options:protected] => Array
                (
                )

        )

    [1] => OpenCensus\Trace\Ext\Annotation Object
        (
            [description:protected] => asdf
            [time:protected] => %d.%d
            [options:protected] => Array
                (
                )

        )

)
Array
(
    [0] => OpenCensus\Trace\Ext\Annotation Object
        (
            [description:protected] => abc
            [time:protected] => %d.%d
            [options:protected] => Array
                (
                )

        )

)
