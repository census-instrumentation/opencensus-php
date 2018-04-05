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

require_once __DIR__ . '/mock_error_log.php';

use Psr\Log\LoggerInterface;
use OpenCensus\Trace\Exporter\LoggerExporter;
use OpenCensus\Trace\Span;
use Prophecy\Argument;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class LoggerExporterTest extends TestCase
{
    private $tracer;
    private $logger;

    public function setUp()
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    public function testReportWithAnExceptionErrorLog()
    {
        $span = new Span([
            'name' => 'span',
            'startTime' => microtime(true),
            'endTime' => microtime(true) + 10
        ]);

        $this->logger->log('some-level', Argument::type('string'))->willThrow(
            new \Exception('error_log test')
        );

        $exporter = new LoggerExporter($this->logger->reveal(), 'some-level');
        $this->expectOutputString(
            'Reporting the Trace data failed: error_log test'
        );
        $this->assertFalse($exporter->export([$span->spanData()]));
    }

    public function testLogsTrace()
    {
        $span = new Span([
            'name' => 'span',
            'startTime' => microtime(true),
            'endTime' => microtime(true) + 10
        ]);

        $this->logger->log('some-level', Argument::type('string'))->shouldBeCalled();

        $exporter = new LoggerExporter($this->logger->reveal(), 'some-level');
        $this->assertTrue($exporter->export([$span->spanData()]));
    }
}
