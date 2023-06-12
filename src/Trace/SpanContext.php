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

use OpenCensus\Core\Context;

/**
 * SpanContext encapsulates your current context within your request's trace. It includes
 * 3 fields: the `traceId`, the current `spanId`, and an `enabled` flag which indicates whether
 * or not the request is being traced.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Tracer;
 *
 * $context = Tracer::spanContext();
 * echo $context; // output the header format for using the current context in a remote call
 * ```
 */
class SpanContext
{
    use IdGeneratorTrait;

    /**
     * @var string The current traceId. This is stored as a hex string.
     */
    private $traceId;

    /**
     * @var string|null The current spanId. This is stored as a hex string. This
     *      is the deepest nested span currently open.
     */
    private $spanId;

    /**
     * @var bool|null Whether or not tracing is enabled for this request.
     */
    private $enabled;

    /**
     * @var bool Whether or not the context was detected from an incoming header.
     */
    private $fromHeader;

    /**
     * Creates a new SpanContext instance
     *
     * @param string $traceId The current traceId. If not set, one will be
     *        generated for you.
     * @param string|null $spanId The current spanId. **Defaults to** `null`.
     * @param bool|null $enabled Whether or not tracing is enabled on this
     *        request. **Defaults to** `null`.
     * @param bool $fromHeader Whether or not the context was detected from an
     *        incoming header. **Defaults to** `false`.
     */
    public function __construct($traceId = null, $spanId = null, $enabled = null, $fromHeader = false)
    {
        $this->traceId = $traceId ?: $this->generateTraceId();
        $this->spanId = $spanId;
        $this->enabled = $enabled;
        $this->fromHeader = $fromHeader;
    }

    /**
     * Fetch the current traceId.
     *
     * @return string
     */
    public function traceId()
    {
        return $this->traceId;
    }

    /**
     * Fetch the current spanId.
     *
     * @return string
     */
    public function spanId()
    {
        return $this->spanId;
    }

    /**
     * Set the current spanId.
     *
     * @param string|null $spanId The spanId to set.
     */
    public function setSpanId($spanId)
    {
        $this->spanId = $spanId;
    }

    /**
     * Whether or not the request is being traced.
     *
     * @return bool
     */
    public function enabled()
    {
        return $this->enabled;
    }

    /**
     * Set whether or not the request is being traced.
     *
     * @param bool|null $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Whether or not this context was detected from a request header.
     *
     * @return bool
     */
    public function fromHeader()
    {
        return $this->fromHeader;
    }
}
