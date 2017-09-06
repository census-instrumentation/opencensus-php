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

namespace OpenCensus\Tests\Unit\Trace\Reporter;

require_once __DIR__ . '/mock_error_log.php';

use OpenCensus\Trace\Reporter\GoogleCloudReporter;
use OpenCensus\Trace\TraceContext;
use OpenCensus\Trace\Tracer\TracerInterface;
use OpenCensus\Trace\Tracer\ContextTracer;
use OpenCensus\Trace\TraceSpan as OpenCensusTraceSpan;
use Prophecy\Argument;
use Google\Cloud\Trace\Trace;
use Google\Cloud\Trace\TraceSpan;
use Google\Cloud\Trace\TraceClient;

/**
 * @group trace
 */
class GoogleCloudReporterTest extends \PHPUnit_Framework_TestCase
{
    private $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->prophesize(TraceClient::class);
    }

    public function testFormatsTrace()
    {
        $tracer = new ContextTracer(new TraceContext('testtraceid'));
        $tracer->inSpan(['name' => 'main'], function () use ($tracer) {
            $tracer->inSpan(['name' => 'span1'], 'usleep', [10]);
            $tracer->inSpan(['name' => 'span2'], 'usleep', [20]);
        });

        $reporter = new GoogleCloudReporter(['client' => $this->client->reveal()]);
        $spans = $reporter->convertSpans($tracer);

        $this->assertCount(3, $spans);
        foreach ($spans as $span) {
            $this->assertInstanceOf(TraceSpan::class, $span);
            $this->assertInternalType('string', $span->name());
            $this->assertInternalType('int', $span->spanId());
            $this->assertRegExp('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{9}Z/', $span->info()['startTime']);
            $this->assertRegExp('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{9}Z/', $span->info()['endTime']);
        }
    }

    public function testReportWithAnExceptionErrorLog()
    {
        $tracer = new ContextTracer(new TraceContext('testtraceid'));
        $tracer->inSpan(['name' => 'main'], function () {});
        $this->client->insert(Argument::any())->willThrow(
            new \Exception('error_log test')
        );
        $trace = $this->prophesize(Trace::class);
        $trace->setSpans(Argument::any())->shouldBeCalled();
        $this->client->trace(Argument::any())->willReturn($trace->reveal());
        $reporter = new GoogleCloudReporter(
            ['client' => $this->client->reveal()]
        );
        $this->expectOutputString(
            'Reporting the Trace data failed: error_log test'
        );
        $reporter->report($tracer);
    }

    public function testHandlesKind()
    {
        $tracer = new ContextTracer(new TraceContext('testtraceid'));
        $tracer->inSpan(['name' => 'main'], function () use ($tracer) {
            $tracer->inSpan(['name' => 'span1', 'kind' => OpenCensusTraceSpan::SPAN_KIND_CLIENT], 'usleep', [1]);
            $tracer->inSpan(['name' => 'span2', 'kind' => OpenCensusTraceSpan::SPAN_KIND_SERVER], 'usleep', [1]);
            $tracer->inSpan(['name' => 'span3', 'kind' => OpenCensusTraceSpan::SPAN_KIND_PRODUCER], 'usleep', [1]);
            $tracer->inSpan(['name' => 'span4', 'kind' => OpenCensusTraceSpan::SPAN_KIND_CONSUMER], 'usleep', [1]);
        });

        $reporter = new GoogleCloudReporter(['client' => $this->client->reveal()]);
        $spans = $reporter->convertSpans($tracer);

        $this->assertCount(5, $spans);
        $this->assertEquals(TraceSpan::SPAN_KIND_UNSPECIFIED, $spans[0]->info()['kind']);
        $this->assertEquals(TraceSpan::SPAN_KIND_RPC_CLIENT, $spans[1]->info()['kind']);
        $this->assertEquals(TraceSpan::SPAN_KIND_RPC_SERVER, $spans[2]->info()['kind']);
        $this->assertEquals(TraceSpan::SPAN_KIND_UNSPECIFIED, $spans[3]->info()['kind']);
        $this->assertEquals(TraceSpan::SPAN_KIND_UNSPECIFIED, $spans[4]->info()['kind']);
    }

    /**
     * @dataProvider labelHeaders
     */
    public function testParsesDefaultLabels($headerKey, $headerValue, $expectedLabelKey, $expectedLabelValue)
    {
        $tracer = new ContextTracer(new TraceContext('testtraceid'));
        $tracer->inSpan(['name' => 'main'], function () {});

        $reporter = new GoogleCloudReporter(['client' => $this->client->reveal()]);
        $reporter->processSpans($tracer, [$headerKey => $headerValue]);
        $spans = $tracer->spans();
        $labels = $spans[0]->labels();
        $this->assertArrayHasKey($expectedLabelKey, $labels);
        $this->assertEquals($expectedLabelValue, $labels[$expectedLabelKey]);
    }

    public function labelHeaders()
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

    public function testStacktraceLabel()
    {
        $backtrace = [
            [
                'file' => '/path/to/file.php',
                'class' => 'Foo',
                'line' => 1234,
                'function' => 'asdf',
                'type' => '::'
            ]
        ];
        $tracer = new ContextTracer(new TraceContext('testtraceid'));
        $tracer->inSpan(['backtrace' => $backtrace], function () {});

        $reporter = new GoogleCloudReporter(['client' => $this->client->reveal()]);
        $spans = $reporter->convertSpans($tracer);

        $labels = $spans[0]->info()['labels'];
        $this->assertArrayHasKey('/stacktrace', $labels);

        $expected = [
            'stack_frame' => [
                [
                    'file_name' => '/path/to/file.php',
                    'line_number' => 1234,
                    'method_name' => 'asdf',
                    'class_name' => 'Foo'
                ]
            ]
        ];
        $data = json_decode($labels['/stacktrace'], true);
        $this->assertEquals($expected, $data);
    }
}
