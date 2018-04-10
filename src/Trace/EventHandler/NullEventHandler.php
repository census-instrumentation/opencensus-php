<?php

namespace OpenCensus\Trace\EventHandler;

use OpenCensus\Trace\Annotation;
use OpenCensus\Trace\Link;
use OpenCensus\Trace\MessageEvent;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\TimeEvent;

class NullEventHandler implements SpanEventHandlerInterface
{
    public function attributeAdded(Span $span, $attribute, $value)
    {
    }

    public function annotationAdded(Span $span, Annotation $annotation)
    {
    }

    public function linkAdded(Span $span, Link $link)
    {
    }

    public function messageEventAdded(Span $span, MessageEvent $messageEvent)
    {
    }

    public function timeEventAdded(Span $span, TimeEvent $timeEvent)
    {
    }
}
