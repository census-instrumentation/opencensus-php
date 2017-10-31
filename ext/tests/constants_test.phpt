--TEST--
OpenCensus Trace: Test Constants Defined
--FILE--
<?php

var_dump(OpenCensus\Trace\Ext\Span::SPAN_KIND_UNKNOWN);
var_dump(OpenCensus\Trace\Ext\Span::SPAN_KIND_CLIENT);
var_dump(OpenCensus\Trace\Ext\Span::SPAN_KIND_SERVER);
var_dump(OpenCensus\Trace\Ext\Span::SPAN_KIND_PRODUCER);
var_dump(OpenCensus\Trace\Ext\Span::SPAN_KIND_CONSUMER);

?>
--EXPECT--
int(0)
int(1)
int(2)
int(3)
int(4)
