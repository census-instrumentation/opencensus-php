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

namespace OpenCensus\Tests\Integration\Trace\Exporter;

use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Exporter\ExporterInterface;
use OpenCensus\Trace\Integrations\Postgres;
use PHPUnit\Framework\TestCase;

class PgsqlTest extends TestCase
{
    private $tracer;
    private static $postgresConnectionString;

    public static function setUpBeforeClass()
    {
        Postgres::load();
        $postgresHost = getenv('POSTGRES_HOST') ?: '127.0.0.1';
        $postgresPort = (int) (getenv('POSTGRES_PORT') ?: 5432);
        $postgresDatabase = getenv('POSTGRES_DATABASE') ?: 'postgres';
        $postgresUsername = getenv('POSTGRES_USERNAME') ?: 'postgres';
        $postgresPassword = getenv('POSTGRES_PASSWORD') ?: 'pgsql';

        self::$postgresConnectionString = sprintf(
            'host=%s port=%d dbname=%s user=%s password=%s',
            $postgresHost,
            $postgresPort,
            $postgresDatabase,
            $postgresUsername,
            $postgresPassword
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('opencensus')) {
            $this->markTestSkipped('Please enable the opencensus extension.');
        }
        opencensus_trace_clear();
        $exporter = $this->prophesize(ExporterInterface::class);
        $this->tracer = Tracer::start($exporter->reveal(), [
            'skipReporting' => true
        ]);
    }

    public function testBasicQuery()
    {
        $conn = pg_pconnect(self::$postgresConnectionString);
        $this->assertTrue($conn !== false);

        $query = 'select 1 as foo, 3 * 2';
        $result = pg_query($conn, $query);
        $this->assertTrue($result !== false);

        $row = pg_fetch_row($result);
        $this->assertEquals(['1', '6'], $row);

        $spans = $this->getSpans();

        $this->assertCount(3, $spans);
        $connectSpan = $spans[1];
        $this->assertEquals('pg_pconnect', $connectSpan->name());

        $querySpan = $spans[2];
        $this->assertEquals('pg_query', $querySpan->name());
        $this->assertCount(1, $querySpan->attributes());
        $this->assertEquals($query, $querySpan->attributes()['query']);
    }

    public function testBasicQueryDefaultConnection()
    {
        $conn = pg_pconnect(self::$postgresConnectionString);
        $this->assertTrue($conn !== false);

        $query = 'select 1 as foo, 2 * 3';
        $result = pg_query($query);
        $this->assertTrue($result !== false);

        $row = pg_fetch_row($result);
        $this->assertEquals(['1', '6'], $row);

        $spans = $this->getSpans();

        $this->assertCount(3, $spans);
        $connectSpan = $spans[1];
        $this->assertEquals('pg_pconnect', $connectSpan->name());

        $querySpan = $spans[2];
        $this->assertEquals('pg_query', $querySpan->name());
        $this->assertCount(1, $querySpan->attributes());
        $this->assertEquals($query, $querySpan->attributes()['query']);
    }

    public function testQueryParams()
    {
        $conn = pg_pconnect(self::$postgresConnectionString);
        $this->assertTrue($conn !== false);

        $query = 'select $1::text, $2::int';
        $result = pg_query_params($conn, $query, ["Joe's Widgets", 6]);
        $this->assertTrue($result !== false);

        $row = pg_fetch_row($result);
        $this->assertEquals(["Joe's Widgets", '6'], $row);

        $spans = $this->getSpans();

        $this->assertCount(3, $spans);
        $connectSpan = $spans[1];
        $this->assertEquals('pg_pconnect', $connectSpan->name());

        $querySpan = $spans[2];
        $this->assertEquals('pg_query_params', $querySpan->name());
        $this->assertCount(1, $querySpan->attributes());
        $this->assertEquals($query, $querySpan->attributes()['query']);
    }

    public function testQueryParamsDefaultConnection()
    {
        $conn = pg_pconnect(self::$postgresConnectionString);
        $this->assertTrue($conn !== false);

        $query = 'select $1::text, $2::int';
        $result = pg_query_params($query, ["Joe's Widgets", 6]);
        $this->assertTrue($result !== false);

        $row = pg_fetch_row($result);
        $this->assertEquals(["Joe's Widgets", '6'], $row);

        $spans = $this->getSpans();

        $this->assertCount(3, $spans);
        $connectSpan = $spans[1];
        $this->assertEquals('pg_pconnect', $connectSpan->name());

        $querySpan = $spans[2];
        $this->assertEquals('pg_query_params', $querySpan->name());
        $this->assertCount(1, $querySpan->attributes());
        $this->assertEquals($query, $querySpan->attributes()['query']);
    }

    public function testPrepareQuery()
    {
        $conn = pg_pconnect(self::$postgresConnectionString);
        $this->assertTrue($conn !== false);

        $query = 'select $1::text, $2::int';
        $result = pg_prepare($conn, 'my_query', $query);
        $this->assertTrue($result !== false);
        $result = pg_execute($conn, 'my_query', ["Joe's Widgets", 6]);

        $row = pg_fetch_row($result);
        $this->assertEquals(["Joe's Widgets", '6'], $row);

        $spans = $this->getSpans();

        $this->assertCount(4, $spans);
        $connectSpan = $spans[1];
        $this->assertEquals('pg_pconnect', $connectSpan->name());

        $prepareSpan = $spans[2];
        $this->assertEquals('pg_prepare', $prepareSpan->name());
        $this->assertCount(2, $prepareSpan->attributes());
        $this->assertEquals('my_query', $prepareSpan->attributes()['statement']);
        $this->assertEquals($query, $prepareSpan->attributes()['query']);

        $executeSpan = $spans[3];
        $this->assertEquals('pg_execute', $executeSpan->name());
        $this->assertCount(1, $executeSpan->attributes());
        $this->assertEquals('my_query', $executeSpan->attributes()['statement']);
    }

    public function testPrepareQueryDefaultConnection()
    {
        $conn = pg_pconnect(self::$postgresConnectionString);
        $this->assertTrue($conn !== false);

        $query = 'select $1::text, $2::int';
        $result = pg_prepare('my_query2', $query);
        $this->assertTrue($result !== false);
        $result = pg_execute('my_query2', ["Joe's Widgets", 6]);

        $row = pg_fetch_row($result);
        $this->assertEquals(["Joe's Widgets", '6'], $row);

        $spans = $this->getSpans();

        $this->assertCount(4, $spans);
        $connectSpan = $spans[1];
        $this->assertEquals('pg_pconnect', $connectSpan->name());

        $prepareSpan = $spans[2];
        $this->assertEquals('pg_prepare', $prepareSpan->name());
        $this->assertCount(2, $prepareSpan->attributes());
        $this->assertEquals('my_query2', $prepareSpan->attributes()['statement']);
        $this->assertEquals($query, $prepareSpan->attributes()['query']);

        $executeSpan = $spans[3];
        $this->assertEquals('pg_execute', $executeSpan->name());
        $this->assertCount(1, $executeSpan->attributes());
        $this->assertEquals('my_query2', $executeSpan->attributes()['statement']);
    }

    public function testConnect()
    {
        $conn = pg_connect(self::$postgresConnectionString);
        $this->assertTrue($conn !== false);

        $spans = $this->getSpans();

        $this->assertCount(2, $spans);
        $connectSpan = $spans[1];
        $this->assertEquals('pg_connect', $connectSpan->name());
    }

    private function getSpans()
    {
        $this->tracer->onExit();
        return $this->tracer->tracer()->spans();
    }
}
