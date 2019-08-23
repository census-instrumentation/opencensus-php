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
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Message\Response;
use OpenCensus\Trace\Exporter\ExporterInterface;
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Integrations\Guzzle\EventSubscriber;
use Prophecy\Argument;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class EventSubscriberTest extends TestCase
{
    private $exporter;

    public function setUp()
    {
        if (!interface_exists('GuzzleHttp\Event\SubscriberInterface')) {
            $this->markTestSkipped('Guzzle 5 not loaded');
        }

        $this->exporter = $this->prophesize(ExporterInterface::class);
        if (extension_loaded('opencensus')) {
            opencensus_trace_clear();
        } else {
            Context::reset();
        }
    }

    public function testAddsSpanContextHeader()
    {
        $this->exporter->export(Argument::that(static function ($spans) {
            return count($spans) === 3 && $spans[2]->name() === 'GuzzleHttp::request';
        }))->shouldBeCalled();

        $rt = Tracer::start($this->exporter->reveal(), [
            'skipReporting' => true
        ]);

        $client = new Client();
        $subscriber = new EventSubscriber();
        $history = new History();
        $mock = new Mock([
            new Response(200, ['X-Foo' => 'Bar'])
        ]);

        $client->getEmitter()->attach($mock);
        $client->getEmitter()->attach($history);
        $client->getEmitter()->attach($subscriber);

        Tracer::inSpan(['name' => 'parentSpan', 'spanId' => '1234'], static function () use ($client) {
            $client->get('/');
        });

        $request = $history->getLastRequest();
        $headers = $request->getHeaders();
        $this->assertArrayHasKey('X-Cloud-Trace-Context', $headers);
        $this->assertRegExp('/[0-9a-f]+\/4660;o=1/', $headers['X-Cloud-Trace-Context'][0]);

        $rt->onExit();
    }
}
