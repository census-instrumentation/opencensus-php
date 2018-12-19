<?php

namespace
{
    function bar()
    {
      return 1;
    }

    function foo($x)
    {
      $sum = 0;
      for ($idx = 0; $idx < $x; $idx++) {
         $sum += bar();
      }
      return $sum;
    }

    function add($a, $b)
    {
        return $a + $b;
    }

    class Foo
    {
        public $val;

        public function __construct($val = null)
        {
            $this->val = $val;
        }

        public static function asdf()
        {
            return 'qwer';
        }

        public static function plus($a, $b)
        {
            return $a + $b;
        }

        public function bar()
        {
            return 1;
        }

        public function add($a, $b)
        {
            return $a + $b;
        }

        public function context()
        {
            return opencensus_trace_context();
        }
    }
}

namespace Illuminate\Database\Eloquent
{
    // fake class with method we know is traced
    class Model
    {
        public function delete() {

        }
    }
}
