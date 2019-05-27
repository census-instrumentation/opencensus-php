<?php
/**
 * Copyright 2019 OpenCensus Authors
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

namespace OpenCensus\Tests\Integration\Trace\Exporter;

use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Exporter\ExporterInterface;
use OpenCensus\Trace\Integrations\Predis as RedisIntegration;
use PHPUnit\Framework\TestCase;
use Predis\Client;

class PredisTest extends TestCase
{
    private $tracer;
    private static $redisHost;
    private static $redisPort;

    public static function setUpBeforeClass()
    {
        RedisIntegration::load();
        self::$redisHost = getenv('REDIS_HOST') ?: '127.0.0.1';
        self::$redisPort = (int) (getenv('REDIS_PORT') ?: 6379);
    }

    public function setUp()
    {
        if (!extension_loaded('opencensus')) {
            $this->markTestSkipped('Please enable the opencensus extension.');
        }
        opencensus_trace_clear();
        $exporter = $this->prophesize(ExporterInterface::class);
        $this->tracer = Tracer::start($exporter->reveal(), [
            'skipReporting' => true
        ]);
    }

    private function getSpans()
    {
        $this->tracer->onExit();
        return $this->tracer->tracer()->spans();
    }

    public function testAddGet()
    {
        $client = new Client([
            'host' => self::$redisHost,
            'port'   => self::$redisPort
        ]);

        $client->set('foo', 'bar');
        $value = $client->get('foo');
        $this->assertEquals('bar', $value);

        $spans = $this->getSpans();
        $this->assertCount(4, $spans);
    }
}
