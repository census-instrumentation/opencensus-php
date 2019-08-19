<?php

namespace OpenCensus\Trace\Ext;

/**
 *
 * @param string $type
 * @param string $message
 * @return bool
 */
function opencensus_core_send_to_daemonclient(string $type, string $message): bool {
    return true;
}

/**
 * Usage: https://github.com/nenadstojanovikj/opencensus-php/tree/master/ext#watching-for-functionmethod-invocations
 * @param string $functionName
 * @param callable $handler
 * @return bool Returns success of operation
 */
function opencensus_trace_function(string $functionName, callable $handler): bool {
    return true;
}

/**
 * Usage: https://github.com/nenadstojanovikj/opencensus-php/tree/master/ext#watching-for-functionmethod-invocations
 * @param string $className
 * @param string $methodName
 * @param callable $handler
 * @return bool Returns success of operation
 */
function opencensus_trace_method(string $className, string $methodName, callable $handler): bool {
    return true;
}

/**
 * Start a trace span. The current trace span (if any) will be set as this span's parent.
 *
 * @param string $spanName
 * @param array $spanOptions
 * @return bool Returns true if the span has been created
 */
function opencensus_trace_begin(string $spanName, array $spanOptions): bool {
    return true;
}

/**
 * Finish the current trace span. The previous trace span (if any) will be set as the current trace span.
 *
 * @return bool Returns true if the span has been finished
 */
function opencensus_trace_finish(): bool {
    return true;
}


/**
 * Retrieve the list of collected trace spans
 *
 * @return Span[]
 */
function opencensus_trace_list(): array {
    return [];
}

/**
 * Clear the list of collected trace spans
 *
 * @return bool Returns true if clear was successful
 */
function opencensus_trace_clear(): bool {
    return true;
}

/**
 * Fetch the current trace context
 *
 * @return SpanContext
 */
function opencensus_trace_context(): SpanContext {
    return new SpanContext();
}

/**
 * Set the initial trace context
 *
 * @param string $traceId The trace id for this request. **Defaults to** a generated value.
 * @param string $parentSpanId [optional] The span id of this request's parent. **Defaults to** `null`.
 */
function opencensus_trace_set_context($traceId, $parentSpanId = null): void {

}

/**
 * Add an attribute to a span.
 *
 * @param string $key
 * @param string $value
 * @param array $options
 *
 *      @type int $spanId The id of the span to which to add the attribute.
 *            Defaults to the current span.
 */
function opencensus_trace_add_attribute($key, $value, $options = []): void {

}
/**
 * Add an annotation to a span
 * @param string $description
 * @param array $options
 *
 *      @type int $spanId The id of the span to which to add the attribute.
 *            Defaults to the current span.
 */
function opencensus_trace_add_annotation($description, $options = []): void {

}
/**
 * Add a link to a span
 * @param string $traceId
 * @param string $spanId
 * @param array $options
 *
 *      @type int $spanId The id of the span to which to add the link.
 *            Defaults to the current span.
 */
function opencensus_trace_add_link($traceId, $spanId, $options = []): void {

}
/**
 * Add a message to a span
 * @param string $type
 * @param string $id
 * @param array $options
 *
 *      @type int $spanId The id of the span to which to add the attribute.
 *            Defaults to the current span.
 */
function opencensus_trace_add_message_event($type, $id, $options = []): void {

}

/**
 * Return the current version of the opencensus_trace extension
 *
 * @return string
 */
function opencensus_trace_version(): string {
    return '1';
}
