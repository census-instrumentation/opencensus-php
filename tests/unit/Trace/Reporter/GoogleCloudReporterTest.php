<?php
/**
 * Copyright 2017 Google Inc.
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

use OpenCensus\Trace\Reporter\GoogleCloudReporter;
use OpenCensus\Trace\TraceContext;
use OpenCensus\Trace\TraceSpan;
use OpenCensus\Trace\Tracer\TracerInterface;
use Prophecy\Argument;
use Google\Cloud\Trace\Trace;
use Google\Cloud\Trace\TraceClient;

/**
 * @group trace
 */
class GoogleCloudReporterTest extends \PHPUnit_Framework_TestCase
{
    private $tracer;
    private $client;

    public function setUp()
    {
        $this->tracer = $this->prophesize(TracerInterface::class);
        $this->client = $this->prophesize(TraceClient::class);
    }

    public function testLogsTrace()
    {
        $spans = [
            new TraceSpan([
                'name' => 'span',
                'startTime' => microtime(true),
                'endTime' => microtime(true) + 10
            ])
        ];
        $this->tracer->context()->willReturn(new TraceContext('testtraceid'));
        $this->tracer->spans()->willReturn($spans);
        $this->tracer->addRootLabel(Argument::type('string'), Argument::any())->willReturn(true);
        $this->client->insert(Argument::type(Trace::class))->willReturn(true);
        $this->client->trace('testtraceid')->willReturn(true);

        $reporter = new GoogleCloudReporter([
            'client' => $this->client->reveal()
        ]);

        $this->assertTrue($reporter->report($this->tracer->reveal()));
    }
}
