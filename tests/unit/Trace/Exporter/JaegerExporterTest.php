<?php
/**
 * Copyright 2018 OpenCensus Authors
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

require_once __DIR__ . '/mock_error_log.php';

use OpenCensus\Trace\Exporter\JaegerExporter;
use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Tracer\TracerInterface;
use OpenCensus\Trace\Tracer\ContextTracer;
use OpenCensus\Trace\Span as OCSpan;
use Prophecy\Argument;
use Jaeger\Thrift\Span;
use Jaeger\Thrift\Agent\AgentIf;

/**
 * @group trace
 */
class JaegerExporterTest extends \PHPUnit_Framework_TestCase
{
    private $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->prophesize(AgentIf::class);
    }

    public function testFormatsTrace()
    {
        $reporter = new JaegerExporter('test-agent');
        $spanData = new OCSpan([
            'name' => 'span-name',
            'spanId' => 'aaa',
            'startTime' => new \DateTime(),
            'endTime' => new \DateTime()
        ]);
        $span = $reporter->convertSpan($spanData);
        $this->assertInstanceOf(Span::class, $span);
        $this->assertInternalType('string', $span->operationName);
        $this->assertInternalType('int', $span->spanId);
        $this->assertInternalType('int', $span->startTime);
        $this->assertInternalType('int', $span->duration);

        $this->assertEquals('span-name', $span->operationName);
        $this->assertEquals(2730, $span->spanId);
    }

    public function testEmptyTrace()
    {
        $tracer = $this->prophesize(TracerInterface::class);
        $tracer->spans()->willReturn([])->shouldBeCalled();

        $reporter = new JaegerExporter('test-agent', ['client' => $this->client->reveal()]);
        $this->assertFalse($reporter->report($tracer->reveal()));
    }
}
