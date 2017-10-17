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

namespace OpenCensus\Tests\Unit\Trace\Propagator;

use OpenCensus\Trace\TraceContext;
use OpenCensus\Trace\Propagator\TraceContextFormatter;

/**
 * @group trace
 */
class TraceContextFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider traceHeaders
     */
    public function testParseContext($traceId, $spanId, $enabled, $header)
    {
        $formatter = new TraceContextFormatter();
        $context = $formatter->deserialize($header);
        $this->assertEquals($traceId, $context->traceId());
        $this->assertEquals($spanId, $context->spanId());
        $this->assertEquals($enabled, $context->enabled());
        $this->assertTrue($context->fromHeader());
    }

    /**
     * @dataProvider traceHeaders
     */
    public function testToString($traceId, $spanId, $enabled, $expected)
    {
        $formatter = new TraceContextFormatter();
        $context = new TraceContext($traceId, $spanId, $enabled);
        $this->assertEquals($expected, $formatter->serialize($context));
    }

    public function traceHeaders()
    {
        return [
            ['4bf92f3577b34da6a3ce929d0e0e4736', 67667974448284343, false, '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-00'],
            ['4bf92f3577b34da6a3ce929d0e0e4736', 67667974448284343, true,  '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01'],
            ['4bf92f3577b34da6a3ce929d0e0e4736', 67667974448284343, null,  '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7'],
            ['4bf92f3577b34da6a3ce929d0e0e4736', 67667974448284343, null,  '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7']
        ];
    }
}
