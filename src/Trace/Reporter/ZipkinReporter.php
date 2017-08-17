<?php
/**
 * Copyright 2017 Google Inc. All Rights Reserved.
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

namespace OpenCensus\Trace\Reporter;

use OpenCensus\Trace\Tracer\TracerInterface;
use OpenCensus\Trace\TraceSpan;

/**
 * This implementation of the ReporterInterface appends a json
 * representation of the trace to a file.
 */
class ZipkinReporter implements ReporterInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $url;

    /**
     * Create a new ZipkinReporter
     *
     * @param string $name The name of this application
     * @param string $host The hostname of the Zipkin server
     * @param int $port The port of the Zipkin server
     * @param string $endpoint (optional) The path for the span reporting endpoint. **Defaults to** `/api/v1/spans`
     */
    public function __construct($name, $host, $port, $endpoint = '/api/v1/spans')
    {
        $this->name = $name;
        $this->host = $host;
        $this->port = $port;
        $this->url = "http://${host}:${port}${endpoint}";
    }

    /**
     * Report the provided Trace to a backend.
     *
     * @param  TracerInterface $tracer
     * @return bool
     */
    public function report(TracerInterface $tracer)
    {
        $spans = $this->convertSpans($tracer);

        if (empty($spans)) {
            return false;
        }

        try {
            $json = json_encode($spans);
            $contextOptions = [
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => $json
                ]
            ];

            $context = stream_context_create($contextOptions);
            file_get_contents($this->url, false, $context);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Convert spans into Zipkin's expected JSON output format.
     *
     * @param  TracerInterface $tracer
     * @return array Representation of the collected trace spans ready for serialization
     */
    public function convertSpans(TracerInterface $tracer)
    {
        $spans = $tracer->spans();
        $context = $tracer->context();
        $traceId = $context->traceId();

        $endpoint = [
            'ipv4' => $this->host,
            'port' => $this->port,
            'serviceName' => $this->name
        ];

        return array_map(function ($span) use ($traceId, $endpoint) {
            $startTime = (int)((float) $span->startTime()->format('U.u') * 1000 * 1000);
            $endTime = (int)((float) $span->endTime()->format('U.u') * 1000 * 1000);
            $spanId = str_pad(dechex($span->spanId()), 16, '0', STR_PAD_LEFT);
            $parentSpanId = $span->parentSpanId()
                ? str_pad(dechex($span->parentSpanId()), 16, '0', STR_PAD_LEFT)
                : null;

            $annotations = [];
            switch ($span->kind()) {
                case TraceSpan::SPAN_KIND_UNKNOWN:
                case TraceSpan::SPAN_KIND_CLIENT:
                    $annotations = [
                        [
                            'endpoint' => $endpoint,
                            'timestamp' => $startTime,
                            'value' => 'cs' // client send
                        ],
                        [
                            'endpoint' => $endpoint,
                            'timestamp' => $endTime,
                            'value' => 'cr' // client receive
                        ]
                    ];
                    break;
                case TraceSpan::SPAN_KIND_SERVER:
                    $annotations = [
                        [
                            'endpoint' => $endpoint,
                            'timestamp' => $startTime,
                            'value' => 'sr' // server receive
                        ],
                        [
                            'endpoint' => $endpoint,
                            'timestamp' => $endTime,
                            'value' => 'ss' // server send
                        ]
                    ];
                    break;
                case TraceSpan::SPAN_KIND_PRODUCER:
                    $annotations = [
                        [
                            'endpoint' => $endpoint,
                            'timestamp' => $startTime,
                            'value' => 'ms' // message send
                        ]
                    ];
                    break;
                case TraceSpan::SPAN_KIND_CONSUMER:
                    $annotations = [
                        [
                            'endpoint' => $endpoint,
                            'timestamp' => $startTime,
                            'value' => 'mr' // message receive
                        ]
                    ];
                    break;
            }

            return [
                // 8-byte identifier encoded as 16 lowercase hex characters
                'id' => $spanId,
                'traceId' => $traceId,
                'name' => $span->name(),
                'timestamp' => $startTime,
                'duration' => $endTime - $startTime,
                'annotations' => $annotations,
                'binaryAnnotations' => array_map(function ($key, $value) {
                    return [
                        'key' => $key,
                        'value' => $value
                    ];
                }, array_keys($span->labels()), $span->labels()),
                'parentId' => $parentSpanId
            ];
        }, $spans);
    }
}
