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
 * This format using a human readable string encoding to propagate SpanContext.
 * The current format of the header is `<trace-id>[/<span-id>][;o=<options>]`.
 * The options are a bitmask of options. Currently the only option is the
 * least significant bit which signals whether the request was traced or not
 * (1 = traced, 0 = not traced).
 */
class CloudTraceFormatter implements FormatterInterface
{
    const CONTEXT_HEADER_FORMAT = '/([0-9a-fA-F]{32})(?:\/(\d+))?(?:;o=(\d+))?/';

    /**
     * Generate a SpanContext object from the Trace Context header
     *
     * @param string $header
     * @return SpanContext
     */
    public function deserialize($header)
    {
        if (preg_match(self::CONTEXT_HEADER_FORMAT, $header, $matches)) {
            return new SpanContext(
                strtolower($matches[1]),
                array_key_exists(2, $matches) && !empty($matches[2])
                    ? dechex((int)($matches[2]))
                    : null,
                array_key_exists(3, $matches) ? $matches[3] == '1' : null,
                true
            );
        }
        return new SpanContext();
    }

    /**
     * Convert a SpanContext to header string
     *
     * @param SpanContext $context
     * @return string
     */
    public function serialize(SpanContext $context)
    {
        $ret = '' . $context->traceId();
        if ($context->spanId()) {
            $ret .= '/' . hexdec($context->spanId());
        }
        if ($context->enabled() !== null) {
            $ret .= ';o=' . ($context->enabled() ? '1' : '0');
        }
        return $ret;
    }
}
