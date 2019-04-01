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

namespace OpenCensus\Tests\Unit\Trace\Integrations\Guzzle;

use OpenCensus\Core\Context;
use OpenCensus\Trace\Exporter\NullExporter;
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Exporter\ExporterInterface;
use OpenCensus\Trace\Integrations\Guzzle\Middleware;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class MiddlewareTest extends TestCase
{
    /**
     * @var ExporterInterface|ObjectProphecy
     */
    private $exporter;

    public function setUp()
    {
        $this->exporter = $this->prophesize(ExporterInterface::class);
        if (extension_loaded('opencensus')) {
            opencensus_trace_clear();
        } else {
            Context::reset();
        }
    }

    public function testAddsSpanContextHeader()
    {
        $this->exporter->export(Argument::that(function ($spans) {
            return count($spans) == 3 && $spans[2]->name() == 'GuzzleHttp::request';
        }))->shouldBeCalled();

        $rt = Tracer::start($this->exporter->reveal(), [
            'skipReporting' => true
        ]);

        $handler = function ($request, $options) {
            $response = $this->prophesize(ResponseInterface::class);
            return $response->reveal();
        };

        $middleware = new Middleware();
        $stack = $middleware($handler);
        $req = $this->prophesize(RequestInterface::class);
        $req->getMethod()->willReturn('GET')->shouldBeCalled();
        $req->getUri()->willReturn('/')->shouldBeCalled();
        $req->withHeader('X-Cloud-Trace-Context', Argument::that(function ($val) {
            return preg_match('/[0-9a-f]+\/4660;o=1/', $val);
        }))->willReturn($req->reveal())->shouldBeCalled();
        $request = $req->reveal();

        $response = Tracer::inSpan(['name' => 'parentSpan', 'spanId' => '1234'], function () use ($stack, $request) {
            return $stack($request, []);
        });

        $rt->onExit();
    }
}
