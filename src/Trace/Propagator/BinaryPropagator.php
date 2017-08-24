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
class BinaryPropagator implements PropagatorInterface
{
    const METADATA_KEY = 'grpc-trace-bin';
    const OPTION_ENABLED = 1;

    /**
     * Generate a TraceContext object from the all the HTTP headers
     *
     * @param array $metadata
     * @return TraceContext
     */
    public function parse($metadata)
    {
        if (array_key_exists(self::METADATA_KEY, $metadata)) {
            return self::deserialize($metadata[self::METADATA_KEY]);
        }
        return new TraceContext();
    }

    /**
     * Generate a TraceContext object from the Trace Context header
     *
     * @param string $header
     * @return TraceContext
     */
    public function deserialize($bin)
    {
        $data = unpack('Cversion/Cfield0/H32traceId/Cfield1/H16spanId/Cfield2/Coptions', $bin);
        $enabled = !!($data['options'] & self::OPTION_ENABLED);
        return new TraceContext($data['traceId'], hexdec($data['spanId']), $enabled, true);
    }

    /**
     * Convert a TraceContext to header string
     *
     * @param TraceContext $context
     * @return string
     */
    public function serialize(TraceContext $context)
    {
        $spanHex = str_pad(dechex($context->spanId()), 16, "0", STR_PAD_LEFT);
        $traceOptions = $context->enabled() ? 1 : 0;
        return pack("CCH*CH*CC", 0, 0, $context->traceId(), 1, $spanHex, 2, $traceOptions);
    }
}
