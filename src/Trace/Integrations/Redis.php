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
 * This class handles instrumenting Redis requests using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Redis;
 *
 * Redis::load();
 * ```
 */
class Redis implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to memcache requests
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Memcached integrations.', E_USER_WARNING);
            return;
        }
        opencensus_trace_method('Redis', '__construct', [static::class, 'handleConstruct']);

        opencensus_trace_method('Redis', 'set', [static::class, 'handleIO']);

        opencensus_trace_method('Redis', 'get', [static::class, 'handleIO']);

        opencensus_trace_method('Redis', 'flushDB');
    }

    /**
     * Trace Construct Options
     *
     * @param  $params
     * @return array
     */
    public static function handleConstruct($params)
    {
        return [
            'attributes' => [
                'host' => $params['host'],
                'port' => $params['port'],
                'db' => $params['database'],
            ],
            'kind' => Span::KIND_CLIENT
        ];
    }

    /**
     * Trace Connect Options
     *
     * @param  $params
     * @return array
     */
    public static function handleConnect($params)
    {
        return [
            'attributes' => [
                'host' => $params['host'],
                'port' => $params['port'],
                'db' => $params['database'],
            ],
            'kind' => Span::KIND_CLIENT
        ];
    }

    /**
     * Trace Set / Get Operations
     *
     * @param  $key
     * @return array
     */
    public static function handleIO($key)
    {
        return [
            'attributes' => ['key' => $key],
            'kind' => Span::KIND_CLIENT
        ];
    }
}
