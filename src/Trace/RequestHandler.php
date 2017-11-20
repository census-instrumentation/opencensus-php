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

namespace OpenCensus\Trace;

use OpenCensus\Core\Context;
use OpenCensus\Core\Scope;
use OpenCensus\Trace\Exporter\ExporterInterface;
use OpenCensus\Trace\Sampler\SamplerInterface;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\Tracer\ContextTracer;
use OpenCensus\Trace\Tracer\ExtensionTracer;
use OpenCensus\Trace\Tracer\NullTracer;
use OpenCensus\Trace\Tracer\TracerInterface;
use OpenCensus\Trace\Propagator\PropagatorInterface;

/**
 * This class manages the logic for sampling and reporting a trace within a
 * single request. It is not meant to be used directly -- instead, it should
 * be managed by the Tracer as its singleton instance.
 */
class RequestHandler
{
    const DEFAULT_ROOT_SPAN_NAME = 'main';

    /**
     * @var ExporterInterface The reported to use at the end of the request
     */
    private $reporter;

    /**
     * @var TracerInterface The tracer to use for this request
     */
    private $tracer;

    /**
     * @var Span The primary span for this request
     */
    private $rootSpan;

    /**
     * @var Scope
     */
    private $scope;

    /**
     * Create a new RequestHandler.
     *
     * @param ExporterInterface $reporter How to report the trace at the end of the request
     * @param SamplerInterface $sampler Which sampler to use for sampling requests
     * @param PropagatorInterface $propagator SpanContext propagator
     * @param array $options [optional] {
     *      Configuration options. See
     *      <a href="Span.html#method___construct">OpenCensus\Trace\Span::__construct()</a>
     *      for the other available options.
     *
     *      @type array $headers Optional array of headers to use in place of $_SERVER
     * }
     */
    public function __construct(
        ExporterInterface $reporter,
        SamplerInterface $sampler,
        PropagatorInterface $propagator,
        array $options = []
    ) {
        $this->reporter = $reporter;
        $headers = array_key_exists('headers', $options)
            ? $options['headers']
            : $_SERVER;

        $spanContext = $propagator->extract($headers);

        // If the context force disables tracing, don't consult the $sampler.
        if ($spanContext->enabled() !== false) {
            $spanContext->setEnabled($spanContext->enabled() || $sampler->shouldSample());
        }

        // If the request was provided with a trace context header, we need to send it back with the response
        // including whether the request was sampled or not.
        if ($spanContext->fromHeader()) {
            if (!headers_sent()) {
                foreach ($propagator->inject($spanContext, $headers) as $header => $value) {
                    header("$header: $value");
                }
            }
        }

        $this->tracer = $spanContext->enabled()
            ? extension_loaded('opencensus') ? new ExtensionTracer($spanContext) : new ContextTracer($spanContext)
            : new NullTracer();

        $spanOptions = $options + [
            'startTime' => $this->startTimeFromHeaders($headers),
            'name' => $this->nameFromHeaders($headers),
            'attributes' => []
        ];
        $this->rootSpan = $this->tracer->startSpan($spanOptions);
        $this->scope = $this->tracer->withSpan($this->rootSpan);

        register_shutdown_function([$this, 'onExit']);
    }

    /**
     * The function registered as the shutdown function. Cleans up the trace and
     * reports using the provided ExporterInterface. Adds additional attributes to
     * the root span detected from the response.
     */
    public function onExit()
    {
        $responseCode = http_response_code();
        if ($responseCode == 301 || $responseCode == 302) {
            foreach (headers_list() as $header) {
                if (substr($header, 0, 9) == 'Location:') {
                    $this->rootSpan->addAttribute(self::HTTP_REDIRECTED_URL, substr($header, 10));
                    break;
                }
            }
        }
        $this->rootSpan->setStatus($responseCode, "HTTP status code: $responseCode");

        $this->scope->close();
        $this->reporter->report($this->tracer);
    }

    /**
     * Return the tracer used for this request.
     *
     * @return TracerInterface
     */
    public function tracer()
    {
        return $this->tracer;
    }

    /**
     * Instrument a callable by creating a Span that manages the startTime
     * and endTime. If an exception is thrown while executing the callable, the
     * exception will be caught, the span will be closed, and the exception will
     * be re-thrown.
     *
     * @param array $spanOptions Options for the span. See
     *        <a href="Span.html#method___construct">OpenCensus\Trace\Span::__construct()</a>
     * @param callable $callable The callable to instrument.
     * @return mixed Returns whatever the callable returns
     */
    public function inSpan(array $spanOptions, callable $callable, array $arguments = [])
    {
        return $this->tracer->inSpan($spanOptions, $callable, $arguments);
    }

    /**
     * Explicitly start a new Span. You will need to manage finishing the Span,
     * including handling any thrown exceptions.
     *
     * @param array $spanOptions [optional] Options for the span. See
     *        <a href="Span.html#method___construct">OpenCensus\Trace\Span::__construct()</a>
     * @return Span
     */
    public function startSpan(array $spanOptions = [])
    {
        return $this->tracer->startSpan($spanOptions);
    }

    /**
     * Attaches the provided span as the current span and returns a Scope
     * object which must be closed.
     *
     * @param Span $span
     * @return Scope
     */
    public function withSpan(Span $span)
    {
        return $this->tracer->withSpan($span);
    }

    private function startTimeFromHeaders(array $headers)
    {
        if (array_key_exists('REQUEST_TIME_FLOAT', $headers)) {
            return $headers['REQUEST_TIME_FLOAT'];
        }
        if (array_key_exists('REQUEST_TIME', $headers)) {
            return $headers['REQUEST_TIME'];
        }
        return null;
    }

    private function nameFromHeaders(array $headers)
    {
        if (array_key_exists('REQUEST_URI', $headers)) {
            return $headers['REQUEST_URI'];
        }
        return self::DEFAULT_ROOT_SPAN_NAME;
    }
}
