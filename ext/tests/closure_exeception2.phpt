--TEST--
OpenCensus Trace: Closure exception should not segfault
--FILE--
<?php

class Bar {
    public function getValue(){
        return 'bar';
    }
}

class Foo {
    public function bar($x)
    {
        return $x;
    }
}

opencensus_trace_method('Foo', 'bar', function ($scope, $x) {
    return ['name' => 'foo', 'startTime' => 0.1, 'attributes' => ['asdf' => $x->bar(), 'zxcv' => 'jkl;']];
});
$bar = new Bar();
$foo = new Foo();
$foo->bar($bar);
?>
--EXPECTF--
Warning: main(): Exception in trace callback in %s on line %d
