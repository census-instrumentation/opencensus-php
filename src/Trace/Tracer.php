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

use OpenCensus\Core\Scope;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\Sampler\AlwaysSampleSampler;
use OpenCensus\Trace\Sampler\SamplerInterface;
use OpenCensus\Trace\Exporter\ExporterInterface;
use OpenCensus\Trace\Propagator\PropagatorInterface;
use OpenCensus\Trace\Propagator\HttpHeaderPropagator;

/**
 * This class provides static functions to give you access to the current
 * request's singleton tracer. You should use this class to instrument your code.
 * The first step, is to configure and start your `Tracer`. Calling `start`
 * will collect trace data during your request and report the results at the
 * request using the provided reporter.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Tracer;
 * use OpenCensus\Trace\Exporter\EchoExporter;
 *
 * $reporter = new EchoExporter();
 * Tracer::start($reporter);
 * ```
 *
 * In the above example, every request is traced. This is not advised as it will
 * add some latency to each request. We provide a sampling mechanism via the
 * <a href="Sampler/SamplerInterface.html">OpenCensus\Trace\Sampler\SamplerInterface</a>. To add sampling to your
 * request tracer, provide the "sampler" option:
 *
 * ```
 * // $cache is a PSR-6 cache implementation
 * $sampler = new QpsSampler($cache, ['rate' => 0.1]);
 * Tracer::start($reporter, [
 *     'sampler' => $sampler
 * ]);
 * ```
 *
 * The above uses a query-per-second sampler at 0.1 requests/second. The implementation
 * requires a PSR-6 cache. See
 * <a href="Sampler/QpsSampler.html">OpenCensus\Trace\Sampler\QpsSampler</a> for more information.
 * You may provide your own implementation of
 * <a href="Sampler/SamplerInterface.html">OpenCensus\Trace\Sampler\SamplerInterface</a>
 * or use one of the provided.
 *
 * To trace code, you can use static <a href="#method_inSpan">OpenCensus\Trace\Tracer::inSpan()</a> helper function:
 *
 * ```
 * Tracer::start($reporter);
 * Tracer::inSpan(['name' => 'outer'], function () {
 *     // some code
 *     Tracer::inSpan(['name' => 'inner'], function () {
 *         // some code
 *     });
 *     // some code
 * });
 * ```
 *
 * You can also start and finish spans independently throughout your code.
 *
 * Explicitly tracing spans:
 * ```
 * // Creates a detached span
 * $span = Tracer::startSpan(['name' => 'expensive-operation']);
 *
 * // Opens a scope that attaches the span to the current context
 * $scope = Tracer::withSpan($span);
 * try {
 *     $pi = calculatePi(1000);
 * } finally {
 *     // Closes the scope (ends the span)
 *     $scope->close();
 * }
 * ```
 *
 * It is recommended that you use the <a href="#method_inSpan">OpenCensus\Trace\Tracer::inSpan()</a>
 * method where you can.
 */
class Tracer
{
    /**
     * @var RequestHandler Singleton instance
     */
    private static $instance;

    /**
     * Start a new trace session for this request. You should call this as early as
     * possible for the most accurate results.
     *
     * @param ExporterInterface $reporter
     * @param array $options Configuration options. See
     *        <a href="Span.html#method___construct">OpenCensus\Trace\Span::__construct()</a>
     *        for the other available options.
     *
     *      @type SamplerInterface $sampler Sampler that defines the sampling rules.
     *            **Defaults to** a new `AlwaysSampleSampler`.
     *      @type PropagatorInterface $propagator SpanContext propagator. **Defaults to**
     *            a new `HttpHeaderPropagator` instance
     *      @type array $headers Optional array of headers to use in place of $_SERVER
     * @return RequestHandler
     */
    public static function start(ExporterInterface $reporter, array $options = [])
    {
        $sampler = array_key_exists('sampler', $options)
            ? $options['sampler']
            : new AlwaysSampleSampler();
        unset($options['sampler']);

        $propagator = array_key_exists('propagator', $options)
            ? $options['propagator']
            : new HttpHeaderPropagator();
        unset($options['propagator']);

        return self::$instance = new RequestHandler($reporter, $sampler, $propagator, $options);
    }

