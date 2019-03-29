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

use OpenCensus\Trace\Exporter\NullExporter;
use OpenCensus\Trace\Sampler\AlwaysSampleSampler;
use OpenCensus\Trace\Sampler\NeverSampleSampler;
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Tracer\NullTracer;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class TracerTest extends TestCase
{
    private $exporter;

    public function setUp()
    {
        $this->exporter = new NullExporter();
    }

    public function testForceDisabled()
    {
        $rt = Tracer::start($this->exporter, [
            'sampler' => new NeverSampleSampler(),
            'skipReporting' => true
        ]);
        $tracer = $rt->tracer();

        $this->assertFalse($tracer->spanContext()->enabled());
        $this->assertInstanceOf(NullTracer::class, $tracer);
    }

    public function testForceEnabled()
    {
        $rt = Tracer::start($this->exporter, [
            'sampler' => new AlwaysSampleSampler(),
            'skipReporting' => true
        ]);
        $tracer = $rt->tracer();

        $this->assertTrue($tracer->spanContext()->enabled());
    }

    public function testGlobalAttributes()
    {
        $rt = Tracer::start($this->exporter, [
            'sampler' => new AlwaysSampleSampler(),
            'skipReporting' => true
        ]);
        Tracer::addAttribute('foo', 'bar');
        $spans = $rt->tracer()->spans();
        $span = $spans[count($spans) - 1];
        $this->assertEquals(['foo' => 'bar'], $span->attributes());
    }

    public function testGlobalAnnotation()
    {
        $rt = Tracer::start($this->exporter, [
            'sampler' => new AlwaysSampleSampler(),
            'skipReporting' => true
        ]);
        Tracer::addAnnotation('some description', [
            'attributes' => [
                'foo' => 'bar'
            ]
        ]);
        $spans = $rt->tracer()->spans();
        $span = $spans[count($spans) - 1];

        $this->assertCount(1, $span->timeEvents());
        $annotation = $span->timeEvents()[0];
        $this->assertEquals('some description', $annotation->description());
        $this->assertEquals(['foo' => 'bar'], $annotation->attributes());
    }

    public function testGlobalMessageEvent()
    {
        $rt = Tracer::start($this->exporter, [
            'sampler' => new AlwaysSampleSampler(),
            'skipReporting' => true
        ]);
        Tracer::addMessageEvent('SENT', 'message-id');
        $spans = $rt->tracer()->spans();
        $span = $spans[count($spans) - 1];

        $this->assertCount(1, $span->timeEvents());
        $messageEvent = $span->timeEvents()[0];
        $this->assertEquals('SENT', $messageEvent->type());
        $this->assertEquals('message-id', $messageEvent->id());
    }

    public function testGlobalLink()
    {
        $rt = Tracer::start($this->exporter, [
            'sampler' => new AlwaysSampleSampler(),
            'skipReporting' => true
        ]);
        Tracer::addLink('trace-id', 'span-id');
        $spans = $rt->tracer()->spans();
        $span = $spans[count($spans) - 1];

        $this->assertCount(1, $span->links());
        $link = $span->links()[0];
        $this->assertEquals('trace-id', $link->traceId());
        $this->assertEquals('span-id', $link->spanId());
    }
}
