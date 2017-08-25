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

namespace OpenCensus\Trace\Propagator;

use OpenCensus\Trace\TraceContext;

/**
 * This propagator uses HTTP headers to propagate TraceContext over HTTP.
 * There are two possible headers `X-Cloud-Trace-Context` and `Trace-Context`.
 * This class handles both formats.
 *
 * The current format of the header is <trace-id>[/<span-id>][;o=<options>].
 * The options are a bitmask of options. Currently the only option is the
 * least significant bit which signals whether the request was traced or not
 * (1 = traced, 0 = not traced).
 */
class HttpHeaderPropagator implements PropagatorInterface
{
    const DEFAULT_HEADER = 'HTTP_X_CLOUD_TRACE_CONTEXT';

    /**
     * @var FormatterInterface
     */
    private $formatter;

    /**
     * @var string
     */
    private $header;

    public function __construct(FormatterInterface $formatter = null, $header = null)
    {
        $this->formatter = $formatter ?: new CloudTraceFormatter();
        $this->header = $header ?: self::DEFAULT_HEADER;
    }

    /**
     * Generate a TraceContext object from the all the HTTP headers
     *
     * @param array $headers
     * @return TraceContext
     */
    public function extract($headers)
    {
        if (array_key_exists($this->header, $headers)) {
            return $this->formatter->deserialize($headers[$this->header]);
        }
        return new TraceContext();
    }

    /**
     * Persiste the current TraceContext back into the results of this request
     *
     * @param TraceContext $context
     * @param array $container
     * @return bool
     */
    public function inject(TraceContext $context, $container)
    {
        if (!headers_sent()) {
            $header = str_replace('_', '-', $this->header);
            header($header . ': ' . $this->formatter->serialize($context));
            return true;
        }
        return false;
    }

    /**
     * Returns the current formatter
     *
     * @return FormatterInterface
     */
    public function formatter()
    {
        return $this->formatter;
    }

    /**
     * Return the key used to propagate the TraceContext
     *
     * @return string
     */
    public function key()
    {
        return $this->header();
    }
}
