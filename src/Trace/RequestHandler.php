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
    const ATTRIBUTE_MAP = [
        Span::ATTRIBUTE_HOST => ['HTTP_HOST', 'SERVER_NAME'],
        Span::ATTRIBUTE_PORT => ['SERVER_PORT'],
        Span::ATTRIBUTE_METHOD => ['REQUEST_METHOD'],
        Span::ATTRIBUTE_PATH => ['REQUEST_URI'],
        Span::ATTRIBUTE_USER_AGENT => ['HTTP_USER_AGENT']
    ];

    /**
     * @var ExporterInterface The reported to use at the end of the request
     */
    private $exporter;

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
     * @var array Replacement $_SERVER variables
     */
    private $headers;

    /**
     * Create a new RequestHandler.
     *
     * @param ExporterInterface $exporter How to report the trace at the end of the request
     * @param SamplerInterface $sampler Which sampler to use for sampling requests
     * @param PropagatorInterface $propagator SpanContext propagator
     * @param array $options [optional] {
     *      Configuration options. See
     *      <a href="Span.html#method___construct">OpenCensus\Trace\Span::__construct()</a>
     *      for the other available options.
     *
     *      @type array $headers Optional array of headers to use in place of $_SERVER
     *      @type bool $skipReporting If true, skips registering of onExit handler.
     * }
     */
    public function __construct(
        ExporterInterface $exporter,
        SamplerInterface $sampler,
        PropagatorInterface $propagator,
        array $options = []
    ) {
        $this->exporter = $exporter;
        $this->headers = array_key_exists('headers', $options)
            ? $options['headers']
            : $_SERVER;

        $spanContext = $propagator->extract($this->headers);

        // If the context force disables tracing, don't consult the $sampler.
        if ($spanContext->enabled() !== false) {
            $spanContext->setEnabled($spanContext->enabled() || $sampler->shouldSample());
        }

        // If the request was provided with a trace context header, we need to send it back with the response
        // including whether the request was sampled or not.
        if ($spanContext->fromHeader()) {
            $propagator->inject($spanContext, []);
        }

        $this->tracer = $spanContext->enabled()
            ? extension_loaded('opencensus') ? new ExtensionTracer($spanContext) : new ContextTracer($spanContext)
            : new NullTracer();

        $spanOptions = $options + [
            'startTime' => $this->startTimeFromHeaders($this->headers),
            'name' => $this->nameFromHeaders($this->headers),
            'attributes' => [],
            'kind' => Span::KIND_SERVER,
            'sameProcessAsParentSpan' => false
        ];
        $this->rootSpan = $this->tracer->startSpan($spanOptions);
        $this->scope = $this->tracer->withSpan($this->rootSpan);

        if (!array_key_exists('skipReporting', $options) || !$options['skipReporting']) {
            register_shutdown_function([$this, 'onExit']);
        }
    }

    /**
     * The function registered as the shutdown function. Cleans up the trace and
     * reports using the provided ExporterInterface. Adds additional attributes to
     * the root span detected from the response.
     */
    public function onExit(): void
    {
        $this->addCommonRequestAttributes($this->headers);

        $this->scope->close();

        $this->exporter->export($this->tracer->spans());
    }

    /**
     * Return the tracer used for this request.
     *
     * @return TracerInterface
     */
    public function tracer(): TracerInterface
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
    public function startSpan(array $spanOptions = []): Span
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
    public function withSpan(Span $span): Scope
    {
        return $this->tracer->withSpan($span);
    }

    /**
     * Add an attribute to the provided Span
     *
     * @param string $attribute
     * @param string $value
     * @param array $options [optional] Configuration options.
     *
     *      @type Span $span The span to add the attribute to.
     */
    public function addAttribute(string $attribute, string $value, array $options = []): void
    {
        $this->tracer->addAttribute($attribute, $value, $options);
    }

    /**
     * Add an annotation to the provided Span
     *
     * @param string $description
     * @param array $options [optional] Configuration options.
     *
     *      @type Span $span The span to add the annotation to.
     *      @type array $attributes Attributes for this annotation.
     *      @type \DateTimeInterface|int|float $time The time of this event.
     */
    public function addAnnotation(string $description, array $options = []): void
    {
        $this->tracer->addAnnotation($description, $options);
    }

    /**
     * Add a link to the provided Span
     *
     * @param string $traceId
     * @param string $spanId
     * @param array $options [optional] Configuration options.
     *
     *      @type Span $span The span to add the link to.
     *      @type string $type The relationship of the current span relative to
     *            the linked span: child, parent, or unspecified.
     *      @type array $attributes Attributes for this annotation.
     *      @type \DateTimeInterface|int|float $time The time of this event.
     */
    public function addLink(string $traceId, string $spanId, array $options = []): void
    {
        $this->tracer->addLink($traceId, $spanId, $options);
    }

    /**
     * Add an message event to the provided Span
     *
     * @param string $type
     * @param string $id
     * @param array $options [optional] Configuration options.
     *
     *      @type Span $span The span to add the message event to.
     *      @type int $uncompressedSize The number of uncompressed bytes sent or
     *            received.
     *      @type int $compressedSize The number of compressed bytes sent or
     *            received. If missing assumed to be the same size as
     *            uncompressed.
     *      @type \DateTimeInterface|int|float $time The time of this event.
     */
    public function addMessageEvent(string $type, string $id, array $options): void
    {
        $this->tracer->addMessageEvent($type, $id, $options);
    }

    public function addCommonRequestAttributes(array $headers): void
    {
        if ($responseCode = http_response_code()) {
            $this->rootSpan->setStatus($responseCode, "HTTP status code: $responseCode");
            $this->tracer->addAttribute(Span::ATTRIBUTE_STATUS_CODE, $responseCode, [
                'spanId' => $this->rootSpan->spanId()
            ]);
        }
        foreach (self::ATTRIBUTE_MAP as $attributeKey => $headerKeys) {
            if ($val = $this->detectKey($headerKeys, $headers)) {
                $this->tracer->addAttribute($attributeKey, $val, [
                    'spanId' => $this->rootSpan->spanId()
                ]);
            }
        }
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

    private function nameFromHeaders(array $headers): string
    {
        if (array_key_exists('REQUEST_URI', $headers)) {
            return $headers['REQUEST_URI'];
        }
        return self::DEFAULT_ROOT_SPAN_NAME;
    }

    private function detectKey(array $keys, array $array)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                return $array[$key];
            }
        }
        return null;
    }
}
