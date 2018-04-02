<?php
/**
 * Copyright 2018 OpenCensus Authors
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

namespace OpenCensus\Trace;

/**
 * This plain PHP class represents a read-only version of a single timed event
 * within a Trace. Spans can be nested and form a trace tree. Often, a trace
 * contains a root span that describes the end-to-end latency of an operation
 * and, optionally, one or more subspans for its suboperations. Spans do not
 * need to be contiguous. There may be gaps between spans in a trace.
 */
class SpanData
{
    /**
     * Unique identifier for a trace. All spans from the same Trace share the
     * same `traceId`. 16-byte value encoded as a hex string.
     *
     * @var string
     */
    private $traceId;

    /**
     * Unique identifier for a span within a trace, assigned when the span is
     * created. 8-byte value encoded as a hex string.
     *
     * @var string
     */
    private $spanId;

    /**
     * The `spanId` of this span's parent span. If this is a root span, then
     * this field must be empty. 8-byte value encoded as a hex string.
     *
     * @var string
     */
    private $parentSpanId;

    /**
     * A description of the span's operation.
     *
     * For example, the name can be a qualified method name or a file name
     * and a line number where the operation is called. A best practice is to
     * use the same display name within an application and at the same call
     * point. This makes it easier to correlate spans in different traces.
     *
     * @var string
     */
    private $name;

    /**
     * @var array A set of attributes, each in the format `[KEY]:[VALUE]`.
     */
    private $attributes = [];

    /**
     * The start time of the span. On the client side, this is the time kept by
     * the local machine where the span execution starts. On the server side,
     * this is the time when the server's application handler starts running.
     *
     * @var \DateTimeInterface
     */
    private $startTime;


    /**
     * The end time of the span. On the client side, this is the time kept by
     * the local machine where the span execution ends. On the server side, this
     * is the time when the server application handler stops running.
     *
     * @var \DateTimeInterface
     */
    private $endTime;

    /**
     * Stack trace captured at the start of the span. This is in the format of
     * `debug_backtrace`.
     *
     * @var array
     */
    private $stackTrace = [];

    /**
     * A collection of `TimeEvent`s. A `TimeEvent` is a time-stamped annotation
     * on the span, consisting of either user-supplied key:value pairs, or
     * details of a message sent/received between Spans
     *
     * @var TimeEvent[]
     */
    private $timeEvents = [];

    /**
     * A collection of links, which are references from this span to a span
     * in the same or different trace.
     *
     * @var Link[]
     */
    private $links = [];

    /**
     * An optional final status for this span.
     *
     * @var Status
     */
    private $status;

    /**
     * A highly recommended but not required flag that identifies when a trace
     * crosses a process boundary. True when the parentSpanId belongs to the
     * same process as the current span.
     *
     * @var bool
     */
    private $sameProcessAsParentSpan;

    /**
     * Distinguishes between spans generated in a particular context. For
     * example, two spans with the same name may be distinguished using `CLIENT`
     * and `SERVER` to identify queueing latency associated with the span.
     *
     * @var string
     */
    private $kind;

    /**
     * An optional value to de-dup the stackTrace array. Represented as a
     * hexadecimal string.
     *
     * @var string
     */
    private $stackTraceHashId;

    /**
     * Instantiate a new SpanData instance.
     *
     * @param string $name The name of the span.
     * @param string $traceId The ID of the trace in hexadecimal.
     * @param string $spanId The ID of the span in hexadecimal.
     * @param \DateTimeInterface $startTime Start time of the span.
     * @param \DateTimeInterface $endTime End time of the span.
     * @param array $options [optional] Configuration options.
     *
     *      @type string $parentSpanId ID of the parent span if any in
     *            hexadecimal.
     *      @type array $attributes Associative array of $attribute => $value
     *            to attach to this span.
     *      @type array $stackTrace Stacktrace in `debug_backtrace` format.
     *      @type TimeEvent[] $timeEvents Timing events.
     *      @type Link[] $links Link references.
     *      @type Status $status The final status for this span.
     *      @type bool $sameProcessAsParentSpan True when the parentSpanId
     *            belongs to the same process as the current span.
     *      @type string $kind The span's type.
     */
    public function __construct(
        $name,
        $traceId,
        $spanId,
        \DateTimeInterface $startTime = null,
        \DateTimeInterface $endTime = null,
        array $options = []
    ) {
        $options += [
            'parentSpanId' => null,
            'attributes' => [],
            'timeEvents' => [],
            'links' => [],
            'status' => null,
            'sameProcessAsParentSpan' => null,
            'stackTrace' => [],
            'kind' => Span::KIND_UNSPECIFIED
        ];

        $this->name = $name;
        $this->traceId = $traceId;
        $this->spanId = $spanId;
        $this->startTime = $startTime;
        $this->endTime = $endTime;

        $this->parentSpanId = $options['parentSpanId'];
        $this->attributes = $options['attributes'];
        $this->stackTrace = $options['stackTrace'];
        $this->timeEvents = $options['timeEvents'];
        $this->links = $options['links'];
        $this->status = $options['status'];
        $this->sameProcessAsParentSpan = $options['sameProcessAsParentSpan'];
        $this->kind = $options['kind'];
    }

    /**
     * Retrieve the start time for this span.
     *
     * @return \DateTimeInterface
     */
    public function startTime()
    {
        return $this->startTime;
    }

    /**
     * Retrieve the end time for this span.
     *
     * @return \DateTimeInterface
     */
    public function endTime()
    {
        return $this->endTime;
    }

    /**
     * Retrieve the ID of this span's trace.
     *
     * @return string
     */
    public function traceId()
    {
        return $this->traceId;
    }

    /**
     * Retrieve the ID of this span.
     *
     * @return string
     */
    public function spanId()
    {
        return $this->spanId;
    }

    /**
     * Retrieve the ID of this span's parent if it exists.
     *
     * @return string
     */
    public function parentSpanId()
    {
        return $this->parentSpanId;
    }

    /**
     * Retrieve the name of this span.
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Return the attributes for this span.
     *
     * @return array
     */
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * Return the time events for this span.
     *
     * @return TimeEvent[]
     */
    public function timeEvents()
    {
        return $this->timeEvents;
    }

    /**
     * Return the links for this span.
     *
     * @return Link[]
     */
    public function links()
    {
        return $this->links;
    }

    /**
     * Retrieve the final status for this span.
     *
     * @return Status
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Retrieve the stackTrace at the moment this span was created
     *
     * @return array
     */
    public function stackTrace()
    {
        return $this->stackTrace;
    }

    /**
     * Return a hash id for this span's stackTrace in hexadecimal
     *
     * @return string
     */
    public function stackTraceHashId()
    {
        if (!isset($this->stackTraceHashId)) {
            // take the lower 16 digits of the md5
            $md5 = md5(serialize($this->stackTrace));
            $this->stackTraceHashId = substr($md5, 16);
        }
        return $this->stackTraceHashId;
    }

    /**
     * Whether or not this span is in the same process as its parent.
     *
     * @return bool
     */
    public function sameProcessAsParentSpan()
    {
        return $this->sameProcessAsParentSpan;
    }

    /**
     * Returns the span kind.
     *
     * @return string
     */
    public function kind()
    {
        return $this->kind;
    }
}
