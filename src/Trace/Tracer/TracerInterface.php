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

namespace OpenCensus\Trace\Tracer;

use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\SpanData;

/**
 * This interface allows you to use the null object pattern for your tracer.
 * Whenever you want to instrument your code, you don't need to check if
 * the configured tracer is null or a real tracer.
 */
interface TracerInterface
{
    /**
     * Instrument a callable by creating a Span
     *
     * @param array $spanOptions Options for the span.
     *      <a href="../Span.html#method___construct">OpenCensus\Trace\Span::__construct()</a>
     * @param callable $callable The callable to instrument.
     * @param array $arguments [optional] Arguments for the callable.
     * @return mixed The result of the callable
     */
    public function inSpan(array $spanOptions, callable $callable, array $arguments = []);

    /**
     * Start a new Span. The start time is already set to the current time.
     * The newly created span is not attached to the current context.
     *
     * @param  array  $spanOptions [description]
     */
    public function startSpan(array $spanOptions);

    /**
     * Attaches the provided span as the current span and returns a Scope
     * object which must be closed.
     *
     * @param Span $span
     * @return Scope
     */
    public function withSpan(Span $span);

    /**
     * Return the spans collected.
     *
     * @return SpanData[]
     */
    public function spans();

    /**
     * Add an attribute to the provided Span
     *
     * @param string $attribute
     * @param string $value
     * @param array $options [optional] Configuration options.
     *
     *      @type Span $span The span to add the attribute to.
     */
    public function addAttribute($attribute, $value, $options = []);

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
    public function addAnnotation($description, $options = []);

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
    public function addLink($traceId, $spanId, $options = []);

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
    public function addMessageEvent($type, $id, $options = []);

    /**
     * Returns the current SpanContext
     *
     * @return SpanContext
     */
    public function spanContext();

    /**
     * Whether or not this tracer is enabled.
     *
     * @return bool
     */
    public function enabled();
}
