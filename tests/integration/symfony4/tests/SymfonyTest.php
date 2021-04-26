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

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class SymfonyTest extends TestCase
{
    private static $outputFile;
    private static $client;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$outputFile = sys_get_temp_dir() . '/spans.json';
        self::$client = new Client([
            'base_uri' => getenv('TESTURL') ?: 'http://localhost:9000'
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearSpans();
    }

    public function testReportsTraceToFile()
    {
        $rand = mt_rand();
        $response = self::$client->request('GET', '/', [
            'query' => [
                'rand' => $rand
            ]
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Hello world!', $response->getBody()->getContents());

        $spans = json_decode(file_get_contents(self::$outputFile), true);
        $this->assertNotEmpty($spans);

        $spansByName = $this->groupSpansByName($spans);

        $this->assertEquals('/?rand=' . $rand, $spans[0]['name']);
        $this->assertNotEmpty($spansByName['kernel.controller']);
        $this->assertNotEmpty($spansByName['kernel.controller_arguments']);
        $this->assertNotEmpty($spansByName['kernel.response']);
        $this->assertNotEmpty($spansByName['kernel.finish_request']);
        $this->assertNotEmpty($spansByName['kernel.terminate']);
    }

    public function testDoctrine()
    {
        // create a user
        $email = uniqid() . '@user.com';
        $response = self::$client->request('GET', '/user/create');
        $this->assertEquals(200, $response->getStatusCode());
        $userData = json_decode($response->getBody()->getContents(), true);

        $spans = json_decode(file_get_contents(self::$outputFile), true);
        $this->assertNotEmpty($spans);

        $spansByName = $this->groupSpansByName($spans);
        $this->assertNotEmpty($spansByName['PDO::__construct']);
        $this->assertNotEmpty($spansByName['PDOStatement::execute']);
        $this->assertNotEmpty($spansByName['PDO::commit']);

        $this->clearSpans();

        // find a user
        $response = self::$client->request('GET', '/user/' . $userData['id']);
        $this->assertEquals(200, $response->getStatusCode());
        $userData = json_decode($response->getBody()->getContents(), true);

        $spans = json_decode(file_get_contents(self::$outputFile), true);
        $this->assertNotEmpty($spans);

        $spansByName = $this->groupSpansByName($spans);
        $this->assertNotEmpty($spansByName['doctrine/load']);
        $this->assertNotEmpty($spansByName['PDO::__construct']);
        $this->assertNotEmpty($spansByName['PDOStatement::execute']);

        $this->clearSpans();

        // list users
        $response = self::$client->request('GET', '/user');
        $this->assertEquals(200, $response->getStatusCode());
        $usersData = json_decode($response->getBody()->getContents(), true);

        $spans = json_decode(file_get_contents(self::$outputFile), true);
        $this->assertNotEmpty($spans);

        $spansByName = $this->groupSpansByName($spans);
        $this->assertNotEmpty($spansByName['doctrine/loadAll']);
        $this->assertNotEmpty($spansByName['PDO::__construct']);
        $this->assertNotEmpty($spansByName['PDO::query']);

        $this->clearSpans();

        // update user
        $response = self::$client->request('GET', '/user/' . $userData['id'] . '/update');
        $this->assertEquals(200, $response->getStatusCode());
        $userData = json_decode($response->getBody()->getContents(), true);

        $spans = json_decode(file_get_contents(self::$outputFile), true);
        $this->assertNotEmpty($spans);

        $spansByName = $this->groupSpansByName($spans);
        $this->assertNotEmpty($spansByName['doctrine/load']);
        $this->assertNotEmpty($spansByName['PDO::__construct']);
        $this->assertNotEmpty($spansByName['PDOStatement::execute']);
        $this->assertNotEmpty($spansByName['PDO::commit']);

        $this->clearSpans();

        // delete user
        $response = self::$client->request('GET', '/user/' . $userData['id'] . '/delete');
        $this->assertEquals(200, $response->getStatusCode());
        $userData = json_decode($response->getBody()->getContents(), true);

        $spans = json_decode(file_get_contents(self::$outputFile), true);
        $this->assertNotEmpty($spans);

        $spansByName = $this->groupSpansByName($spans);
        $this->assertNotEmpty($spansByName['doctrine/load']);
        $this->assertNotEmpty($spansByName['PDO::__construct']);
        $this->assertNotEmpty($spansByName['PDOStatement::execute']);
        $this->assertNotEmpty($spansByName['PDO::commit']);
    }

    private function groupSpansByName($spans)
    {
        $spansByName = [];
        foreach ($spans as $span) {
            if (!array_key_exists($span['name'], $spansByName)) {
                $spansByName[$span['name']] = [];
            }
            $spansByName[$span['name']][] = $span;
        }
        return $spansByName;
    }

    private function clearSpans()
    {
        if (file_exists(self::$outputFile)) {
            $fp = fopen(self::$outputFile, 'r+');
            ftruncate($fp, 0);
            fclose($fp);
        }
    }
}
