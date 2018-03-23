<?php
/**
 * Copyright 2017 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCensus\Tests\Unit\Core;

use OpenCensus\Core\Scope;
use PHPUnit\Framework\TestCase;

/**
 * @group core
 */
class ScopeTest extends TestCase
{
    private $data;

    public function setUp()
    {
        $this->data = [];
    }

    public function testExecutesClosure()
    {
        $scope = new Scope(function () {
            array_push($this->data, 'foo');
        });

        $this->assertCount(0, $this->data);
        $scope->close();
        $this->assertCount(1, $this->data);
    }

    public function testExecuteCallable()
    {
        $scope = new Scope([$this, 'myCallback']);

        $this->assertCount(0, $this->data);
        $scope->close();
        $this->assertCount(1, $this->data);
        $this->assertEquals([1], $this->data);
    }

    public function testExecuteCallableArguments()
    {
        $scope = new Scope([$this, 'myCallback'], [2]);

        $this->assertCount(0, $this->data);
        $scope->close();
        $this->assertCount(1, $this->data);
        $this->assertEquals([2], $this->data);
    }

    public function myCallback($value = null)
    {
        $value = $value ? $value : 1;
        array_push($this->data, $value);
    }
}
