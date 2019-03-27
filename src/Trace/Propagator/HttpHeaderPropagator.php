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
    private const DEFAULT_HEADER = 'X-Cloud-Trace-Context';

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
     *        deserialize SpanContext. **Defaults to** a new CloudTraceFormatter.
     * @param string $header
     */
    public function __construct(FormatterInterface $formatter = null, string $header = self::DEFAULT_HEADER)
    {
        $this->formatter = $formatter ?: new CloudTraceFormatter();
        $this->header = $header;
    }

    public function extract(HeaderGetter $headers): SpanContext
    {
        $data = $headers->get($this->header);

        return $data ? $this->formatter->deserialize($data) : new SpanContext();
    }

    public function inject(SpanContext $context, HeaderSetter $setter): void
    {
        $value = $this->formatter->serialize($context);

        if (!headers_sent()) {
            header("$this->header: $value");
        }
        $setter->set($this->header, $value);
    }
}
