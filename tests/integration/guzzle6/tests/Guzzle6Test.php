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
use HttpTest\HttpTestServer;
use OpenCensus\Trace\Exporter\NullExporter;
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Integrations\Guzzle\Middleware;
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

    /**
     * @runInSeparateProcess
     */
    public function testGuzzleRequest()
    {
        $server = HttpTestServer::create(
            function (RequestInterface $request, ResponseInterface &$response) {
                /* Assert the HTTP call includes the expected values */
                $this->assertEquals('GET', $request->getMethod());
                $response = $response->withStatus(200);
            }
        );

        $exporter = new NullExporter();
        $tracer = Tracer::start($exporter, [
            'skipReporting' => true
        ]);

        $server->start();

        $response = $this->client->get($server->getUrl());

        $this->assertEquals(200, $response->getStatusCode());

        $server->stop();

        $tracer->onExit();

        $spans = $tracer->tracer()->spans();
        $this->assertCount(2, $spans);

        $curlSpan = $spans[1];
        $this->assertEquals('GuzzleHttp::request', $curlSpan->name());
        $this->assertEquals('GET', $curlSpan->attributes()['method']);
        $this->assertEquals($server->getUrl(), $curlSpan->attributes()['uri']);
    }

    /**
     * @runInSeparateProcess
     */
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
        $exporter = new NullExporter();
        $tracer = Tracer::start($exporter, [
            'skipReporting' => true,
            'headers' => [
                'X-Cloud-Trace-Context' => $traceContextHeader
            ]
        ]);

        $server->start();

        $response = $this->client->get($server->getUrl());
        $this->assertEquals(200, $response->getStatusCode());

        $server->stop();

        $tracer->onExit();

        $spans = $tracer->tracer()->spans();
        $this->assertCount(2, $spans);

        $curlSpan = $spans[1];
        $this->assertEquals('GuzzleHttp::request', $curlSpan->name());
        $this->assertEquals('GET', $curlSpan->attributes()['method']);
        $this->assertEquals($server->getUrl(), $curlSpan->attributes()['uri']);
    }
}
