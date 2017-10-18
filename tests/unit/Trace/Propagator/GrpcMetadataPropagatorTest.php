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
use OpenCensus\Trace\Propagator\BinaryFormatter;
use OpenCensus\Trace\Propagator\GrpcMetadataPropagator;

/**
 * @group trace
 */
class GrpcMetadataPropagatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider traceMetadata
     */
    public function testExtract($traceId, $spanId, $enabled, $hex)
    {
        $propagator = new GrpcMetadataPropagator();
        $context = $propagator->extract(['grpc-trace-bin' => hex2bin($hex)]);
        $this->assertEquals($traceId, $context->traceId());
        $this->assertEquals($spanId, $context->spanId());
        $this->assertEquals($enabled, $context->enabled());
        $this->assertTrue($context->fromHeader());
    }

    /**
     * @dataProvider traceMetadata
     */
    public function testExtractCustomKey($traceId, $spanId, $enabled, $hex)
    {
        $propagator = new GrpcMetadataPropagator(new BinaryFormatter(), 'foobar');
        $context = $propagator->extract(['foobar' => hex2bin($hex)]);
        $this->assertEquals($traceId, $context->traceId());
        $this->assertEquals($spanId, $context->spanId());
        $this->assertEquals($enabled, $context->enabled());
        $this->assertTrue($context->fromHeader());
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
            ['123456789012345678901234567890ab', '00000000000004d2', false, '00' . '00123456789012345678901234567890ab' . '01' . '00000000000004d2' . '02' . '00'],
            ['123456789012345678901234567890ab', '00000000000004d2', true,  '00' . '00123456789012345678901234567890ab' . '01' . '00000000000004d2' . '02' . '01'],
            ['123456789012345678901234567890ab', null, false, '00' . '00123456789012345678901234567890ab' . '01' . '0000000000000000' . '02' . '00'],
            ['123456789012345678901234567890ab', null, true,  '00' . '00123456789012345678901234567890ab' . '01' . '0000000000000000' . '02' . '01']
        ];
    }
}
