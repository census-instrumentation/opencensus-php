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

namespace OpenCensus\Trace;

use OpenCensus\Trace\Reporter\ReporterInterface;
use OpenCensus\Trace\Sampler\SamplerInterface;
use OpenCensus\Trace\TraceSpan;
use OpenCensus\Trace\Tracer\ContextTracer;
use OpenCensus\Trace\Tracer\ExtensionTracer;
use OpenCensus\Trace\Tracer\NullTracer;
use OpenCensus\Trace\Tracer\TracerInterface;
use OpenCensus\Trace\Propagator\PropagatorInterface;

/**
 * This class manages the logic for sampling and reporting a trace within a
 * single request. It is not meant to be used directly -- instead, it should
 * be managed by the RequestTracer as its singleton instance.
 *
 * @internal
 */
class RequestHandler
{
    const DEFAULT_ROOT_SPAN_NAME = 'main';

    /**
     * @var ReporterInterface The reported to use at the end of the request
     */
    private $reporter;

    /**
     * @var TracerInterface The tracer to use for this request
     */
    private $tracer;

    /**
     * Create a new RequestHandler.
     *
     * @param ReporterInterface $reporter How to report the trace at the end of the request
     * @param SamplerInterface $sampler Which sampler to use for sampling requests
     * @param PropagatorInterface $propagator TraceContext propagator
     * @param array $options [optional] {
     *      Configuration options. See
     *      {@see OpenCensus\Trace\TraceSpan::__construct()} for the other available options.
     *
     *      @type array $headers Optional array of headers to use in place of $_SERVER
     * }
     */
    public function __construct(
        ReporterInterface $reporter,
        SamplerInterface $sampler,
        PropagatorInterface $propagator,
        array $options = []
    ) {
        $this->reporter = $reporter;
        $headers = array_key_exists('headers', $options)
            ? $options['headers']
            : $_SERVER;

        $context = $propagator->parse($headers);

        // If the context force disables tracing, don't consult the $sampler.
        if ($context->enabled() !== false) {
            $context->setEnabled($context->enabled() || $sampler->shouldSample());
        }

        // If the request was provided with a trace context header, we need to send it back with the response
        // including whether the request was sampled or not.
        if ($context->fromHeader()) {
            if (!headers_sent()) {
                header('X-Cloud-Trace-Context: ' . $propagator->serialize($context));
            }
        }

        $this->tracer = $context->enabled()
            ? extension_loaded('opencensus') ? new ExtensionTracer($context) : new ContextTracer($context)
            : new NullTracer();

        $spanOptions = $options + [
            'startTime' => $this->startTimeFromHeaders($headers),
            'name' => $this->nameFromHeaders($headers),
            'labels' => []
        ];
        $this->tracer->startSpan($spanOptions);

        register_shutdown_function([$this, 'onExit']);
    }

    /**
     * The function registered as the shutdown function. Cleans up the trace and
     * reports using the provided ReporterInterface. Adds additional labels to
     * the root span detected from the response.
     */
    public function onExit()
    {
        // close all open spans
        do {
            $span = $this->tracer->endSpan();
        } while ($span);
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
     * Instrument a callable by creating a TraceSpan that manages the startTime
     * and endTime. If an exception is thrown while executing the callable, the
     * exception will be caught, the span will be closed, and the exception will
     * be re-thrown.
     *
     * @param array $spanOptions Options for the span.
     *        {@see OpenCensus\Trace\TraceSpan::__construct()}
     * @param callable $callable    The callable to inSpan.
     * @return mixed Returns whatever the callable returns
     */
    public function inSpan(array $spanOptions, callable $callable, array $arguments = [])
    {
        return $this->tracer->inSpan($spanOptions, $callable, $arguments);
    }

    /**
     * Explicitly start a new TraceSpan. You will need to manage finishing the TraceSpan,
     * including handling any thrown exceptions.
     *
     * @param array $spanOptions [optional] Options for the span.
     *        {@see OpenCensus\Trace\TraceSpan::__construct()}
     * @return TraceSpan
     */
    public function startSpan(array $spanOptions = [])
    {
        return $this->tracer->startSpan($spanOptions);
    }

    /**
     * Explicitly finish the current context (TraceSpan).
     *
     * @return TraceSpan
     */
    public function endSpan()
    {
        return $this->tracer->endSpan();
    }

    /**
     * Return the current context (TraceSpan)
     *
     * @return TraceContext
     */
    public function context()
    {
        return $this->tracer->context();
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
