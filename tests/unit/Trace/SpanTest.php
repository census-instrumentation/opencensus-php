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

namespace OpenCensus\Tests\Unit\Trace;

use OpenCensus\Trace\Span;

/**
 * @group trace
 */
class SpanTest extends \PHPUnit_Framework_TestCase
{
    const EXPECTED_TIMESTAMP_FORMAT = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{9}Z$/';

    public function testGeneratesDefaultSpanId()
    {
        $span = new Span();

        $this->assertNotEmpty($span->spanId());
    }

    public function testReadsSpanId()
    {
        $span = new Span(['spanId' => '1234']);

        $this->assertEquals('1234', $span->spanId());
    }

    public function testReadsAttributes()
    {
        $span = new Span(['attributes' => ['foo' => 'bar']]);

        $attributes = $span->attributes();
        $this->assertArrayHasKey('foo', $attributes);
        $this->assertEquals('bar', $attributes['foo']);
    }

    public function testCanAddAttribute()
    {
        $span = new Span();
        $span->addAttribute('foo', 'bar');

        $attributes = $span->attributes();
        $this->assertArrayHasKey('foo', $attributes);
        $this->assertEquals('bar', $attributes['foo']);
    }

    public function testNoAttributes()
    {
        $span = new Span();

        $this->assertEmpty($span->attributes());
    }

    public function testEmptyAttributes()
    {
        $span = new Span(['attributes' => []]);

        $this->assertEquals([], $span->attributes());
    }

    public function testGeneratesDefaultSpanName()
    {
        $span = new Span();

        $this->assertStringStartsWith('app', $span->name());
    }

    public function testReadsName()
    {
        $span = new Span(['name' => 'myspan']);

        $this->assertEquals('myspan', $span->name());
    }

    public function testStartFormat()
    {
        $span = new Span();
        $span->setStartTime();

        $this->assertInstanceOf(\DateTimeInterface::class, $span->startTime());
    }

    public function testFinishFormat()
    {
        $span = new Span();
        $span->setEndTime();

        $this->assertInstanceOf(\DateTimeInterface::class, $span->endTime());
    }

    public function testGeneratesBacktrace()
    {
        $span = new Span();

        $this->assertInternalType('array', $span->stackTrace());
        $this->assertTrue(count($span->stackTrace()) > 0);
        $stackframe = $span->stackTrace()[0];
        $this->assertEquals('testGeneratesBacktrace', $stackframe['function']);
        $this->assertEquals(self::class, $stackframe['class']);
    }

    public function testOverrideBacktrace()
    {
        $stackTrace = [
            [
                'class' => 'Foo',
                'line' => 1234,
                'function' => 'asdf',
                'type' => '::'
            ]
        ];
        $span = new Span([
            'stackTrace' => $stackTrace
        ]);

        $this->assertCount(1, $span->stackTrace());
        $stackframe = $span->stackTrace()[0];
        $this->assertEquals('asdf', $stackframe['function']);
        $this->assertEquals('Foo', $stackframe['class']);
    }

    /**
     * @dataProvider timestampFields
     */
    public function testCanFormatTimestamps($field, $timestamp, $expected)
    {
        $span = new Span([$field => $timestamp]);
        $date = call_user_func([$span, $field]);
        $this->assertInstanceOf(\DateTimeInterface::class, $date);
        $this->assertEquals($expected, $date->format('Y-m-d\TH:i:s.u000\Z'));
    }

    public function timestampFields()
    {
        return [
            ['startTime', 1490737410, '2017-03-28T21:43:30.000000000Z'],
            ['startTime', 1490737450.4843, '2017-03-28T21:44:10.484299000Z'],
            ['endTime', 1490737410, '2017-03-28T21:43:30.000000000Z'],
            ['endTime', 1490737450.4843, '2017-03-28T21:44:10.484299000Z'],
        ];
    }
}
