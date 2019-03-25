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

    /**
     * Create a new ContextTracer
     *
     * @param SpanContext|null $initialContext [optional] The starting span context.
     */
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

    public function inSpan(array $spanOptions, callable $callable, array $arguments = [])
    {
        $span = $this->startSpan($spanOptions + [
            'sameProcessAsParentSpan' => !empty($this->spans)
        ]);
        $scope = $this->withSpan($span);
        try {
            return call_user_func_array($callable, $arguments);
        } finally {
            $scope->close();
        }
    }

    public function startSpan(array $spanOptions = []): Span
    {
        $spanOptions += [
            'traceId' => $this->spanContext()->traceId(),
            'parentSpanId' => $this->spanContext()->spanId(),
            'startTime' => microtime(true)
        ];

        return new Span($spanOptions);
    }

    public function withSpan(Span $span): Scope
    {
        array_push($this->spans, $span);
        $prevContext = Context::current()
            ->withValues([
                'currentSpan' => $span,
                'spanId' => $span->spanId()
            ])
            ->attach();
        $span->attach();
        return new Scope(function () use ($prevContext) {
            $currentContext = Context::current();
            $span = $currentContext->value('currentSpan');
            if ($span) {
                $span->setEndTime();
            }
            $currentContext->detach($prevContext);
        });
    }

    public function spans(): array
    {
        return array_map(function (Span $span) {
            return $span->spanData();
        }, $this->spans);
    }

    public function addAttribute($attribute, $value, $options = []): void
    {
        $span = $this->getSpan($options);
        $span->addAttribute($attribute, $value);
    }

    public function addAnnotation($description, $options = []): void
    {
        $span = $this->getSpan($options);
        $span->addTimeEvent(new Annotation($description, $options));
    }

    public function addLink($traceId, $spanId, $options = []): void
    {
        $span = $this->getSpan($options);
        $span->addLink(new Link($traceId, $spanId, $options));
    }

    public function addMessageEvent($type, $id, $options = []): void
    {
        $span = $this->getSpan($options);
        $span->addTimeEvent(new MessageEvent($type, $id, $options));
    }

    public function spanContext(): SpanContext
    {
        $context = Context::current();
        return new SpanContext(
            $context->value('traceId'),
            $context->value('spanId'),
            $context->value('enabled'),
            $context->value('fromHeader')
        );
    }

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
