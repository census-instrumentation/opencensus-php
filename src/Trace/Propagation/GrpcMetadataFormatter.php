<?php
/**
 * Copyright 2017 Google Inc. All Rights Reserved.
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

namespace OpenCensus\Trace\Propagation;

use OpenCensus\Trace\TraceContext;

class GrpcMetadataFormatter implements PropagationFormatterInterface
{
    const METADATA_KEY = 'grpc-trace-bin';

    /**
     * Generate a TraceContext object from the all the HTTP headers
     *
     * @param array $metadata
     * @return TraceContext
     */
    public static function parse($metadata)
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
    public static function deserialize($bin)
    {
        // TODO: implement when spec if finalized
        return new TraceContext();
    }

    /**
     * Convert a TraceContext to header string
     *
     * @param TraceContext $context
     * @return string
     */
    public static function serialize(TraceContext $context)
    {
        // TODO: implement when spec if finalized
        return '';
    }
}
