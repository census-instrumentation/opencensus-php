--TEST--
OpenCensus Trace: Bug 001: Attributes incorrect GC count yields garbage data.
--FILE--
<?php

function myEcho ($var) {
    echo $var . PHP_EOL;
}

function execute() {
    $str = "Hello World!";
    myEcho($str);
}

function allocate_strings($count) {
    $foo = "asdf";
    for ($i = 0; $i < $count; $i++) {
        $foo .= ",$i";
    }
}

function inspect() {
    $span = opencensus_trace_list()[0];
    print_r($span->attributes());
}

// 1: Sanity test a simple profile run
opencensus_trace_function("myEcho", function ($x) {
    return ['name' => 'foo', 'startTime' => 0.1, 'attributes' => ['text' => $x]];
});

execute();
execute();

inspect();
inspect();

?>
--EXPECT--
Hello World!
Hello World!
Array
(
    [text] => Hello World!
)
Array
(
    [text] => Hello World!
)
