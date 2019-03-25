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
     * @param FormatterInterface $formatter The formatter used to serialize and
     *        deserialize SpanContext. **Defaults to** a new
     *        CloudTraceFormatter.
     * @param string|null $header
     */
    public function __construct(FormatterInterface $formatter = null, $header = null)
    {
        $this->formatter = $formatter ?: new CloudTraceFormatter();
        $this->header = $header ?: self::DEFAULT_HEADER;
    }

    public function extract($headers): SpanContext
    {
        if (array_key_exists($this->header, $headers)) {
            return $this->formatter->deserialize($headers[$this->header]);
        }
        return new SpanContext();
    }

    public function inject(SpanContext $context, &$container): void
    {
        $header = $this->key();
        $value = $this->formatter->serialize($context);
        if (!headers_sent()) {
            header("$header: $value");
        }
        $container[$header] = $value;
    }

    public function formatter()
    {
        return $this->formatter;
    }

    public function key()
    {
        return str_replace('_', '-', preg_replace('/^HTTP_/', '', $this->header));
    }
}
