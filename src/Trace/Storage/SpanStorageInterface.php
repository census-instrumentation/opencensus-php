<?php

namespace OpenCensus\Trace\Storage;

use OpenCensus\Trace\Annotation;
use OpenCensus\Trace\Link;
use OpenCensus\Trace\MessageEvent;
use OpenCensus\Trace\Span;

interface SpanStorageInterface
{
    public function addAttribute(Span $span, $attribute, $value);

    public function addAnnotation(Span $span, Annotation $annotation);

    public function addLink(Span $span, Link $link);

    public function addMessageEvent(Span $span, MessageEvent $messageEvent);

    public function spanContext();

    public function attach(Span $span);

    public function hasAttachedSpans();
}
