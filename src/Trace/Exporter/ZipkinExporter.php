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

use OpenCensus\Trace\Tracer\TracerInterface;
use OpenCensus\Trace\Span;

/**
 * This implementation of the ExporterInterface appends a json
 * representation of the trace to a file.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Exporter\ZipkinExporter;
 * use OpenCensus\Trace\Tracer;
 *
 * $exporter = new ZipkinExporter('my_app', 'localhost', 9411);
 * Tracer::begin($exporter);
 * ```
 */
class ZipkinExporter implements ExporterInterface
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
     * Create a new ZipkinExporter
     *
     * @param string $name The name of this application
     * @param string $host The hostname of the Zipkin server
     * @param int $port The port of the Zipkin server
     * @param string $endpoint (optional) The path for the span reporting endpoint. **Defaults to** `/api/v2/spans`
     */
    public function __construct($name, $host, $port, $endpoint = '/api/v2/spans')
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
     * Convert spans into Zipkin's expected JSON output format. See
     * <a href="http://zipkin.io/zipkin-api/#/default/post_spans">output format definition</a>.
     *
     * @param TracerInterface $tracer
     * @param array $headers [optional] HTTP headers to parse. **Defaults to** $_SERVER
     * @return array Representation of the collected trace spans ready for serialization
     */
    public function convertSpans(TracerInterface $tracer, $headers = null)
    {
        $headers = $headers ?: $_SERVER;
        $spans = $tracer->spans();
        $traceId = $tracer->spanContext()->traceId();

        // True is a request to store this span even if it overrides sampling policy.
        // This is true when the X-B3-Flags header has a value of 1.
        $isDebug = array_key_exists('HTTP_X_B3_FLAGS', $headers) && $headers['HTTP_X_B3_FLAGS'] == '1';

        // True if we are contributing to a span started by another tracer (ex on a different host).
        $isShared = !empty($spans) && $spans[0]->parentSpanId() != null;

        $localEndpoint = [
            'serviceName' => $this->name,
            'ipv4' => $this->host,
            'port' => $this->port
        ];

        $zipkinSpans = [];
        foreach ($spans as $span) {
            $startTime = (int)((float) $span->startTime()->format('U.u') * 1000 * 1000);
            $endTime = (int)((float) $span->endTime()->format('U.u') * 1000 * 1000);
            $spanId = str_pad($span->spanId(), 16, '0', STR_PAD_LEFT);
            $parentSpanId = $span->parentSpanId()
                ? str_pad($span->parentSpanId(), 16, '0', STR_PAD_LEFT)
                : null;

            $attributes = $span->attributes();
            if (empty($attributes)) {
                // force json_encode to render an empty object ("{}") instead of an empty array ("[]")
                $attributes = new \stdClass();
            }

            $zipkinSpan = [
                'traceId' => $traceId,
                'name' => $span->name(),
                'parentId' => $parentSpanId,
                'id' => $spanId,
                'timestamp' => $startTime,
                'duration' => $endTime - $startTime,
                'debug' => $isDebug,
                'shared' => $isShared,
                'localEndpoint' => $localEndpoint,
                'tags' => $attributes
            ];

            $zipkinSpans[] = $zipkinSpan;
        }

        return $zipkinSpans;
    }
}
