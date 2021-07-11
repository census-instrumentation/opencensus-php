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
use OpenCensus\Trace\Propagator\HttpHeaderPropagator;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class HttpHeaderPropagatorTest extends TestCase
{
    /**
     * @dataProvider traceMetadata
     */
    public function testExtract($traceId, $spanId, $enabled, $header)
    {
        $propagator = new HttpHeaderPropagator();
        $context = $propagator->extract(['X-Cloud-Trace-Context' => $header]);
        $this->assertEquals($traceId, $context->traceId());
        $this->assertEquals($spanId, $context->spanId());
        $this->assertEquals($enabled, $context->enabled());
        $this->assertTrue($context->fromHeader());
    }

    /**
     * @dataProvider traceMetadata
     */
    public function testExtractCustomKey($traceId, $spanId, $enabled, $header)
    {
        $propagator = new HttpHeaderPropagator(new CloudTraceFormatter(), 'Trace-Context');
        $context = $propagator->extract(['Trace-Context' => $header]);
        $this->assertEquals($traceId, $context->traceId());
        $this->assertEquals($spanId, $context->spanId());
        $this->assertEquals($enabled, $context->enabled());
        $this->assertTrue($context->fromHeader());
    }

    /**
     * @dataProvider traceMetadata
     */
    public function testInject($traceId, $spanId, $enabled, $header)
    {
        $propagator = new HttpHeaderPropagator();
        $context = new SpanContext($traceId, $spanId, $enabled);
        $output = $propagator->inject($context, []);
        $this->assertArrayHasKey('X-Cloud-Trace-Context', $output);
        $this->assertEquals($header, $output['X-Cloud-Trace-Context']);
    }

    /**
     * @dataProvider traceMetadata
     */
    public function testInjectCustomKey($traceId, $spanId, $enabled, $header)
    {
        $propagator = new HttpHeaderPropagator(new CloudTraceFormatter(), 'Trace-Context');
        $context = new SpanContext($traceId, $spanId, $enabled);
        $output = $propagator->inject($context, []);
        $this->assertArrayHasKey('Trace-Context', $output);
        $this->assertEquals($header, $output['Trace-Context']);
    }

    /**
     * Data provider for testing serialization and serialization. We use hex strings here to make
     * the test human readable to see that our test data adheres to the spec.
     * See https://github.com/census-instrumentation/opencensus-specs/blob/master/encodings/BinaryEncoding.md
     * for the encoding specification.
     */
    public function traceMetadata()
    {
        return [
            ['123456789012345678901234567890ab', '4d2', false, '123456789012345678901234567890ab/1234;o=0'],
            ['123456789012345678901234567890ab', '4d2', true,  '123456789012345678901234567890ab/1234;o=1'],
            ['123456789012345678901234567890ab', null, false,   '123456789012345678901234567890ab;o=0'],
            ['123456789012345678901234567890ab', null, true,    '123456789012345678901234567890ab;o=1'],
        ];
    }
}
