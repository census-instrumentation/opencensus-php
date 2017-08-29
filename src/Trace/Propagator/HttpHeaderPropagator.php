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
 * The default headers is `X-Cloud-Trace-Context`.
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

    /**
     * Create a new HttpHeaderPropagator
     *
     * @param FormatterInterface $formatter The formatter used to serialize/deserialize TraceContext
     *        **Defaults to** a new CloudTraceFormatter.
     * @param string $key [optional] The header key to store/retrieve the encoded TraceContext.
     *        **Defaults to** `HTTP_X_CLOUD_TRACE_CONTEXT`
     */
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
     * @return array
     */
    public function inject(TraceContext $context, $container)
    {
        $header = str_replace('_', '-', preg_replace('/^HTTP_/', '', $this->header));
        $container[$header] = $this->formatter->serialize($context);
        return $container;
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
