<?php
/**
 * Copyright 2017 OpenCensus Authors
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
 * This plain PHP class represents a Trace resource. For more information see
 * [TraceResource](https://cloud.google.com/trace/docs/reference/v1/rest/v1/projects.traces#resource-trace)
 */
class Trace implements \Serializable
{
    use IdGeneratorTrait;

    /**
     * @var string The trace id for this trace. 128-bit numeric formatted as a 32-byte hex string
     */
    private $traceId;

    /**
     * @var TraceSpan[] List of TraceSpans to report
     */
    private $spans = [];

    /**
     * Instantiate a new Trace instance.
     *
     * @param string $traceId [optional] The id of the trace. If not provided, one will be generated
     *        automatically for you.
     * @param array $spans [optional] Array of TraceSpan constructor arguments. See
     *        {@see OpenCensus\Trace\TraceSpan::__construct()} for configuration details.
     * }
     */
    public function __construct($traceId = null, $spans = null)
    {
        $this->traceId = $traceId ?: $this->generateTraceId();
        if ($spans) {
            $this->spans = array_map(function ($span) {
                return new TraceSpan($span);
            }, $spans);
        }
    }

    /**
     * Retrieves the trace's id.
     *
     * @return string
     */
    public function traceId()
    {
        return $this->traceId;
    }

    /**
     * Returns a serializable array representing this trace. If no span data
     * is cached, a network request will be made to retrieve it.
     *
     * @see https://cloud.google.com/trace/docs/reference/v1/rest/v1/projects.traces/get Traces get API documentation.
     *
     * @param array $options [optional] Configuration Options
     * @return array
     */
    public function info(array $options = [])
    {
        // We don't want to maintain both an info array and array of TraceSpans,
        // so we'll rely on the presence of the loaded/specified spans for whether
        // or not we should fetch remote data.
        if (!$this->spans) {
            $this->reload($options);
        }

        return [
            'traceId' => $this->traceId,
            'spans' => array_map(function ($span) {
                return $span->info();
            }, $this->spans)
        ];
    }

    /**
     * Retrieves the spans for this trace.
     *
     * @return TraceSpan[]
     */
    public function spans()
    {
        return $this->spans;
    }

    /**
     * Create an instance of {@see OpenCensus\Trace\TraceSpan}
     *
     * @param array $options [optional] See {@see OpenCensus\Trace\TraceSpan::__construct()}
     *        for configuration details.
     * @return TraceSpan
     */
    public function span(array $options = [])
    {
        return new TraceSpan($options);
    }

    /**
     * Set the spans for this trace.
     *
     * @param TraceSpan[] $spans
     */
    public function setSpans(array $spans)
    {
        $this->spans = $spans;
    }

    /**
     * Serialize data.
     *
     * @return string
     * @access private
     */
    public function serialize()
    {
        return serialize([
            $this->traceId,
            $this->spans
        ]);
    }

    /**
     * Unserialize data.
     *
     * @param string
     * @access private
     */
    public function unserialize($data)
    {
        list(
            $this->traceId,
            $this->spans
        ) = unserialize($data);
    }
}
