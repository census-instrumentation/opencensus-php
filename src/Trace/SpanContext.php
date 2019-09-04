<?php declare(strict_types=1);

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

use OpenCensus\Utils\IdGenerator;

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
     * @var bool Whether or not tracing is enabled for this request.
     */
    private $enabled;


    /**
     * @var bool Whether or not this context was detected from a request header.
     */
    private $fromHeader;

    /**
     * Creates a new SpanContext instance
     *
     * @param string $traceId The current traceId. If not set, one will be generated.
     * @param string|null $spanId The current spanId.
     * @param bool|null $enabled Whether or not this span should be sent to the extractor.
     * @param bool $fromHeader Whether or not the context was detected from the incoming headers.
     */
    public function __construct(
        string $traceId = null,
        string $spanId = null,
        bool $enabled = null,
        bool $fromHeader = false
    ) {
        $this->traceId = $traceId ?: IdGenerator::hex(16);
        $this->spanId = $spanId;
        $this->enabled = $enabled;
        $this->fromHeader = $fromHeader;
    }

    /**
     * Fetch the current traceId.
     *
     * @return string
     */
    public function traceId(): string
    {
        return $this->traceId;
    }

    /**
     * Fetch the current spanId.
     *
     * @return string|null
     */
    public function spanId()
    {
        return $this->spanId;
    }

    /**
     * Set the current spanId.
     *
     * @param string $spanId The spanId to set.
     */
    public function setSpanId(string $spanId)
    {
        $this->spanId = $spanId;
    }

    /**
     * Whether or not the request is being traced.
     *
     * @return bool|null
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
    public function fromHeader(): bool
    {
        return $this->fromHeader;
    }
}
