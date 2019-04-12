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

use OpenCensus\Trace\Integrations\Redis as RedisIntegration;
use PHPUnit\Framework\TestCase;

class RedisTest extends TestCase
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
}
