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
Fatal error: Uncaught Error: Call to undefined method Bar::bar() in %s
Stack trace:
#%d %s
#%d %s
  thrown in %s on line %d
