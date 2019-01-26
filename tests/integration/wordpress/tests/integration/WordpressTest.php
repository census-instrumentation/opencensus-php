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

class WordpressTest extends TestCase
{
    private static $outputFile;

    public static function setUpBeforeClass()
    {
        self::$outputFile = sys_get_temp_dir() . '/spans.json';
    }

    public function setUp()
    {
        parent::setUp();
        if (file_exists(self::$outputFile)) {
            $fp = fopen(self::$outputFile, 'r+');
            ftruncate($fp, 0);
            fclose($fp);
        }
    }

    public function testReportsTraceToFile()
    {
        $rand = mt_rand();
        $client = new Client(['base_uri' => 'http://localhost:9000']);
        $response = $client->request('GET', '/', [
            'query' => [
                'rand' => $rand
            ]
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Hello world!', $response->getBody()->getContents());

        $spans = json_decode(file_get_contents(self::$outputFile), true);
        $this->assertNotEmpty($spans);

        $spansByName = [];
        foreach ($spans as $span) {
            if (!array_key_exists($span['name'], $spansByName)) {
                $spansByName[$span['name']] = [];
            }
            $spansByName[$span['name']][] = $span;
        }

        $this->assertEquals('/?rand=' . $rand, $spans[0]['name']);
        $this->assertNotEmpty($spansByName['mysqli_query']);
        $this->assertNotEmpty($spansByName['load_textdomain']);
        $this->assertNotEmpty($spansByName['get_header']);
        $this->assertNotEmpty($spansByName['load_template']);
        // commented out as twentynineteen theme does not have sidebar
        // $this->assertNotEmpty($spansByName['get_sidebar']);
        $this->assertNotEmpty($spansByName['get_footer']);
    }
}
