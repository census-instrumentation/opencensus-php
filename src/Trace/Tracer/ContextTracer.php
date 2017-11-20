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
use OpenCensus\Trace\Span;
use OpenCensus\Trace\SpanContext;

/**
 * This implementation of the TracerInterface manages your trace context throughout
 * the request. It maintains a stack of `Span` records that are currently open
 * allowing you to know the current context at any moment.
 */
class ContextTracer implements TracerInterface
{
    /**
     * @var Span[] List of Spans to report
     */
    private $spans = [];

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
        $span = $this->startSpan($spanOptions);
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
            'parentSpanId' => $this->spanContext()->spanId(),
            'startTime' => microtime(true)
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
        array_push($this->spans, $span);
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
     * @return Span[]
     */
    public function spans()
    {
        return $this->spans;
    }

    /**
     * Add a attribute to the current Span
     *
     * @param string $attribute
     * @param string $value
     */
    public function addAttribute($attribute, $value)
    {
        $span = Context::current()->value('currentSpan');
        if ($span) {
            $span->addAttribute($attribute, $value);
        }
    }

    /**
     * Add a attribute to the primary Span
     *
     * @param string $attribute
     * @param string $value
     */
    public function addRootAttribute($attribute, $value)
    {
        if (!empty($this->spans)) {
            $this->spans[0]->addAttribute($attribute, $value);
        }
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
     * Returns the current SpanContext
     *
     * @return SpanContext
     */
    public function spanContext()
    {
        $context = Context::current();
        return new SpanContext(
            $context->value('traceId'),
            $context->value('spanId'),
            $context->value('enabled'),
            $context->value('fromHeader')
        );
    }
}
