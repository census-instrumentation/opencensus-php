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

require_once __DIR__ . '/mock_error_log.php';

use OpenCensus\Trace\Exporter\StackdriverExporter;
use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Tracer\TracerInterface;
use OpenCensus\Trace\Tracer\ContextTracer;
use OpenCensus\Trace\Span as OCSpan;
use Prophecy\Argument;
use Google\Cloud\Trace\Trace;
use Google\Cloud\Trace\Span;
use Google\Cloud\Trace\TraceClient;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class StackdriverExporterTest extends TestCase
{
    /**
     * @var TraceClient
     */
    private $client;

    /**
     * @var SpanData[]
     */
    private $spans;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->prophesize(TraceClient::class);

        $this->spans = array_map(function ($span) {
            return $span->spanData();
        }, [
            new OCSpan([
                'name' => 'span',
                'startTime' => microtime(true),
                'endTime' => microtime(true) + 10
            ])
        ]);
    }

    public function testFormatsTrace()
    {
        $exporter = new StackdriverExporter(['client' => $this->client->reveal()]);
        $spans = $exporter->convertSpans($this->spans);

        foreach ($spans as $span) {
            $this->assertInstanceOf(Span::class, $span);
            $this->assertInternalType('string', $span->name());
            $this->assertInternalType('string', $span->spanId());
            $this->assertRegExp('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{9}Z/', $span->jsonSerialize()['startTime']);
            $this->assertRegExp('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{9}Z/', $span->jsonSerialize()['endTime']);
        }
    }

    public function testReportWithAnExceptionErrorLog()
    {
        $this->client->insert(Argument::any())->willThrow(
            new \Exception('error_log test')
        );
        $trace = $this->prophesize(Trace::class);
        $trace->setSpans(Argument::any())->shouldBeCalled();
        $this->client->trace(Argument::any())->willReturn($trace->reveal());
        $exporter = new StackdriverExporter(
            ['client' => $this->client->reveal()]
        );
        $this->expectOutputString(
            'Reporting the Trace data failed: error_log test'
        );
        $this->assertFalse($exporter->export($this->spans));
    }

    public function testStacktrace()
    {
        $stackTrace = [
            [
                'file' => '/path/to/file.php',
                'class' => 'Foo',
                'line' => 1234,
                'function' => 'asdf',
                'type' => '::'
            ]
        ];
        $span = new OCSpan([
            'stackTrace' => $stackTrace
        ]);
        $span->setStartTime();
        $span->setEndTime();

        $exporter = new StackdriverExporter(['client' => $this->client->reveal()]);
        $spans = $exporter->convertSpans([$span->spanData()]);

        $data = $spans[0]->jsonSerialize();
        $this->assertArrayHasKey('stackTrace', $data);
    }

    public function testEmptyTrace()
    {
        $exporter = new StackdriverExporter(['client' => $this->client->reveal()]);
        $this->assertFalse($exporter->export([]));
    }

    /**
     * @dataProvider attributesToTest
     */
    public function testMapsAttributes($key, $value, $expectedAttributeKey, $expectedAttributeValue)
    {
        $tracer = new ContextTracer(new SpanContext('testtraceid'));
        $tracer->inSpan([
            'name' => 'span',
            'attributes' => [
                $key => $value
            ]
        ], function () {});

        $span = new OCSpan([
            'attributes' => [
                $key => $value
            ]
        ]);
        $span->setStartTime();
        $span->setEndTime();

        $exporter = new StackdriverExporter(['client' => $this->client->reveal()]);
        $spans = $exporter->convertSpans([$span->spanData()]);
        $this->assertCount(1, $spans);
        $span = $spans[0];

        $attributes = $span->jsonSerialize()['attributes'];
        $this->assertArrayHasKey($expectedAttributeKey, $attributes);
        $this->assertEquals($expectedAttributeValue, $attributes[$expectedAttributeKey]);
    }

    public function attributesToTest()
    {
        return [
            ['http.host', 'foo.example.com', '/http/host', 'foo.example.com'],
            ['http.port', '80', '/http/port', '80'],
            ['http.path', '/foobar', '/http/url', '/foobar'],
            ['http.method', 'PUT', '/http/method', 'PUT'],
            ['http.user_agent', 'user agent', '/http/user_agent', 'user agent']
        ];
    }

    public function testReportsVersionAttribute()
    {
        $trace = $this->prophesize(Trace::class);
        $trace->setSpans(Argument::that(function ($spans) {
            $this->assertCount(1, $spans);
            $attributes = $spans[0]->jsonSerialize()['attributes'];
            $this->assertArrayHasKey('g.co/agent', $attributes);
            $this->assertRegexp('/\d+\.\d+\.\d+/', $attributes['g.co/agent']);
            return true;
        }))->shouldBeCalled();
        $this->client->trace('aaa')->willReturn($trace->reveal());
        $this->client->insert(Argument::type(Trace::class))
            ->willReturn(true)->shouldBeCalled();

        $span = new OCSpan([
            'traceId' => 'aaa',
            'attributes' => [
                $key => $value
            ]
        ]);
        $span->setStartTime();
        $span->setEndTime();

        $exporter = new StackdriverExporter(['client' => $this->client->reveal()]);
        $this->assertTrue($exporter->export([$span->spanData()]));
    }
}
