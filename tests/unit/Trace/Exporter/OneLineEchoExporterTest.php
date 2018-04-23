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

namespace OpenCensus\Tests\Unit\Trace\Exporter;

use OpenCensus\Trace\Exporter\OneLineEchoExporter;
use OpenCensus\Trace\Span;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class OneLineEchoExporterTest extends TestCase
{
    public function testLogsTrace()
    {
        $startTime = microtime(true);
        $span = new Span([
            'name' => 'span',
            'startTime' => $startTime,
            'endTime' => $startTime + 10
        ]);

        ob_start();
        $exporter = new OneLineEchoExporter();
        $this->assertTrue($exporter->export([$span->spanData()]));
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('[ 10000.00 ms] span' . PHP_EOL, $output);
    }
}
