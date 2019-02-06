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

use OpenCensus\Trace\SpanContext;

/**
 * This propagator uses HTTP headers to propagate SpanContext over HTTP.
 * The default headers is `X-Cloud-Trace-Context`.
 */
class HttpHeaderPropagator implements PropagatorInterface
{
    const DEFAULT_HEADER = 'X-Cloud-Trace-Context';

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
     * @param FormatterInterface $formatter The formatter used to serialize and
     *        deserialize SpanContext. **Defaults to** a new
     *        CloudTraceFormatter.
     * @param string $key [optional] The header key to store/retrieve the
     *        encoded SpanContext. **Defaults to** `X-Cloud-Trace-Context`
     */
    public function __construct(FormatterInterface $formatter = null, $header = null)
    {
        $this->formatter = $formatter ?: new CloudTraceFormatter();
        $this->header = $header ?: self::DEFAULT_HEADER;
    }

    /**
     * Generate a SpanContext object from the all the HTTP headers
     *
     * @param array $headers
     * @return SpanContext
     */
    public function extract($headers)
    {
        if (array_key_exists($this->header, $headers)) {
            return $this->formatter->deserialize($headers[$this->header]);
        }
        return new SpanContext();
    }

    /**
     * Persists the current SpanContext back into the results of this request
     *
     * @param SpanContext $context
     * @param array $container
     * @return array
     */
    public function inject(SpanContext $context, $container)
    {
        $header = $this->header;
        $value = $this->formatter->serialize($context);

        $container[$header] = $value;

        return $container;
    }
}
