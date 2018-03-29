<?php
/**
 * Copyright 2017 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCensus\Trace\Integrations;

use OpenCensus\Trace\Span;

/**
 * This class handles instrumenting curl requests using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Curl;
 *
 * Curl::load();
 * ```
 */
class Curl implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to curl requests
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load curl integrations.', E_USER_WARNING);
            return;
        }

        opencensus_trace_function('curl_exec', [static::class, 'handleCurlResource']);
        opencensus_trace_function('curl_multi_add_handle');
        opencensus_trace_function('curl_multi_remove_handle');
    }

    /**
     * Handle extracting the uri from a given curl resource handler
     *
     * @internal
     * @param resource $resource The curl handler
     * @return array
     */
    public static function handleCurlResource($resource)
    {
        return [
            'attributes' => [
                'uri' => curl_getinfo($resource, CURLINFO_EFFECTIVE_URL)
            ],
            'kind' => Span::KIND_CLIENT
        ];
    }
}
