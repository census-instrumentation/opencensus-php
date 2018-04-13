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

use OpenCensus\Core\Context;
use OpenCensus\Core\Scope;
use OpenCensus\Trace\Annotation;
use OpenCensus\Trace\Link;
use OpenCensus\Trace\MessageEvent;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Storage\MemoryStorage;

/**
 * This implementation of the TracerInterface manages your trace context throughout
 * the request. It maintains a stack of `Span` records that are currently open
 * allowing you to know the current context at any moment.
 */
class ContextTracer implements TracerInterface
{
    /**
     * @var MemoryStorage
     */
    private $storage;

    public function __construct(SpanContext $initialContext = null)
    {
        if ($initialContext) {
            Context::current()->withValues([
                'traceId' => $initialContext->traceId(),
                'spanId' => $initialContext->spanId(),
                'enabled' => $initialContext->enabled(),
                'fromHeader' => $initialContext->fromHeader()
            ])->attach();
        }
        $this->storage = new MemoryStorage();
    }

    /**
     * Instrument a callable by creating a Span that manages the startTime and endTime.
     *
     * @param array $spanOptions Options for the span. See
     *        <a href="../Span.html#method___construct">OpenCensus\Trace\Span::__construct()</a>
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
     * Create a new Span. The start time is already set to the current time.
     * The newly created span is not attached to the current context.
     *
     * @param array $spanOptions [optional] Options for the span. See
     *        <a href="../Span.html#method___construct">OpenCensus\Trace\Span::__construct()</a>
     * @return Span
     */
    public function startSpan(array $spanOptions = [])
    {
        $spanOptions += [
            'traceId' => $this->spanContext()->traceId(),
            'parentSpanId' => $this->spanContext()->spanId(),
            'startTime' => microtime(true),
            'storage' => $this->storage
        ];

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
        $prevContext = Context::current()
            ->withValues([
                'currentSpan' => $span,
                'spanId' => $span->spanId()
            ])
            ->attach();
        return new Scope(function () use ($prevContext) {
            $currentContext = Context::current();
            $span = $currentContext->value('currentSpan');
            if ($span) {
                $span->setEndTime();
            }
            $currentContext->detach($prevContext);
        });
    }

    /**
     * Return the spans collected.
     *
     * @return SpanData[]
     */
    public function spans()
    {
        return array_map(function ($span) {
            return $span->spanData();
        }, $this->storage->spans());
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
        $span->addAttribute($attribute, $value);
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
        $span->addTimeEvent(new Annotation($description, $options));
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
        $span->addLink(new Link($traceId, $spanId, $options));
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
        $span->addTimeEvent(new MessageEvent($type, $id, $options));
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

    private function getSpan($options = [])
    {
        return array_key_exists('span', $options)
            ? $options['span']
            : Context::current()->value('currentSpan');
    }
}
