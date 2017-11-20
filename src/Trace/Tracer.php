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
use OpenCensus\Trace\Sampler\SamplerFactory;
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
 * requires a PSR-6 cache. See <a href="Sampler/QpsSampler.html">OpenCensus\Trace\Sampler\QpsSampler</a> for more information.
 * You may provide your own implementation of <a href="Sampler/SamplerInterface.html">OpenCensus\Trace\Sampler\SamplerInterface</a>
 * or use one of the provided. You may provide a configuration array for the sampler instead. See
 * <a href="Sampler/SamplerFactory.html#method_build">OpenCensus\Trace\Sampler\SamplerFactory::build()</a> for builder options:
 *
 * ```
 * // $cache is a PSR-6 cache implementation
 * Tracer::start($reporter, [
 *     'sampler' => [
 *         'type' => 'qps',
 *         'rate' => 0.1,
 *         'cache' => $cache
 *     ]
 * ]);
 * ```
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
     * @param array $options Configuration options. See <a href="Span.html#method___construct">OpenCensus\Trace\Span::__construct()</a>
     *        for the other available options.
     *
     *      @type SamplerInterface|array $sampler Sampler or sampler factory build arguments. See
     *          <a href="Sampler/SamplerFactory.html#method_build">OpenCensus\Trace\Sampler\SamplerFactory::build()</a>
     *          for the available options.
     *      @type PropagatorInterface $propagator SpanContext propagator. **Defaults to**
     *            a new `HttpHeaderPropagator` instance
     *      @type array $headers Optional array of headers to use in place of $_SERVER
     * @return RequestHandler
     */
    public static function start(ExporterInterface $reporter, array $options = [])
    {
        $samplerOptions = array_key_exists('sampler', $options) ? $options['sampler'] : [];
        unset($options['sampler']);

        $sampler = ($samplerOptions instanceof SamplerInterface)
            ? $samplerOptions
            : SamplerFactory::build($samplerOptions);

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
        return self::$instance->startSpan($spanOptions);
    }

    /**
     * Attaches the provided span as the current span and returns a Scope
     * object which must be closed.
     *
     * Example:
     * ```
     * $span = RequestTracer::startSpan(['name' => 'expensive-operation']);
     * $scope = RequestTracer::withSpan($span);
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
        return self::$instance->withSpan($span);
    }
}
