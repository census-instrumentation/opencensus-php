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
 * This format using a human readable string encoding to propagate TraceContext.
 * See https://github.com/TraceContext/tracecontext-spec/blob/master/trace_context/HTTP_HEADER_FORMAT.md
 * for the definition.
 */
class TraceContextFormatter implements FormatterInterface
{
    const CONTEXT_HEADER_FORMAT = '/([0-9a-f]{2})-(.*)/';
    const VERSION_0_FORMAT = '/([0-9a-f]{32})-([0-9a-f]{16})(?:-([0-9a-f]{2}))?/';

    /**
     * Generate a TraceContext object from the Trace Context header
     *
     * @param string $header
     * @return TraceContext
     */
    public function deserialize($header)
    {
        if (preg_match(self::CONTEXT_HEADER_FORMAT, $header, $matches)) {
            if ($matches[1] == "00") {
                return $this->deserializeVersion0($matches[2]);
            } else {
                trigger_error("Unrecognized TraceContext header version: " . $matches[1], E_USER_WARNING);
            }
        }
        return new TraceContext();
    }

    /**
     * Convert a TraceContext to header string. Uses version 0.
     *
     * @param TraceContext $context
     * @return string
     */
    public function serialize(TraceContext $context)
    {
        $ret = '00-' . $context->traceId();
        if ($context->spanId()) {
            $ret .= '-' . str_pad(dechex($context->spanId()), 16, "0", STR_PAD_LEFT);
        }
        if ($context->enabled() !== null) {
            $ret .= '-' . ($context->enabled() ? '01' : '00');
        }
        return $ret;
    }

    private function deserializeVersion0($header)
    {
        if (preg_match(self::VERSION_0_FORMAT, $header, $matches)) {
            return new TraceContext(
                $matches[1],
                hexdec($matches[2]),
                array_key_exists(3, $matches) ? $matches[3] == '01' : null,
                true
            );
        }
        trigger_error("Unrecognized TraceContext version 0 format: " . $header, E_USER_WARNING);
    }
}
