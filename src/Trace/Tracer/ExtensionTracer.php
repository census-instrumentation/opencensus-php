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
use OpenCensus\Trace\TimeEvent;
use OpenCensus\Trace\DateFormatTrait;
use OpenCensus\Trace\EventHandler\SpanEventHandlerInterface;

/**
 * This implementation of the TracerInterface utilizes the opencensus extension
 * to manage span context. The opencensus extension augments user created spans and
 * adds automatic tracing to several commonly desired events.
 */
class ExtensionTracer implements TracerInterface, SpanEventHandlerInterface
{
    use DateFormatTrait;

    /**
     * @var bool
     */
    private $hasSpans = false;

    /**
     * Create a new ExtensionTracer
     *
     * @param SpanContext|null $initialContext [optional] The starting span
     *     context.
     */
    public function __construct(SpanContext $initialContext = null)
    {
        if ($initialContext) {
            opencensus_trace_set_context($initialContext->traceId(), $initialContext->spanId());
        }
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
            'sameProcessAsParentSpan' => $this->hasSpans
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
        $spanOptions['eventHandler'] = $this;
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
        $spanData = $span->spanData();
        $startTime = $spanData->startTime()
            ? (float)($spanData->startTime()->format('U.u'))
            : microtime(true);
        $info = [
            'traceId' => $spanData->traceId(),
            'spanId' => $spanData->spanId(),
            'parentSpanId' => $spanData->parentSpanId(),
            'startTime' => $startTime,
            'attributes' => $spanData->attributes(),
            'stackTrace' => $spanData->stackTrace(),
            'kind' => $spanData->kind(),
            'sameProcessAsParentSpan' => $spanData->sameProcessAsParentSpan()
        ];
        opencensus_trace_begin($spanData->name(), $info);
        $this->hasSpans = true;
        $span->attach();
        foreach ($spanData->timeEvents() as $timeEvent) {
            $this->timeEventAdded($span, $timeEvent);
        }
        foreach ($spanData->links() as $link) {
            $this->linkAdded($span, $link);
        }
        return new Scope(function () {
            opencensus_trace_finish();
        });
    }

    /**
     * Return the spans collected.
     *
     * @return Span[]
     */
    public function spans()
    {
        // each span returned from opencensus_trace_list should be a
        // OpenCensus\Span object
        $traceId = $this->spanContext()->traceId();
        return array_map(function ($span) use ($traceId) {
            return $this->mapSpan($span, $traceId);
        }, opencensus_trace_list());
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
        if (array_key_exists('span', $options)) {
            $options['spanId'] = $options['span']->spanId();
        }
        opencensus_trace_add_attribute($attribute, $value, $options);
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
        if (array_key_exists('span', $options)) {
            $options['spanId'] = $options['span']->spanId();
        }
        opencensus_trace_add_annotation($description, $options);
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
        if (array_key_exists('span', $options)) {
            $options['spanId'] = $options['span']->spanId();
        }
        opencensus_trace_add_link($traceId, $spanId, $options);
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
        if (array_key_exists('span', $options)) {
            $options['spanId'] = $options['span']->spanId();
        }
        opencensus_trace_add_message_event($type, $id, $options);
    }

    /**
     * Returns the current SpanContext
     *
     * @return SpanContext
     */
    public function spanContext()
    {
        $context = opencensus_trace_context();
        return new SpanContext(
            $context->traceId(),
            $context->spanId(),
            true
        );
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
     * Triggers when an attribute is added to a span.
     *
     * @param Span $span The span the attribute was added to
     * @param string $attribute The name of the attribute added
     * @param string $value The attribute value
     */
    public function attributeAdded(Span $span, $attribute, $value)
    {
        // If the span is already attached (managed by the extension), then
        // tell the extension to add the attribute.
        if ($span->attached()) {
            $this->addAttribute($attribute, $value, [
                'span' => $span
            ]);
        }
    }

    /**
     * Triggers when a link is added to a span.
     *
     * @param Span $span The span the link was added to
     * @param Link $link The link added to the span
     */
    public function linkAdded(Span $span, Link $link)
    {
        // If the span is already attached (managed by the extension), then
        // tell the extension to add the link.
        if ($span->attached()) {
            $this->addLink($link->traceId(), $link->spanId(), [
                'type' => $link->type(),
                'attributes' => $link->attributes(),
                'span' => $span
            ]);
        }
    }

    /**
     * Triggers when a time event is added to a span.
     *
     * @param Span $span The span the time event was added to
     * @param TimeEvent $timeEvent The time event added to the span
     */
    public function timeEventAdded(Span $span, TimeEvent $timeEvent)
    {
        if ($span->attached()) {
            if ($timeEvent instanceof Annotation) {
                $this->addAnnotation($timeEvent->description(), [
                    'time' => $timeEvent->time(),
                    'attributes' => $timeEvent->attributes(),
                    'span' => $span
                ]);
            } elseif ($timeEvent instanceof MessageEvent) {
                $this->addMessageEvent($timeEvent->type(), $timeEvent->id(), [
                    'time' => $timeEvent->time(),
                    'uncompressedSize' => $timeEvent->uncompressedSize(),
                    'compressedSize' => $timeEvent->compressedSize(),
                    'span' => $span
                ]);
            }
        }
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

    private function mapSpan($span, $traceId)
    {
        return new SpanData(
            $span->name(),
            $traceId,
            $span->spanId(),
            $this->formatFloatTimeToDate($span->startTime()),
            $this->formatFloatTimeToDate($span->endTime()),
            [
                'parentSpanId' => $span->parentSpanId(),
                'attributes' => $span->attributes(),
                'stackTrace' => $span->stackTrace(),
                'links' => array_map([$this, 'mapLink'], $span->links()),
                'timeEvents' => array_map([$this, 'mapTimeEvent'], $span->timeEvents()),
                'kind' => $this->getKind($span),
                'sameProcessAsParentSpan' => $this->getSameProcessAsParentSpan($span)
            ]
        );
    }

    private function getKind($span)
    {
        if (method_exists($span, 'kind')) {
            return $span->kind();
        }
        return Span::KIND_UNSPECIFIED;
    }

    private function getSameProcessAsParentSpan($span)
    {
        if (method_exists($span, 'sameProcessAsParentSpan')) {
            return $span->sameProcessAsParentSpan();
        }
        return true;
    }

    private function mapLink($link)
    {
        return new Link($link->traceId(), $link->spanId(), $link->options());
    }

    private function mapTimeEvent($timeEvent)
    {
        $options = $timeEvent->options();
        $options['time'] = $timeEvent->time();

        switch (get_class($timeEvent)) {
            case 'OpenCensus\Trace\Ext\Annotation':
                return new Annotation($timeEvent->description(), $options);
                break;
            case 'OpenCensus\Trace\Ext\MessageEvent':
                return new MessageEvent($timeEvent->type(), $timeEvent->id(), $options);
                break;
        }
        return null;
    }
}
