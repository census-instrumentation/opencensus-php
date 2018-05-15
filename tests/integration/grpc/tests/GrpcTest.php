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

use Grpc\BaseStub;
use Grpc\Channel;
use Grpc\ChannelCredentials;
use Grpc\Interceptor;
use Grpc\Server;
use OpenCensus\Trace\Exporter\ExporterInterface;
use OpenCensus\Trace\Integrations\Grpc\TraceInterceptor;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\Tracer;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class GrpcTest extends TestCase
{
    /* @var Server */
    private $server;

    /* @var int */
    private $port;

    /* @var Channel */
    private $channel;

    public function setUp()
    {
        parent::setUp();
        $this->server = new Server([]);
        $this->port = $this->server->addHttp2Port('0.0.0.0:0');
        $this->channel = new Channel(
            'localhost:' . $this->port, [
                'credentials' => ChannelCredentials::createInsecure()
            ]
        );
        $this->server->start();

        if (extension_loaded('opencensus')) {
            opencensus_trace_clear();
        }
    }

    public function tearDown()
    {
        $this->channel->close();
        parent::tearDown();
    }

    public function testGrpcUnaryUnary()
    {
        $exporter = $this->prophesize(ExporterInterface::class);
        $tracer = Tracer::start($exporter->reveal(), [
            'skipReporting' => true
        ]);

        $interceptor = new TraceInterceptor();
        $channel = Interceptor::intercept($this->channel, $interceptor);
        $client = new InterceptorClient('localhost' . $this->port, [
            'credentials' => ChannelCredentials::createInsecure()
        ], $channel);

        $request = new SimpleRequest('grpc_unary_data');

        $call = $client->UnaryCall($request);
        $event = $this->server->requestCall();
        $this->assertEquals('/dummy_method', $event->method);
        $this->assertArrayHasKey('grpc-trace-bin', $event->metadata);

        $spans = $tracer->tracer()->spans();
        $this->assertCount(2, $spans);

        $unarySpan = $spans[1];
        $this->assertEquals('grpc/simpleRequest', $unarySpan->name());
        $this->assertEquals(Span::KIND_CLIENT, $unarySpan->kind());
    }

    public function testGrpcStreamUnary()
    {
        $exporter = $this->prophesize(ExporterInterface::class);
        $tracer = Tracer::start($exporter->reveal(), [
            'skipReporting' => true
        ]);

        $interceptor = new TraceInterceptor();
        $channel = Interceptor::intercept($this->channel, $interceptor);
        $client = new InterceptorClient('localhost' . $this->port, [
            'credentials' => ChannelCredentials::createInsecure()
        ], $channel);

        $request = new SimpleRequest('grpc_unary_data');

        $streamCall = $client->StreamCall();
        $streamCall->write($request);
        $event = $this->server->requestCall();
        $this->assertEquals('/dummy_method', $event->method);
        $this->assertArrayHasKey('grpc-trace-bin', $event->metadata);

        $spans = $tracer->tracer()->spans();
        $this->assertCount(2, $spans);

        $streamSpam = $spans[1];
        $this->assertEquals('grpc/clientStreamRequest', $streamSpam->name());
        $this->assertEquals(Span::KIND_CLIENT, $streamSpam->kind());

    }
}

class InterceptorClient extends BaseStub
{
    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param Channel|InterceptorChannel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null)
    {
        parent::__construct($hostname, $opts, $channel);
    }
    /**
     * A simple RPC.
     * @param \Routeguide\Point $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function UnaryCall(
        SimpleRequest $argument,
        $metadata = [],
        $options = []
    ) {
        return $this->_simpleRequest(
            '/dummy_method',
            $argument,
            [],
            $metadata,
            $options
        );
    }
    /**
     * A client-to-server streaming RPC.
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function StreamCall(
        $metadata = [],
        $options = []
    ) {
        return $this->_clientStreamRequest('/dummy_method', [], $metadata, $options);
    }
}

class SimpleRequest
{
    private $data;
    public function __construct($data)
    {
        $this->data = $data;
    }
    public function setData($data)
    {
        $this->data = $data;
    }
    public function serializeToString()
    {
        return $this->data;
    }
}