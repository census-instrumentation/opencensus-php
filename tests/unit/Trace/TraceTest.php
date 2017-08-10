<?php
/**
 * Copyright 2017 Google Inc.
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

use OpenCensus\Trace\Trace;
use OpenCensus\Trace\TraceSpan;

/**
 * @group trace
 */
class TraceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dateFormats
     */
    public function testLoadFromArray($date)
    {
        $trace = new Trace(
            '1234abcd',
            [
                [
                    'spanId' => '12345',
                    'kind' => 'SPAN_KIND_UNSPECIFIED',
                    'name' => 'spanname',
                    'startTime' => $date,
                    'endTime' => $date
                ]
            ]
        );
        $this->assertEquals('1234abcd', $trace->traceId());
        $this->assertEquals(1, count($trace->spans()));
        foreach($trace->spans() as $span) {
            $this->assertInstanceOf(TraceSpan::class, $span);
            $this->assertInstanceOf(\DateTimeInterface::class, $span->startTime());
            $this->assertInstanceOf(\DateTimeInterface::class, $span->endTime());
        }
    }

    public function testGeneratesDefaultTraceId()
    {
        $trace = new Trace();
        $this->assertRegExp('/^[0-9a-f]{32}$/', $trace->traceId());
    }

    public function testBuildsSpan()
    {
        $trace = new Trace();
        $span = $trace->span(['name' => 'myspan']);
        $this->assertInstanceOf(TraceSpan::class, $span);
        $this->assertEquals('myspan', $span->name());
    }

    public function testSpecifyingSpansSkipsTraceGetCall()
    {
        $trace = new Trace('1', [['name' => 'main']]);
        $trace->info();
    }

    public function dateFormats()
    {
        return [
            [1490737450.4843],
            [new \DateTime()]
        ];
    }
}
