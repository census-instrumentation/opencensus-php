--TEST--
OpenCensus Trace: Providing integer as name
--FILE--
<?php

class CastableObject
{
    private $value = 'my value';

    public function __toString()
    {
        return $this->value;
    }
}

$obj = new CastableObject();
opencensus_trace_begin('foo', ['name' => $obj]);
opencensus_trace_finish();
$spans = opencensus_trace_list();

echo 'Number of spans: ' . count($spans) . PHP_EOL;
$span = $spans[0];
var_dump($span->name());

?>
--EXPECTF--
Number of spans: 1
string(8) "my value"