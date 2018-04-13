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

use OpenCensus\Core\Scope;
use OpenCensus\Trace\Annotation;
use OpenCensus\Trace\Link;
use OpenCensus\Trace\MessageEvent;
use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\SpanData;
use OpenCensus\Trace\Storage\ExtensionStorage;

/**
 * This implementation of the TracerInterface utilizes the opencensus extension
 * to manage span context. The opencensus extension augments user created spans and
 * adds automatic tracing to several commonly desired events.
 */
class ExtensionTracer implements TracerInterface
{
    /**
     * @var ExtensionStorage
     */
    private $storage;

    public function __construct(SpanContext $initialContext = null)
    {
        $this->storage = new ExtensionStorage($initialContext);
    }

    /**
     * Instrument a callable by creating a Span
     *
     * @param array $spanOptions Options for the span. See
     *      <a href="../Span.html#method___construct">OpenCensus\Trace\Span::__construct()</a>
     * @param callable $callable The callable to instrument.
     * @param array $arguments [optional] Arguments for the callable.
     * @return mixed The result of the callable
     */
    public function inSpan(array $spanOptions, callable $callable, array $arguments = [])
    {
        $span = $this->startSpan($spanOptions + [
            'sameProcessAsParentSpan' => $this->storage->hasAttachedSpans()
        ]);
        $scope = $this->withSpan($span);
        try {
            return call_user_func_array($callable, $arguments);
        } finally {
            $scope->close();
        }
    }

    /**
     * Start a new Span. The start time is already set to the current time.
     *
     * @param array $spanOptions [optional] Options for the span. See
     *      <a href="../Span.html#method___construct">OpenCensus\Trace\Span::__construct()</a>
     */
    public function startSpan(array $spanOptions)
    {
        if (!array_key_exists('name', $spanOptions)) {
            $spanOptions['name'] = $this->generateSpanName();
        }
        $spanOptions['storage'] = $this->storage;
        return new Span($spanOptions);
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
        $this->storage->attach($span);
        return new Scope(function () use ($span) {
            $this->storage->detach($span);
        });
    }

    /**
     * Return the spans collected.
     *
     * @return SpanData[]
     */
    public function spans()
    {
        return $this->storage->spans();
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
    public function addAttribute($attribute, $value, $options = [])
    {
        $span = $this->getSpan($options);
        $this->storage->addAttribute($span, $attribute, $value, $options);
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
    public function addAnnotation($description, $options = [])
    {
        $span = $this->getSpan($options);
        $this->storage->addAnnotation($span, new Annotation($description, $options));
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
    public function addLink($traceId, $spanId, $options = [])
    {
        $span = $this->getSpan($options);
        $this->storage->addLink($span, new Link($traceId, $spanId, $options));
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
    public function addMessageEvent($type, $id, $options = [])
    {
        $span = $this->getSpan($options);
        $this->storage->addMessageEvent($span, new MessageEvent($type, $id, $options));
    }

    /**
     * Returns the current SpanContext
     *
     * @return SpanContext
     */
    public function spanContext()
    {
        return $this->storage->spanContext();
    }

    /**
     * Whether or not this tracer is enabled.
     *
     * @return bool
     */
    public function enabled()
    {
        return $this->spanContext()->enabled();
    }

    /**
     * Generate a name for this span. Attempts to generate a name
     * based on the caller's code.
     *
     * @return string
     */
    private function generateSpanName()
    {
        // Try to find the first stacktrace class entry that doesn't start with OpenCensus\Trace
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $bt) {
            $bt += ['line' => null];
            if (!array_key_exists('class', $bt)) {
                return implode('/', array_filter(['app', basename($bt['file']), $bt['function'], $bt['line']]));
            } elseif (substr($bt['class'], 0, 18) != 'OpenCensus\Trace') {
                return implode('/', array_filter(['app', $bt['class'], $bt['function'], $bt['line']]));
            }
        }

        // We couldn't find a suitable backtrace entry - generate a random one
        return uniqid('span');
    }

    private function getSpan(array $options)
    {
        return array_key_exists('span', $options)
            ? $options['span']
            : new Span(['spanId' => $this->spanContext()->spanId()]);
    }
}
