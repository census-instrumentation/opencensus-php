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
use OpenCensus\Trace\Status;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class SpanTest extends TestCase
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

    public function testCanAddAttribute()
    {
        $span = new Span();
        $span->addAttribute('foo', 'bar');

        $attributes = $span->spanData()->attributes();
        $this->assertArrayHasKey('foo', $attributes);
        $this->assertEquals('bar', $attributes['foo']);
    }

    public function testNoAttributes()
    {
        $span = new Span();

        $this->assertEmpty($span->spanData()->attributes());
    }

    public function testEmptyAttributes()
    {
        $span = new Span(['attributes' => []]);

        $this->assertEmpty($span->spanData()->attributes());
    }

    public function testGeneratesDefaultSpanName()
    {
        $span = new Span();

        $this->assertStringStartsWith('app', $span->spanData()->name());
    }

    public function testReadsName()
    {
        $span = new Span(['name' => 'myspan']);

        $this->assertEquals('myspan', $span->spanData()->name());
    }

    public function testStartFormat()
    {
        $span = new Span();
        $span->setStartTime();

        $this->assertInstanceOf(\DateTimeInterface::class, $span->spanData()->startTime());
    }

    public function testFinishFormat()
    {
        $span = new Span();
        $span->setEndTime();

        $this->assertInstanceOf(\DateTimeInterface::class, $span->spanData()->endTime());
    }

    public function testGeneratesBacktrace()
    {
        $span = new Span();

        $stackTrace = $span->spanData()->stackTrace();
        $this->assertInternalType('array', $stackTrace);
        $this->assertNotEmpty($stackTrace);
        $stackframe = $stackTrace[0];
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

        $stackTrace = $span->spanData()->stackTrace();
        $this->assertCount(1, $stackTrace);
        $stackframe = $stackTrace[0];
        $this->assertEquals('asdf', $stackframe['function']);
        $this->assertEquals('Foo', $stackframe['class']);
    }

    public function testDefaultStatus()
    {
        $span = new Span();

        $this->assertNull($span->spanData()->status());
    }

    public function testConstructingWithStatus()
    {
        $status = new Status(200, 'OK');
        $span = new Span(['status' => $status]);

        $status = $span->spanData()->status();
        $this->assertInstanceOf(Status::class, $status);
        $this->assertEquals($status, $status);
    }

    public function testSettingStatus()
    {
        $span = new Span([
            'startTime' => 0,
            'endTime' => 0
        ]);
        $span->setStatus(500, 'A server error occurred');

        $status = $span->spanData()->status();
        $this->assertInstanceOf(Status::class, $status);
        $this->assertEquals(500, $status->code());
        $this->assertEquals('A server error occurred', $status->message());
    }

    /**
     * @dataProvider timestampFields
     */
    public function testCanFormatTimestamps($field, $timestamp, $expected)
    {
        $data = [$field => $timestamp];
        $data += [
            'startTime' => 0,
            'endTime' => 0
        ];
        $span = new Span($data);
        $spanData = $span->spanData();
        $date = call_user_func([$spanData, $field]);
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
