<?php
/**
 * Copyright 2019 OpenCensus Authors
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
 * This class handles instrumenting ElasticSearch requests with elastica using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Elastica;
 *
 * Elastica::load();
 * ```
 */
class Elastica implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to elasticsearch requests
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Elastica integrations.', E_USER_WARNING);
        }

        opencensus_trace_method('Elastica\Client', '__construct', [static::class, 'handleConstruct']);

        opencensus_trace_method('Elastica\Client', 'request', [static::class, 'handleRequest']);
    }

    /**
     * @param $elastica
     * @param $config
     *
     * @return array
     */
    public static function handleConstruct($elastica, $config)
    {
        $attributes = [];
        foreach ($config as $key => $param) {
            $attributes[$key] = $param;
        }

        return [
            'attributes' => $attributes,
            'kind' => Span::KIND_CLIENT,
        ];
    }

    /**
     * @param $elastica
     * @param $path
     * @param $method
     * @param $data
     * @param $query
     *
     * @return array
     */
    public static function handleRequest($elastica, $path, $method, $data, $query)
    {
        return [
            'attributes' => [
                'path' => $path,
                'method' => $method,
                'data' => json_encode($data),
                'query' => json_encode($query),
            ],
            'kind' => Span::KIND_CLIENT,
        ];
    }
}
