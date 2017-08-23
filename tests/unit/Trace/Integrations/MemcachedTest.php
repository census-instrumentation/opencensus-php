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

namespace OpenCensus\Tests\Unit\Trace\Integrations;

use OpenCensus\Trace\Integrations\Memcached;

/**
 * @group trace
 */
class MemcachedTest extends \PHPUnit_Framework_TestCase
{
    public function testHandleLabelsString()
    {
        $key = 'mykey';
        $memcache = null;

        $spanOptions = Memcached::handleLabels($memcache, $key);
        $expected = [
            'labels' => [
                'key' => 'mykey'
            ]
        ];

        $this->assertEquals($expected, $spanOptions);
    }

    public function testHandleLabelsArray()
    {
        $key = [
            'key1',
            'key2'
        ];
        $memcache = null;

        $spanOptions = Memcached::handleLabels($memcache, $key);
        $expected = [
            'labels' => [
                'key' => 'key1,key2'
            ]
        ];

        $this->assertEquals($expected, $spanOptions);
    }

    public function testHandleLabelsByKeyString()
    {
        $key = 'mykey';
        $memcache = null;
        $serverKey = 'server1';

        $spanOptions = Memcached::handleLabelsByKey($memcache, $serverKey, $key);
        $expected = [
            'labels' => [
                'serverKey' => 'server1',
                'key' => 'mykey'
            ]
        ];

        $this->assertEquals($expected, $spanOptions);
    }

    public function testHandleLabelsByKeyArray()
    {
        $key = [
            'key1',
            'key2'
        ];
        $memcache = null;
        $serverKey = 'server1';

        $spanOptions = Memcached::handleLabelsByKey($memcache, $serverKey, $key);
        $expected = [
            'labels' => [
                'serverKey' => 'server1',
                'key' => 'key1,key2'
            ]
        ];

        $this->assertEquals($expected, $spanOptions);
    }

    public function testHandleCas()
    {
        $memcache = null;
        $casToken = 'token1';
        $key = 'key1';

        $spanOptions = Memcached::handleCas($memcache, $casToken, $key);
        $expected = [
            'labels' => [
                'casToken' => 'token1',
                'key' => 'key1'
            ]
        ];

        $this->assertEquals($expected, $spanOptions);
    }

    public function testHandleCasByKey()
    {
        $memcache = null;
        $casToken = 'token1';
        $serverKey = 'server1';
        $key = 'key1';

        $spanOptions = Memcached::handleCasByKey($memcache, $casToken, $serverKey, $key);
        $expected = [
            'labels' => [
                'casToken' => 'token1',
                'serverKey' => 'server1',
                'key' => 'key1'
            ]
        ];

        $this->assertEquals($expected, $spanOptions);
    }

    public function testHandleSetMulti()
    {
        $memcache = null;
        $items = [
            'foo' => 'bar',
            'asdf' => 'qwer'
        ];

        $spanOptions = Memcached::handleSetMulti($memcache, $items);
        $expected = [
            'labels' => [
                'key' => 'foo,asdf'
            ]
        ];

        $this->assertEquals($expected, $spanOptions);
    }

    public function testHandleSetMultiByKey()
    {
        $memcache = null;
        $serverKey = 'server1';
        $items = [
            'foo' => 'bar',
            'asdf' => 'qwer'
        ];

        $spanOptions = Memcached::handleSetMultiByKey($memcache, $serverKey, $items);
        $expected = [
            'labels' => [
                'serverKey' => 'server1',
                'key' => 'foo,asdf'
            ]
        ];

        $this->assertEquals($expected, $spanOptions);
    }
}
