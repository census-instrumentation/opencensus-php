<?php

namespace OpenCensus\Trace\Storage;

use OpenCensus\Trace\Annotation;
use OpenCensus\Trace\DateFormatTrait;
use OpenCensus\Trace\Link;
use OpenCensus\Trace\MessageEvent;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\SpanData;

class ExtensionStorage implements SpanStorageInterface
{
    use DateFormatTrait;

    private $spans = [];

    public function __construct(SpanContext $initialContext = null)
    {
        if ($initialContext) {
            opencensus_trace_set_context($initialContext->traceId(), $initialContext->spanId());
        }
    }

    public function attach(Span $span)
    {
        $this->spans[$span->spanId()] = $span;

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
        foreach ($spanData->timeEvents() as $timeEvent) {
            if ($timeEvent instanceof Annotation) {
                $this->addAnnotation($span, $timeEvent);
            } elseif ($timeEvent instanceof MessageEvent) {
                $this->addMessageEvent($span, $timeEvent);
            }
        }
        foreach ($spanData->links() as $link) {
            $this->addLink($span, $link);
        }

        $this->hasSpans = true;
    }

    public function detach(Span $span)
    {
        opencensus_trace_finish();
    }

    public function addAttribute(Span $span, $attribute, $value)
    {
        if ($this->attached($span)) {
            opencensus_trace_add_attribute($attribute, $value, [
                'spanId' => $span->spanId()
            ]);
        }
    }

    public function addAnnotation(Span $span, Annotation $annotation)
    {
        if ($this->attached($span)) {
            opencensus_trace_add_annotation($annotation->description(), [
                'attributes' => $annotation->attributes(),
                'time' => $annotation->time(),
                'spanId' => $span->spanId()
            ]);
        }
    }

    public function addLink(Span $span, Link $link)
    {
        if ($this->attached($span)) {
            opencensus_trace_add_link($link->traceId(), $link->spanId(), [
                'attributes' => $link->attributes(),
                'type' => $link->type(),
                'spanId' => $span->spanId()
            ]);
        }
    }

    public function addMessageEvent(Span $span, MessageEvent $messageEvent)
    {
        if ($this->attached($span)) {
            opencensus_trace_add_message_event($messageEvent->type(), $messageEvent->id(), [
                'time' => $messageEvent->time(),
                'compressedSize' => $messageEvent->compressedSize(),
                'uncompressedSize' => $messageEvent->uncompressedSize(),
                'spanId' => $span->spanId()
            ]);
        }
    }

    public function spans()
    {
        // each span returned from opencensus_trace_list should be a
        // OpenCensus\Span object
        $traceId = $this->spanContext()->traceId();
        return array_map(function ($span) use ($traceId) {
            return $this->mapSpan($span, $traceId);
        }, opencensus_trace_list());
    }

    public function hasAttachedSpans()
    {
        return !empty(opencensus_trace_list());
    }

    public function spanContext()
    {
        $context = opencensus_trace_context();
        return new SpanContext(
            $context->traceId(),
            $context->spanId(),
            true
        );
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

    private function attached(Span $span)
    {
        return array_key_exists($span->spanId(), $this->spans);
    }
}
