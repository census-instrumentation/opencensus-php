<?php
/**
 * Copyright 2017 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCensus\Trace\Exporter;


use Jaeger\Thrift\Agent\AgentClient;
use Jaeger\Thrift\Batch;
use Jaeger\Thrift\Process;
use Jaeger\Thrift\Span;

use Thrift\Protocol\TBinaryProtocol;
use Thrift\Protocol\TCompactProtocol;
use Thrift\Transport\TMemoryBuffer;
use Thrift\Transport\THttpClient;

/**
 * This implementation of the ExporterInterface talks to a Jaeger backend using
 * Thrift over UDP.
 */
class JaegerHttpExporter extends JaegerExporter
{
    protected function doReport(array $spans)
    {
        $http = new THttpClient($this->host, $this->port, '/');
        $protocol = new TBinaryProtocol($http);
        $client = new AgentClient(null, $protocol);
        $batch = new Batch([
            'process' => $this->process,
            'spans' => $spans
        ]);



        try {
            $ret = $client->emitBatch($batch);
            var_dump($ret);
            return $ret;
        } catch (\Exception $e) {
            var_dump($e);
        }
    }
}
