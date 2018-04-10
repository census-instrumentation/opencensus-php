--TEST--
OpenCensus Trace: Test setting message events
--FILE--
<?php

opencensus_trace_begin('root', ['spanId' => '1234']);
opencensus_trace_add_message_event('TYPE_UNSPECIFIED', 'some id', [
    'compressedSize' => 123
]);
opencensus_trace_begin('inner', []);
opencensus_trace_add_message_event('TYPE_UNSPECIFIED', 'some id', ['spanId' => '1234']);
opencensus_trace_add_message_event('TYPE_UNSPECIFIED', 'some id');
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
    [0] => OpenCensus\Trace\Ext\MessageEvent Object
        (
            [type:protected] => TYPE_UNSPECIFIED
            [id:protected] => some id
            [time:protected] => %d.%d
            [options:protected] => Array
                (
                    [compressedSize] => 123
                )

        )

    [1] => OpenCensus\Trace\Ext\MessageEvent Object
        (
            [type:protected] => TYPE_UNSPECIFIED
            [id:protected] => some id
            [time:protected] => %d.%d
            [options:protected] => Array
                (
                    [spanId] => 1234
                )

        )

)
Array
(
    [0] => OpenCensus\Trace\Ext\MessageEvent Object
        (
            [type:protected] => TYPE_UNSPECIFIED
            [id:protected] => some id
            [time:protected] => %d.%d
            [options:protected] => Array
                (
                )

        )

)
