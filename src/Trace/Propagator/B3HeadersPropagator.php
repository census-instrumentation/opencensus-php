<?php

namespace OpenCensus\Trace\Propagator;

use OpenCensus\Trace\SpanContext;

/**
 * @see https://github.com/openzipkin/b3-propagation
 */
class B3HeadersPropagator implements PropagatorInterface
{
    private const X_B3_TRACE_ID = 'X-B3-TraceId';
    private const X_B3_SPAN_ID = 'X-B3-SpanId';
    private const X_B3_SAMPLED = 'X-B3-Sampled';
    private const X_B3_FLAGS = 'X-B3-Flags';

    public function extract(HeaderGetter $headers): SpanContext
    {
        $traceId = $headers->get(self::X_B3_TRACE_ID);
        $spanId = $headers->get(self::X_B3_SPAN_ID);
        $sampled = $headers->get(self::X_B3_SAMPLED);
        $flags = $headers->get(self::X_B3_FLAGS);

        if (!$traceId || !$spanId) {
            return new SpanContext();
        }

        $enabled = null;

        if ($sampled !== null) {
            $enabled = ($sampled === '1' || $sampled === 'true');
        }

        if ($flags === '1') {
            $enabled = true;
        }

        return new SpanContext($traceId, $spanId, $enabled, true);
    }

    public function inject(SpanContext $context, HeaderSetter $headers): void
    {
        $headers->set(self::X_B3_TRACE_ID, $context->traceId());
        $headers->set(self::X_B3_SPAN_ID, $context->spanId() ?? '');
        $headers->set(self::X_B3_SAMPLED, $context->enabled() ? 1 : 0);
    }
}
