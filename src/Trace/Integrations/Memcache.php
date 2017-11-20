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

/**
 * This class handles instrumenting memcache requests using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Memcache;
 *
 * Memcache::load();
 */
class Memcache implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to memcache requests
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Memcache integrations.', E_USER_WARNING);
            return;
        }

        // bool Memcache::add ( string $key , mixed $var [, int $flag [, int $expire ]] )
        opencensus_trace_method('Memcache', 'add', [self::class, 'handleAttributes']);

        // string Memcache::get ( string $key [, int &$flags ] )
        // array Memcache::get ( array $keys [, array &$flags ] )
        opencensus_trace_method('Memcache', 'get', [self::class, 'handleAttributes']);

        // bool Memcache::set ( string $key , mixed $var [, int $flag [, int $expire ]] )
        opencensus_trace_method('Memcache', 'set', [self::class, 'handleAttributes']);

        // bool Memcache::delete ( string $key [, int $timeout = 0 ] )
        opencensus_trace_method('Memcache', 'delete', [self::class, 'handleAttributes']);

        opencensus_trace_method('Memcache', 'flush');

        // bool Memcache::replace ( string $key , mixed $var [, int $flag [, int $expire ]] )
        opencensus_trace_method('Memcache', 'replace', [self::class, 'handleAttributes']);

        // int Memcache::increment ( string $key [, int $value = 1 ] )
        opencensus_trace_method('Memcache', 'increment', [self::class, 'handleAttributes']);

        // int Memcache::decrement ( string $key [, int $value = 1 ] )
        opencensus_trace_method('Memcache', 'decrement', [self::class, 'handleAttributes']);

        // bool Memcache::connect ( string $host [, int $port [, int $timeout ]] )
        opencensus_trace_method('Memcache', 'connect', [self::class, 'handleConnect']);
    }

    /**
     * Handle converting the key or keys provided to a Memcache function into a comma-separated attribute
     *
     * @internal
     * @param \Memcache $memcache
     * @param array|string $keyOrKeys The key or keys to operate on
     * @return array
     */
    public static function handleAttributes($memcache, $keyOrKeys)
    {
        $key = is_array($keyOrKeys) ? implode(",", $keyOrKeys) : $keyOrKeys;
        return [
            'attributes' => ['key' => $key]
        ];
    }

    /**
     * Extract the host as a attribute
     *
     * @internal
     * @param \Memcache $memcache
     * @param string $host
     * @return array
     */
    public static function handleConnect($memcache, $host)
    {
        return [
            'attributes' => [
                'host' => $host
            ]
        ];
    }
}