    /**
     * Instrument a callable by creating a Span that manages the startTime and endTime.
     * If an exception is thrown while executing the callable, the exception will be caught,
     * the span will be closed, and the exception will be re-thrown.
     *
     * Example:
     * ```
     * // Instrumenting code as a closure
     * Tracer::inSpan(['name' => 'some-closure'], function () {
     *   // do something expensive
     * });
     * ```
     *
     * ```
     * // Instrumenting code as a callable (parameters optional)
     * function fib($n) {
     *   // do something expensive
     * }
     * $number = Tracer::inSpan(['name' => 'some-callable'], 'fib', [10]);
     * ```
     *
     * @param array $spanOptions Options for the span. See
     *      <a href="Span.html#method___construct">OpenCensus\Trace\Span::__construct()</a>
     * @param callable $callable The callable to instrument.
     * @param array $arguments [optional] Arguments to the callable.
     * @return mixed Returns whatever the callable returns
     */
    public static function inSpan(array $spanOptions, callable $callable, array $arguments = [])
    {
        if (!isset(self::$instance)) {
            return call_user_func_array($callable, $arguments);
        }
        return self::$instance->inSpan($spanOptions, $callable, $arguments);
    }

    /**
     * Explicitly start a new Span. You will need to attach the span and handle
     * any thrown exceptions.
     *
     * Example:
     * ```
     * $span = Tracer::startSpan(['name' => 'expensive-operation']);
     * $scope = Tracer::withSpan($span);
     * try {
     *     // do something expensive
     * } catch (\Exception $e) {
     * } finally {
     *     $scope->close();
     * }
     * ```
     *
     * @param array $spanOptions [optional] Options for the span. See
     *      <a href="Span.html#method___construct">OpenCensus\Trace\Span::__construct()</a>
     * @return Span
     */
    public static function startSpan(array $spanOptions = [])
    {
        if (!isset(self::$instance)) {
            return new Span();
        }
        return self::$instance->startSpan($spanOptions);
    }

    /**
     * Attaches the provided span as the current span and returns a Scope
     * object which must be closed.
     *
     * Example:
     * ```
     * $span = Tracer::startSpan(['name' => 'expensive-operation']);
     * $scope = Tracer::withSpan($span);
     * try {
     *     // do something expensive
     * } finally {
     *     $scope->close();
     * }
     * ```
     *
     * @param Span $span
     * @return Scope
     */
    public static function withSpan(Span $span)
    {
        if (!isset(self::$instance)) {
            return new Scope(function () {
            });
        }
        return self::$instance->withSpan($span);
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
    public static function addAttribute($attribute, $value, $options = [])
    {
        if (!isset(self::$instance)) {
            return;
        }
        return self::$instance->addAttribute($attribute, $value, $options);
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
    public static function addAnnotation($description, $options = [])
    {
        if (!isset(self::$instance)) {
            return;
        }
        return self::$instance->addAnnotation($description, $options);
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
    public static function addLink($traceId, $spanId, $options = [])
    {
        if (!isset(self::$instance)) {
            return;
        }
        return self::$instance->addLink($traceId, $spanId, $options);
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
    public static function addMessageEvent($type, $id, $options = [])
    {
        if (!isset(self::$instance)) {
            return;
        }
        return self::$instance->addMessageEvent($type, $id, $options);
    }

    /**
     * Returns the current span context.
     *
     * @return SpanContext
     */
    public static function spanContext()
    {
        if (!isset(self::$instance)) {
            return new SpanContext(null, null, false);
        }
        return self::$instance->tracer()->spanContext();
    }
}
