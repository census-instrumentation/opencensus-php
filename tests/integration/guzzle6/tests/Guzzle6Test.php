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
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class Guzzle5Test extends TestCase
{
    public function testGuzzleRequest()
    {
        $stack = new HandlerStack();
        $stack->setHandler(\GuzzleHttp\choose_handler());
        $stack->push(new Middleware());
        $client = new Client(['handler' => $stack]);

        $url = 'http://www.google.com/';
        $exporter = $this->prophesize(ExporterInterface::class);
        $tracer = Tracer::start($exporter->reveal(), [
            'skipReporting' => true
        ]);
        $response = $client->get($url);
        $this->assertEquals(200, $response->getStatusCode());

        $tracer->onExit();

        $spans = $tracer->tracer()->spans();
        $this->assertCount(2, $spans);

        $curlSpan = $spans[1];
        $this->assertEquals('GuzzleHttp::request', $curlSpan->name());
        $this->assertEquals('GET', $curlSpan->attributes()['method']);
        $this->assertEquals($url, $curlSpan->attributes()['uri']);
    }
}
