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

use OpenCensus\Trace\Exporter\EchoExporter;
use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\Tracer\TracerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class EchoExporterTest extends TestCase
{
    private $tracer;

    public function setUp()
    {
        $this->tracer = $this->prophesize(TracerInterface::class);
    }

    public function testLogsTrace()
    {
        $spans = [
            new Span([
                'name' => 'span',
                'startTime' => microtime(true),
                'endTime' => microtime(true) + 10
            ])
        ];

        $this->tracer->spans()->willReturn($spans);

        ob_start();
        $reporter = new EchoExporter();
        $this->assertTrue($reporter->report($this->tracer->reveal()));
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertGreaterThan(0, strlen($output));
    }
}
