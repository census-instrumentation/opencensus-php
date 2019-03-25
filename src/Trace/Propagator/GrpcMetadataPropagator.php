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
 * This propagator contains the logic for propagating SpanContext over
 * grpc using its request metadata. It will default to using the BinaryFormatter
 * to serialize/deserialize SpanContext.
 */
class GrpcMetadataPropagator implements PropagatorInterface
{
    const DEFAULT_METADATA_KEY = 'grpc-trace-bin';

    /**
     * @var FormatterInterface
     */
    private $formatter;

    /**
     * @var string
     */
    private $key;

    /**
     * Create a new GrpcMetadataPropagator
     *
     * @param FormatterInterface $formatter [optional] The formatter used to serialize/deserialize SpanContext
     *        **Defaults to** a new BinaryFormatter.
     * @param string $key [optional] The grpc metadata key to store/retrieve the encoded SpanContext.
     *        **Defaults to** `grpc-trace-bin`
     */
    public function __construct(FormatterInterface $formatter = null, $key = null)
    {
        $this->formatter = $formatter ?: new BinaryFormatter();
        $this->key = $key ?: self::DEFAULT_METADATA_KEY;
    }

    public function extract($metadata): SpanContext
    {
        if (array_key_exists($this->key, $metadata)) {
            return $this->formatter->deserialize($metadata[$this->key]);
        }
        return new SpanContext();
    }

    public function inject(SpanContext $context, &$metadata): void
    {
        $metadata[$this->key] = $this->formatter->serialize($context);
    }

    /**
     * Fetch the formatter for propagating the SpanContext
     *
     * @return FormatterInterface
     */
    public function formatter()
    {
        return $this->formatter;
    }

    /**
     * Return the key used to propagate the SpanContext
     *
     * @return string
     */
    public function key()
    {
        return $this->key;
    }
}
