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

use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Exporter\ExporterInterface;
use OpenCensus\Trace\Integrations\Curl;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class CurlTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        Curl::load();
    }

    public function setUp()
    {
        if (!extension_loaded('opencensus')) {
            $this->markTestSkipped('Please enable the opencensus extension.');
        }
        opencensus_trace_clear();
    }

    public function testCurlExec()
    {
        $url = 'https://www.google.com/';

        $exporter = $this->prophesize(ExporterInterface::class);
        $tracer = Tracer::start($exporter->reveal(), [
            'skipReporting' => true
        ]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        $this->assertNotEmpty($output);
        $tracer->onExit();

        $spans = $tracer->tracer()->spans();
        $this->assertCount(2, $spans);

        $curlSpan = $spans[1];
        $this->assertEquals('curl_exec', $curlSpan->name());
        $this->assertEquals($url, $curlSpan->attributes()['uri']);
    }
}
