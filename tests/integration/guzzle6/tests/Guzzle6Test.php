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

namespace OpenCensus\Tests\Integration\Trace;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Exporter\ExporterInterface;
use OpenCensus\Trace\Integrations\Guzzle\Middleware;
use OpenCensus\Trace\Propagator\HttpHeaderPropagator;
use OpenCensus\Trace\Propagator\TraceContextFormatter;
use HttpTest\HttpTestServer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class Guzzle6Test extends TestCase
{
    private $client;

    public function setUp()
    {
        parent::setUp();
        $stack = new HandlerStack();
        $stack->setHandler(\GuzzleHttp\choose_handler());
        $stack->push(new Middleware());
        $this->client = new Client(['handler' => $stack]);
    }

    public function testGuzzleRequest()
    {
        $server = HttpTestServer::create(
            function (RequestInterface $request, ResponseInterface &$response) {
                /* Assert the HTTP call includes the expected values */
                $this->assertEquals('GET', $request->getMethod());
                $response = $response->withStatus(200);
            }
        );

        $this->withServer($server, function ($server) {
            $exporter = $this->prophesize(ExporterInterface::class);
            $tracer = Tracer::start($exporter->reveal(), [
                'skipReporting' => true
            ]);
            $response = $this->client->get($server->getUrl());
            $this->assertEquals(200, $response->getStatusCode());

            $tracer->onExit();

            $spans = $tracer->tracer()->spans();
            $this->assertCount(2, $spans);

            $curlSpan = $spans[1];
            $this->assertEquals('GuzzleHttp::request', $curlSpan->name());
            $this->assertEquals('GET', $curlSpan->attributes()['method']);
            $this->assertEquals($server->getUrl(), $curlSpan->attributes()['uri']);
        });
    }

    public function testPersistsTraceContext()
    {
        $server = HttpTestServer::create(
            function (RequestInterface $request, ResponseInterface &$response) {
                /* Assert the HTTP call includes the expected values */
                $this->assertEquals('GET', $request->getMethod());
                $contextHeader = $request->getHeaderLine('X-Cloud-Trace-Context');
                $this->assertNotEmpty($contextHeader);
                $this->assertStringStartsWith('1603c1cde5c74f23bcf1682eb822fcf7', $contextHeader);
                $response = $response->withStatus(200);
            }
        );

        $this->withServer($server, function ($server) {
            $traceContextHeader = '1603c1cde5c74f23bcf1682eb822fcf7/1150672535;o=1';
            $exporter = $this->prophesize(ExporterInterface::class);
            $tracer = Tracer::start($exporter->reveal(), [
                'skipReporting' => true,
                'headers' => [
                    'HTTP_X_CLOUD_TRACE_CONTEXT' => $traceContextHeader
                ]
            ]);
            $response = $this->client->get($server->getUrl());
            $this->assertEquals(200, $response->getStatusCode());

            $tracer->onExit();

            $spans = $tracer->tracer()->spans();
            $this->assertCount(2, $spans);

            $curlSpan = $spans[1];
            $this->assertEquals('GuzzleHttp::request', $curlSpan->name());
            $this->assertEquals('GET', $curlSpan->attributes()['method']);
            $this->assertEquals($server->getUrl(), $curlSpan->attributes()['uri']);
        });
    }

    public function testPersistsTraceContextWithCustomFormat()
    {
        $server = HttpTestServer::create(
            function (RequestInterface $request, ResponseInterface &$response) {
                /* Assert the HTTP call includes the expected values */
                $this->assertEquals('GET', $request->getMethod());

                $contextHeader = $request->getHeaderLine('X-Trace-Context');
                $this->assertNotEmpty($contextHeader);
                $this->assertStringStartsWith('00-4bf92f3577b34da6a3ce929d0e0e4736', $contextHeader);
                $response = $response->withStatus(200);
            }
        );

        $this->withServer($server, function ($server) {
            $propagator = new HttpHeaderPropagator(new TraceContextFormatter(), 'HTTP_X_TRACE_CONTEXT');
            $traceContextHeader = '00-4BF92F3577B34DA6A3CE929D0E0E4736-00F067AA0BA902B7-01';
            $exporter = $this->prophesize(ExporterInterface::class);
            $tracer = Tracer::start($exporter->reveal(), [
                'skipReporting' => true,
                'propagator' => $propagator,
                'headers' => [
                    'HTTP_X_TRACE_CONTEXT' => $traceContextHeader
                ]
            ]);

            $stack = new HandlerStack();
            $stack->setHandler(\GuzzleHttp\choose_handler());
            $stack->push(new Middleware($propagator));
            $this->client = new Client(['handler' => $stack]);

            $response = $this->client->get($server->getUrl());
            $this->assertEquals(200, $response->getStatusCode());

            $tracer->onExit();

            $spans = $tracer->tracer()->spans();
            $this->assertCount(2, $spans);

            $curlSpan = $spans[1];
            $this->assertEquals('GuzzleHttp::request', $curlSpan->name());
            $this->assertEquals('GET', $curlSpan->attributes()['method']);
            $this->assertEquals($server->getUrl(), $curlSpan->attributes()['uri']);
        });
    }

    private function withServer($server, $callback)
    {
        $pid = pcntl_fork();
        if ($pid === -1) {
            $this->fail('Error forking thread.');
        } elseif ($pid) {
            // The fork allows to run the HTTP server in background.
            $server->start();
            pcntl_waitpid($pid, $status);
        } else {
            // We are in the child process
            $server->waitForReady();

            try {
                call_user_func($callback, $server);
            } finally {
                $server->stop();
            }

            exit;
        }
    }
}
