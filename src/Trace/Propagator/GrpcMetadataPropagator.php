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
 * This propagator will contain the canonical method for propagating
 * TraceContext over grpc. The specification is not finalized yet. The
 * current design uses a metadata key `grpc-trace-bin` with a binary
 * encoding. Do not use this propagator until it's implemented.
 *
 * @experimental
 */
class GrpcMetadataPropagator implements PropagatorInterface
{
    const METADATA_KEY = 'grpc-trace-bin';

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
        // TODO: implement when spec is finalized
        return new TraceContext();
    }

    /**
     * Convert a TraceContext to header string
     *
     * @param TraceContext $context
     * @return string
     */
    public function serialize(TraceContext $context)
    {
        // TODO: implement when spec is finalized
        return '';
    }
}
