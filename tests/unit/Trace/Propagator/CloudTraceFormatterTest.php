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

use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Propagator\CloudTraceFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class CloudTraceFormatterTest extends TestCase
{
    /**
     * @dataProvider traceHeaders
     */
    public function testParseContext($traceId, $spanId, $enabled, $header)
    {
        $formatter = new CloudTraceFormatter();
        $context = $formatter->deserialize($header);
        $this->assertEquals($traceId, $context->traceId());
        $this->assertEquals($spanId, $context->spanId());
        $this->assertEquals($enabled, $context->enabled());
        $this->assertTrue($context->fromHeader());
    }

    /**
     * @dataProvider upperTraceHeaders
     */
    public function testParseUppercaseHexContext($traceId, $spanId, $enabled, $header)
    {
        $formatter = new CloudTraceFormatter();
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
        $formatter = new CloudTraceFormatter();
        $context = new SpanContext($traceId, $spanId, $enabled);
        $this->assertEquals($expected, $formatter->serialize($context));
    }

    public function traceHeaders()
    {
        return [
            ['123456789012345678901234567890ab', '4d2', false, '123456789012345678901234567890ab/1234;o=0'],
            ['123456789012345678901234567890ab', '4d2', true,  '123456789012345678901234567890ab/1234;o=1'],
            ['123456789012345678901234567890ab', '4d2', null,  '123456789012345678901234567890ab/1234'],
            ['123456789012345678901234567890ab', null, false,  '123456789012345678901234567890ab;o=0'],
            ['123456789012345678901234567890ab', null, true,   '123456789012345678901234567890ab;o=1'],
            ['123456789012345678901234567890ab', null, null,   '123456789012345678901234567890ab'],
        ];
    }

    public function upperTraceHeaders()
    {
        return [
            ['123456789012345678901234567890ab', '4d2', false, '123456789012345678901234567890AB/1234;o=0'],
            ['123456789012345678901234567890ab', '4d2', true,  '123456789012345678901234567890AB/1234;o=1'],
            ['123456789012345678901234567890ab', '4d2', null,  '123456789012345678901234567890AB/1234'],
            ['123456789012345678901234567890ab', null, false,  '123456789012345678901234567890AB;o=0'],
            ['123456789012345678901234567890ab', null, true,   '123456789012345678901234567890AB;o=1'],
            ['123456789012345678901234567890ab', null, null,   '123456789012345678901234567890AB'],
        ];
    }
}
