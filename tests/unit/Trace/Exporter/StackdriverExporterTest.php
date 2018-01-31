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
use OpenCensus\Trace\Span as OpenCensusSpan;
use Prophecy\Argument;
use Google\Cloud\Trace\Trace;
use Google\Cloud\Trace\Span;
use Google\Cloud\Trace\TraceClient;

/**
 * @group trace
 */
class StackdriverExporterTest extends \PHPUnit_Framework_TestCase
{
    private $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->prophesize(TraceClient::class);
    }

    public function testFormatsTrace()
    {
        $tracer = new ContextTracer(new SpanContext('testtraceid'));
        $tracer->inSpan(['name' => 'main'], function () use ($tracer) {
            $tracer->inSpan(['name' => 'span1'], 'usleep', [10]);
            $tracer->inSpan(['name' => 'span2'], 'usleep', [20]);
        });

        $reporter = new StackdriverExporter(['client' => $this->client->reveal()]);
        $spans = $reporter->convertSpans($tracer);

        $this->assertCount(3, $spans);
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
        $tracer = new ContextTracer(new SpanContext('testtraceid'));
        $tracer->inSpan(['name' => 'main'], function () {});
        $this->client->insert(Argument::any())->willThrow(
            new \Exception('error_log test')
        );
        $trace = $this->prophesize(Trace::class);
        $trace->setSpans(Argument::any())->shouldBeCalled();
        $this->client->trace(Argument::any())->willReturn($trace->reveal());
        $reporter = new StackdriverExporter(
            ['client' => $this->client->reveal()]
        );
        $this->expectOutputString(
            'Reporting the Trace data failed: error_log test'
        );
        $this->assertFalse($reporter->report($tracer));
    }

    /**
     * @dataProvider attributeHeaders
     */
    public function testParsesDefaultAttributes($headerKey, $headerValue, $expectedAttributeKey, $expectedAttributeValue)
    {
        $tracer = new ContextTracer(new SpanContext('testtraceid'));
        $tracer->inSpan(['name' => 'main'], function () {});

        $reporter = new StackdriverExporter(['client' => $this->client->reveal()]);
        $reporter->processSpans($tracer, [$headerKey => $headerValue]);
        $spans = $tracer->spans();
        $attributes = $spans[0]->attributes();
        $this->assertArrayHasKey($expectedAttributeKey, $attributes);
        $this->assertEquals($expectedAttributeValue, $attributes[$expectedAttributeKey]);
    }

    public function attributeHeaders()
    {
        return [
            ['REQUEST_URI', '/foobar', '/http/url', '/foobar'],
            ['REQUEST_METHOD', 'PUT', '/http/method', 'PUT'],
            ['SERVER_PROTOCOL', 'https', '/http/client_protocol', 'https'],
            ['HTTP_HOST', 'foo.example.com', '/http/host', 'foo.example.com'],
            ['SERVER_NAME', 'foo.example.com', '/http/host', 'foo.example.com'],
            ['GAE_SERVICE', 'test-app', 'g.co/gae/app/module', 'test-app'],
            ['GAE_VERSION', 't12345', 'g.co/gae/app/module_version', 't12345'],
            ['HTTP_X_APPENGINE_CITY', 'kirkland', '/http/client_city', 'kirkland'],
            ['HTTP_X_APPENGINE_REGION', 'wa', '/http/client_region', 'wa'],
            ['HTTP_X_APPENGINE_COUNTRY', 'US', '/http/client_country', 'US']
        ];
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
        $tracer = new ContextTracer(new SpanContext('testtraceid'));
        $tracer->inSpan(['stackTrace' => $stackTrace], function () {});

        $reporter = new StackdriverExporter(['client' => $this->client->reveal()]);
        $spans = $reporter->convertSpans($tracer);

        $data = $spans[0]->jsonSerialize();
        $this->assertArrayHasKey('stackTrace', $data);
    }

    public function testEmptyTrace()
    {
        $tracer = new ContextTracer(new SpanContext('testtraceid'));

        $reporter = new StackdriverExporter(['client' => $this->client->reveal()]);
        $this->assertFalse($reporter->report($tracer));
    }
}
