<?php

namespace OpenCensus\Trace\EventHandler;

use OpenCensus\Trace\Link;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\TimeEvent;

/**
 * This interface defines events that are triggered when a Span is updated.
 */
interface SpanEventHandlerInterface
{
    /**
     * Triggers when an attribute is added to a span.
     *
     * @param Span $span The span the attribute was added to
     * @param string $attribute The name of the attribute added
     * @param string $value The attribute value
     */
    public function attributeAdded(Span $span, $attribute, $value);

    /**
     * Triggers when a link is added to a span.
     *
     * @param Span $span The span the link was added to
     * @param Link $link The link added to the span
     */
    public function linkAdded(Span $span, Link $link);

    /**
     * Triggers when a time event is added to a span.
     *
     * @param Span $span The span the time event was added to
     * @param TimeEvent $timeEvent The time event added to the span
     */
    public function timeEventAdded(Span $span, TimeEvent $timeEvent);
}
