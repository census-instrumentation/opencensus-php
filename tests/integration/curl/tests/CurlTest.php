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
use HttpTest\HttpTestServer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
        $server = HttpTestServer::create(
            function (RequestInterface $request, ResponseInterface &$response) {
                /* Assert the HTTP call includes the expected values */
                $this->assertEquals('GET', $request->getMethod());
                $response = $response->withStatus(200);
            }
        );

        $this->withServer($server, function ($server) {
            $url = $server->getUrl() . '/';
            $exporter = $this->prophesize(ExporterInterface::class);
            $tracer = Tracer::start($exporter->reveal(), [
                'skipReporting' => true
            ]);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);
            $tracer->onExit();

            $spans = $tracer->tracer()->spans();
            $this->assertCount(2, $spans);

            $curlSpan = $spans[1];
            $this->assertEquals('curl_exec', $curlSpan->name());
            $this->assertEquals($url, $curlSpan->attributes()['uri']);
        });
    }

    private function withServer($server, $callback)
    {
        $pid = pcntl_fork();
        if ($pid === -1) {
            $this->fail('Error forking thread.');
        } elseif ($pid) {
            // The fork allows to run the HTTP server in background.
            $server->start();
            pcntl_waitpid($pid, $status);
        } else {
            // We are in the child process
            $server->waitForReady();

            try {
                call_user_func($callback, $server);
            } finally {
                $server->stop();
            }

            exit;
        }
    }
}
