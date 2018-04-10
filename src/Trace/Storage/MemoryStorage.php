<?php

namespace OpenCensus\Trace\Storage;

use OpenCensus\Trace\Annotation;
use OpenCensus\Trace\AttributeTrait;
use OpenCensus\Trace\Link;
use OpenCensus\Trace\MessageEvent;
use OpenCensus\Trace\Span;

class MemoryStorage implements SpanStorageInterface
{
    /**
     * @var Span[]
     */
    private $spans = [];

    /**
     * @var array Associative array of spans keyed by id.
     */
    private $spansById = [];

    public function addAttribute(Span $span, $attribute, $value)
    {
        $this->spansById[$span->spanId()] = $span;
    }

    public function addAnnotation(Span $span, Annotation $annotation)
    {
        $this->spansById[$span->spanId()] = $span;
    }

    public function addLink(Span $span, Link $link)
    {
        $this->spansById[$span->spanId()] = $span;
    }

    public function addMessageEvent(Span $span, MessageEvent $messageEvent)
    {
        $this->spansById[$span->spanId()] = $span;
    }

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

    public function attach(Span $span)
    {
        $this->spans[] = $span;
    }

    public function spans()
    {
        return $this->spans;
    }

    public function hasAttachedSpans()
    {
        return !empty($this->spans);
    }
}
