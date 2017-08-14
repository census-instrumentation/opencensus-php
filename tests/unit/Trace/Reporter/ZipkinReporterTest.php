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

namespace OpenCensus\Tests\Unit\Trace\Reporter;

use OpenCensus\Trace\Reporter\ZipkinReporter;
use OpenCensus\Trace\TraceContext;
use OpenCensus\Trace\TraceSpan;
use OpenCensus\Trace\Tracer\TracerInterface;
use Prophecy\Argument;

/**
 * @group trace
 */
class ZipkinReporterTest extends \PHPUnit_Framework_TestCase
{
    private $tracer;

    public function setUp()
    {
        $this->tracer = $this->prophesize(TracerInterface::class);
    }

    /**
     * http://zipkin.io/zipkin-api/#/paths/%252Fspans/post
     */
    public function testFormatsTrace()
    {
        $spans = [
            new TraceSpan([
                'name' => 'span',
                'startTime' => microtime(true),
                'endTime' => microtime(true) + 10
            ])
        ];
        $this->tracer->context()->willReturn(new TraceContext());
        $this->tracer->spans()->willReturn($spans);

        $reporter = new ZipkinReporter('myapp', 'localhost', 9411);

        $data = $reporter->convertSpans($this->tracer->reveal());

        $this->assertInternalType('array', $data);
        foreach ($data as $span) {
            $this->assertRegExp('/[0-9a-z]{16}/', $span['id']);
            $this->assertRegExp('/[0-9a-z]{32}/', $span['traceId']);
            $this->assertInternalType('string', $span['name']);
            $this->assertInternalType('int', $span['timestamp']);
            $this->assertInternalType('int', $span['duration']);
            $this->assertInternalType('array', $span['annotations']);
            foreach ($span['annotations'] as $annotation) {
                $this->assertInternalType('array', $annotation['endpoint']);
                $this->assertInternalType('string', $annotation['endpoint']['ipv4']);
                $this->assertInternalType('int', $annotation['endpoint']['port']);
                $this->assertInternalType('string', $annotation['endpoint']['serviceName']);
                $this->assertInternalType('int', $annotation['timestamp']);
                $this->assertInternalType('string', $annotation['value']);
            }
            $this->assertInternalType('array', $span['binaryAnnotations']);
            foreach ($span['binaryAnnotations'] as $annotation) {
                $this->assertInternalType('string', $annotation['key']);
                $this->assertInternalType('string', $annotation['value']);
            }
        }
    }
}