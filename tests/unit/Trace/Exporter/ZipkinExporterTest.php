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

namespace OpenCensus\Tests\Unit\Trace\Exporter;

use OpenCensus\Core\Context;
use OpenCensus\Trace\Exporter\ZipkinExporter;
use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\Tracer\TracerInterface;
use OpenCensus\Trace\Tracer\ContextTracer;
use Prophecy\Argument;

/**
 * @group trace
 */
class ZipkinExporterTest extends \PHPUnit_Framework_TestCase
{
    private $tracer;

    public function setUp()
    {
        $this->tracer = $this->prophesize(TracerInterface::class);
        Context::reset();
    }

    /**
     * http://zipkin.io/zipkin-api/#/paths/%252Fspans/post
     */
    public function testFormatsTrace()
    {
        $spans = [
            new Span([
                'name' => 'span',
                'startTime' => microtime(true),
                'endTime' => microtime(true) + 10
            ])
        ];

        $this->tracer->spans()->willReturn($spans);

        $reporter = new ZipkinExporter('myapp', 'localhost', 9411);

        $data = $reporter->convertSpans($this->tracer->reveal());

        $this->assertInternalType('array', $data);
        foreach ($data as $span) {
            $this->assertRegExp('/[0-9a-z]{16}/', $span['id']);
            $this->assertRegExp('/[0-9a-z]{32}/', $span['traceId']);
            $this->assertInternalType('string', $span['name']);
            $this->assertInternalType('int', $span['timestamp']);
            $this->assertInternalType('int', $span['duration']);

            $this->assertInternalType('array', $span['tags']);
            foreach ($span['tags'] as $key => $value) {
                $this->assertInternalType('string', $key);
                $this->assertInternalType('string', $value);
            }
            $this->assertFalse($span['shared']);
            $this->assertFalse($span['debug']);
        }
    }

    public function testSpanKind()
    {
        $tracer = new ContextTracer(new SpanContext('testtraceid'));
        $tracer->inSpan(['name' => 'main'], function () use ($tracer) {
            $tracer->inSpan(['name' => 'span1', 'kind' => Span::SPAN_KIND_CLIENT], 'usleep', [1]);
            $tracer->inSpan(['name' => 'span2', 'kind' => Span::SPAN_KIND_SERVER], 'usleep', [1]);
            $tracer->inSpan(['name' => 'span3', 'kind' => Span::SPAN_KIND_PRODUCER], 'usleep', [1]);
            $tracer->inSpan(['name' => 'span4', 'kind' => Span::SPAN_KIND_CONSUMER], 'usleep', [1]);
        });

        $reporter = new ZipkinExporter('myapp', 'localhost', 9411);
        $spans = $reporter->convertSpans($tracer);

        $annotationValue = function ($annotation) {
            return $annotation['value'];
        };

        $this->assertCount(5, $spans);
        $this->assertFalse(array_key_exists('kind', $spans[0]));
        $this->assertEquals('CLIENT', $spans[1]['kind']);
        $this->assertEquals('SERVER', $spans[2]['kind']);
        $this->assertEquals('PRODUCER', $spans[3]['kind']);
        $this->assertEquals('CONSUMER', $spans[4]['kind']);
    }

    public function testSpanDebug()
    {
        $spanContext = new SpanContext('testtraceid');
        $spanContext->attach();

        $tracer = new ContextTracer();
        $tracer->inSpan(['name' => 'main'], function () {});

        $reporter = new ZipkinExporter('myapp', 'localhost', 9411);
        $spans = $reporter->convertSpans($tracer, [
            'HTTP_X_B3_FLAGS' => '1'
        ]);

        $this->assertCount(1, $spans);
        $this->assertTrue($spans[0]['debug']);
    }

    public function testSpanShared()
    {
        $spanContext = new SpanContext('testtraceid', 12345);
        $spanContext->attach();

        $tracer = new ContextTracer();
        $tracer->inSpan(['name' => 'main'], function () {});

        $reporter = new ZipkinExporter('myapp', 'localhost', 9411);
        $spans = $reporter->convertSpans($tracer);

        $this->assertCount(1, $spans);
        $this->assertTrue($spans[0]['shared']);
    }
}
