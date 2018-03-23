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

require_once 'Jaeger/Types.php';
require_once 'Jaeger/ZipkinCollector.php';

use OpenCensus\Trace\Tracer\TracerInterface;
use OpenCensus\Trace\Span as OCSpan;
use Jaeger\Thrift\Agent\Zipkin\Annotation;
use Jaeger\Thrift\Agent\Zipkin\Endpoint;
use Jaeger\Thrift\Agent\Zipkin\Span;
use Jaeger\Thrift\Agent\Zipkin\ZipkinCollectorClient;
use Thrift\Protocol\TCompactProtocol;
use Thrift\Transport\TMemoryBuffer;

/**
 * This implementation of the ExporterInterface talks to a Jaeger backend using
 * Thrift over UDP.
 */
class JaegerExporter implements ExporterInterface
{
    private $host;
    private $port;
    private $client;
    private $data;
    private $localEndpoint;

    public function __construct($name, array $options = [])
    {
        $options += [
            'host' => '127.0.0.1',
            'port' => 6831
        ]
        $server = array_key_exists('server', $options) ? $server : $_SERVER;
        $this->host = $options['host'];
        $this->port = (int) $options['port'];
        $this->localEndpoint = new Endpoint([
            'service_name' => $name
        ]);
        if (array_key_exists('SERVER_PORT', $server)) {
            $this->localEndpoint->port = intval($server['SERVER_PORT']);
        }
    }

    /**
     * Set the localEndpoint ipv4 value for all reported spans. Note that this
     * is optional because the reverse DNS lookup can be slow.
     *
     * @param string $ipv4 IPv4 address
     */
    public function setLocalIpv4($ipv4)
    {
        $this->localEndpoint->ipv4 = $ipv4;
    }

    /**
     * Set the localEndpoint ipv6 value for all reported spans. Note that this
     * is optional because the reverse DNS lookup can be slow.
     *
     * @param string $ipv6 IPv6 address
     */
    public function setLocalIpv6($ipv6)
    {
        $this->localEndpoint->ipv6 = $ipv6;
    }

    public function report(TracerInterface $tracer)
    {
        $spans = array_map([$this, 'convertSpan'], $tracer->spans());
        var_dump($spans);

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket === null) {
            return false;
        }

        try {
            $buffer = new TMemoryBuffer();
            $protocol = new TCompactProtocol($buffer);
            $client = new ZipkinCollectorClient(null, $protocol);
            $client->send_submitZipkinBatch($spans);

            $data = $buffer->getBuffer();
            var_dump($data);

            socket_sendto($socket, $data, strlen($data), 0, $this->host, $this->port);
            return true;
        } finally {
            socket_close($socket);
        }
        return false;
    }

    private function convertSpan(OCSpan $span)
    {
        $startTime = (int)((float) $span->startTime()->format('U.u') * 1000 * 1000);
        $endTime = (int)((float) $span->endTime()->format('U.u') * 1000 * 1000);
        $spanId = hexdec($span->spanId());
        $parentSpanId = hexdec($span->parentSpanId());
        $traceId = hexdec($span->traceId());

        return new Span([
            'trace_id' => $traceId,
            'name' => $span->name(),
            'id' => $spanId,
            'parent_id' => $parentSpanId,
            'timestamp' => $startTime,
            'duration' => $endTime - $startTime,
            'annotations' => [
                new Annotation([
                    'timestamp' => $startTime,
                    'host' => $this->localEndpoint,
                    'value' => 'span start'
                ])
            ],
            'binary_annotations' => []
        ]);
    }
}
