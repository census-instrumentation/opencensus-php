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

namespace OpenCensus\Trace\Propagator;

use OpenCensus\Trace\TraceContext;

/**
 * This propagator uses HTTP headers to propagate TraceContext over HTTP.
 * There are two possible headers `X-Cloud-Trace-Context` and `Trace-Context`.
 * This class handles both formats.
 *
 * The current format of the header is <trace-id>[/<span-id>][;o=<options>].
 * The options are a bitmask of options. Currently the only option is the
 * least significant bit which signals whether the request was traced or not
 * (1 = traced, 0 = not traced).
 */
class HttpHeaderPropagator implements PropagatorInterface
{
    const HTTP_HEADERS = [
        'HTTP_X_CLOUD_TRACE_CONTEXT',
        'HTTP_TRACE_CONTEXT'
    ];
    const CONTEXT_HEADER_FORMAT = '/([0-9a-f]{32})(?:\/(\d+))?(?:;o=(\d+))?/';

    /**
     * Generate a TraceContext object from the all the HTTP headers
     *
     * @param array $headers
     * @return TraceContext
     */
    public function parse($headers)
    {
        foreach (self::HTTP_HEADERS as $header) {
            if (array_key_exists($header, $headers)) {
                return self::deserialize($headers[$header]);
            }
        }
        return new TraceContext();
    }

    /**
     * Generate a TraceContext object from the Trace Context header
     *
     * @param string $header
     * @return TraceContext
     */
    public function deserialize($header)
    {
        if (preg_match(self::CONTEXT_HEADER_FORMAT, $header, $matches)) {
            return new TraceContext(
                $matches[1],
                array_key_exists(2, $matches) ? $matches[2] : null,
                array_key_exists(3, $matches) ? $matches[3] == '1' : null,
                true
            );
        }
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
        $ret = '' . $context->traceId();
        if ($context->spanId()) {
            $ret .= '/' . $context->spanId();
        }
        $ret .= ';o=' . ($context->enabled() ? '1' : '0');
        return $ret;
    }
}
