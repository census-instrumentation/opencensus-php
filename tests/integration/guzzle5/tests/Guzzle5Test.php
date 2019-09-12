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
use HttpTest\HttpTestServer;
use OpenCensus\Trace\Exporter\NullExporter;
use OpenCensus\Trace\Propagator\HttpHeaderPropagator;
use OpenCensus\Trace\RequestHandler;
use OpenCensus\Trace\Sampler\AlwaysSampleSampler;
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Integrations\Guzzle\EventSubscriber;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class Guzzle5Test extends TestCase
{
    private $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = new Client();
        if (extension_loaded('opencensus')) {
            opencensus_trace_clear();
        }
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


        $tracer = new RequestHandler(
            new NullExporter(),
            new AlwaysSampleSampler(),
            new HttpHeaderPropagator(),
            ['skipReporting' => true]
        );

        $this->client->getEmitter()->attach(new EventSubscriber($tracer->tracer()));

        $server->start();
        $response = $this->client->get($server->getUrl());
        $server->stop();
        $tracer->onExit();
        $this->assertEquals(200, $response->getStatusCode());

        $spans = $tracer->tracer()->spans();

        $this->assertCount(2, $spans);
        $curlSpan = $spans[1];
        $this->assertEquals('Guzzle: localhost', $curlSpan->name());
        $this->assertEquals('GET', $curlSpan->attributes()['http.method']);
        $this->assertEquals($server->getUrl(), $curlSpan->attributes()['http.uri']);
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

        $traceContextHeader = '1603c1cde5c74f23bcf1682eb822fcf7/1150672535;o=1';
        $tracer = new RequestHandler(
            new NullExporter(),
            new AlwaysSampleSampler(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true,
                'headers' => [
                    'X-Cloud-Trace-Context' => $traceContextHeader,
                ],
            ]
        );

        $this->client->getEmitter()->attach(new EventSubscriber($tracer->tracer()));

        $server->start();
        $response = $this->client->get($server->getUrl());
        $server->stop();
        $tracer->onExit();
        $this->assertEquals(200, $response->getStatusCode());

        $spans = $tracer->tracer()->spans();

        $this->assertCount(2, $spans);
        $curlSpan = $spans[1];
        $this->assertEquals('Guzzle: localhost', $curlSpan->name());
        $this->assertEquals('GET', $curlSpan->attributes()['http.method']);
        $this->assertEquals($server->getUrl(), $curlSpan->attributes()['http.uri']);
    }
}
