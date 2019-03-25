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
        $context = $propagator->extract(['HTTP_X_CLOUD_TRACE_CONTEXT' => $header]);
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
        $propagator = new HttpHeaderPropagator(new CloudTraceFormatter(), 'HTTP_TRACE_CONTEXT');
        $context = $propagator->extract(['HTTP_TRACE_CONTEXT' => $header]);
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
        $propagator->inject($context, $output);
        $this->assertArrayHasKey('X-CLOUD-TRACE-CONTEXT', $output);
        $this->assertEquals($header, $output['X-CLOUD-TRACE-CONTEXT']);
    }

    /**
     * @dataProvider traceMetadata
     */
    public function testInjectCustomKey($traceId, $spanId, $enabled, $header)
    {
        $propagator = new HttpHeaderPropagator(new CloudTraceFormatter(), 'HTTP_TRACE_CONTEXT');
        $context = new SpanContext($traceId, $spanId, $enabled);
        $propagator->inject($context, $output);
        $this->assertArrayHasKey('TRACE-CONTEXT', $output);
        $this->assertEquals($header, $output['TRACE-CONTEXT']);
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
