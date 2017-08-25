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
 * This propagator will contains the method for serializaing and deserializing
 * TraceContext over a binary format.
 *
 * See https://github.com/census-instrumentation/opencensus-specs/blob/master/encodings/BinaryEncoding.md
 * for the encoding specification.
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

    public function __construct(FormatterInterface $formatter = null, $key = null)
    {
        $this->formatter = $formatter ?: new BinaryFormatter();
        $this->key = $key ?: self::DEFAULT_METADATA_KEY;
    }

    /**
     * Generate a TraceContext object from the all the HTTP headers
     *
     * @param array $metadata
     * @return TraceContext
     */
    public function extract($metadata)
    {
        if (array_key_exists($this->key, $metadata)) {
            return $this->formatter->deserialize($metadata[$this->key]);
        }
        return new TraceContext();
    }

    /**
     * Fetch the formatter for propagating the TraceContext
     *
     * @return FormatterInterface
     */
    public function inject(TraceContext $context, $metadata)
    {
        $metadata[$this->key] = $this->formatter->serialize($context);
        return true;
    }

    /**
     * Fetch the formatter for propagating the TraceContext
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
        return $this->key;
    }

}
