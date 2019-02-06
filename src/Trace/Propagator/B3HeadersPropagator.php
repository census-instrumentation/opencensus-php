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

    public function extract($container): SpanContext
    {
        $traceId = $container[self::X_B3_TRACE_ID] ?? null;
        $spanId = $container[self::X_B3_SPAN_ID] ?? null;
        $sampled = $container[self::X_B3_SAMPLED] ?? null;
        $flags = $container[self::X_B3_FLAGS] ?? null;

        $enabled = null;

        if ($sampled !== null) {
            $enabled = ($sampled === '1' || $sampled === 'true');
        }

        if ($flags === '1') {
            $enabled = true;
        }

        return new SpanContext($traceId, $spanId, $enabled, true);
    }

    public function inject(SpanContext $context, &$container): void
    {
        $container[self::X_B3_TRACE_ID] = $context->traceId();
        $container[self::X_B3_SPAN_ID] = $context->spanId();
        $container[self::X_B3_SAMPLED] = $context->enabled() ? 1 : 0;
    }
}
