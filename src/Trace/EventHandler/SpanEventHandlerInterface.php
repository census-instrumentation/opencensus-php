<?php

namespace OpenCensus\Trace\EventHandler;

use OpenCensus\Trace\Annotation;
use OpenCensus\Trace\Link;
use OpenCensus\Trace\MessageEvent;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\TimeEvent;

interface SpanEventHandlerInterface
{
    public function attributeAdded(Span $span, $attribute, $value);

    public function linkAdded(Span $span, Link $link);

    public function timeEventAdded(Span $span, TimeEvent $timeEvent);
}
