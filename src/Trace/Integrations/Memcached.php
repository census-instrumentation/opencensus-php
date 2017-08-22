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
 * use OpenCensus\Trace\Integrations\Memcached
 *
 * Memcached::load();
 */
class Memcached implements IntegrationInterface
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

        // bool Memcached::add ( string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'add', [self::class, 'handleLabels']);

        // bool Memcached::addByKey ( string $server_key , string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'add', [self::class, 'handleLabelsByKey']);

        // bool Memcached::append ( string $key , string $value )
        opencensus_trace_method('Memcached', 'append', [self::class, 'handleLabels']);

        // bool Memcached::appendByKey ( string $server_key , string $key , string $value )
        opencensus_trace_method('Memcached', 'appendByKey', [self::class, 'handleLabelsByKey']);

        // bool Memcached::cas ( float $cas_token , string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'cas', [self::class, 'handleCas']);

        // bool Memcached::casByKey ( float $cas_token , string $server_key , string $key , mixed $value
        //                            [, int $expiration ] )
        opencensus_trace_method('Memcached', 'casByKey', [self::class, 'handleCasByKey']);

        // int Memcached::decrement ( string $key [, int $offset = 1 [, int $initial_value = 0 [, int $expiry = 0 ]]] )
        opencensus_trace_method('Memcached', 'decrement', [self::class, 'handleLabels']);

        // int Memcached::decrementByKey ( string $server_key , string $key [, int $offset = 1 [, int $initial_value = 0
        //                                 [, int $expiry = 0 ]]] )
        opencensus_trace_method('Memcached', 'decrementByKey', [self::class, 'handleLabelsByKey']);

        // bool Memcached::delete ( string $key [, int $time = 0 ] )
        opencensus_trace_method('Memcached', 'delete', [self::class, 'handleLabels']);

        // bool Memcached::deleteByKey ( string $server_key , string $key [, int $time = 0 ] )
        opencensus_trace_method('Memcached', 'deleteByKey', [self::class, 'handleLabelsByKey']);

        // bool Memcached::flush ([ int $delay = 0 ] )
        opencensus_trace_method('Memcached', 'flush');

        // mixed Memcached::get ( string $key [, callable $cache_cb [, int &$flags ]] )
        opencensus_trace_method('Memcached', 'get', [self::class, 'handleLabels']);

        // mixed Memcached::getByKey ( string $server_key , string $key [, callable $cache_cb [, int $flags ]] )
        opencensus_trace_method('Memcached', 'getByKey', [self::class, 'handleLabelsByKey']);

        // mixed Memcached::getMulti ( array $keys [, int $flags ] )
        opencensus_trace_method('Memcached', 'getMulti', [self::class, 'handleLabels']);

        // array Memcached::getMultiByKey ( string $server_key , array $keys [, int $flags ] )
        opencensus_trace_method('Memcached', 'getMultiByKey', [self::class, 'handleLabelsByKey']);

        // int Memcached::increment ( string $key [, int $offset = 1 [, int $initial_value = 0 [, int $expiry = 0 ]]] )
        opencensus_trace_method('Memcached', 'increment', [self::class, 'handleLabels']);

        // int Memcached::incrementByKey ( string $server_key , string $key [, int $offset = 1 [, int $initial_value = 0
        //                                 [, int $expiry = 0 ]]] )
        opencensus_trace_method('Memcached', 'incrementByKey', [self::class, 'handleLabelsByKey']);

        // bool Memcached::prepend ( string $key , string $value )
        opencensus_trace_method('Memcached', 'prepend', [self::class, 'handleLabels']);

        // bool Memcached::prependByKey ( string $server_key , string $key , string $value )
        opencensus_trace_method('Memcached', 'prependByKey', [self::class, 'handleLabelsByKey']);

        // bool Memcached::replace ( string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'replace', [self::class, 'handleLabels']);

        // bool Memcached::replaceByKey ( string $server_key , string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'replaceByKey', [self::class, 'handleLabelsByKey']);

        // bool Memcached::set ( string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'set', [self::class, 'handleLabels']);

        // bool Memcached::setByKey ( string $server_key , string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'setByKey', [self::class, 'handleLabelsByKey']);

        // bool Memcached::setMulti ( array $items [, int $expiration ] )
        opencensus_trace_method('Memcached', 'setMulti', [self::class, 'handleSetMulti']);

        // bool Memcached::setMultiByKey ( string $server_key , array $items [, int $expiration ] )
        opencensus_trace_method('Memcached', 'setMultiByKey', [self::class, 'handleSetMultiByKey']);
    }

    /**
     * Handle converting the key or keys provided to a Memcache function into a comma-separated label
     *
     * @param \Memcached $memcached
     * @param array|string $keyOrKeys The key or keys to operate on
     * @return array
     */
    public static function handleLabels($memcached, $keyOrKeys)
    {
        $key = is_array($keyOrKeys) ? implode(",", $keyOrKeys) : $keyOrKeys;
        return [
            'labels' => ['key' => $key]
        ];
    }

    /**
     * Handle converting the key or keys provided to a Memcache function into a comma-separated label
     *
     * @param \Memcached $memcached
     * @param string $serverKey The key identifying the server to store the value on or retrieve it from.
     * @param array|string $keyOrKeys The key or keys to operate on
     * @return array
     */
    public static function handleLabelsByKey($memcached, $serverKey, $keyOrKeys)
    {
        $key = is_array($keyOrKeys) ? implode(",", $keyOrKeys) : $keyOrKeys;
        return [
            'labels' => [
                'serverKey' => $serverKey,
                'key' => $key
            ]
        ];
    }

    /**
     * Handle converting the key and check and set token to labels
     *
     * @param \Memcached $memcached
     * @param string $casToken The check and set token. Unique value associated with the existing item. Generated by
     *        memcache.
     * @param string $key The key or keys to operate on
     * @return array
     */
    public static function handleCas($memcached, $casToken, $key)
    {
        return [
            'labels' => [
                'casToken' => $casToken,
                'key' => $key
            ]
        ];
    }

    /**
     * Handle converting the key and check and set token to labels
     *
     * @param \Memcached $memcached
     * @param string $casToken The check and set token. Unique value associated with the existing item. Generated by
     *        memcache.
     * @param string $serverKey The key identifying the server to store the value on or retrieve it from.
     * @param string $key The key or keys to operate on
     * @return array
     */
    public static function handleCasByKey($memcached, $casToken, $serverKey, $key)
    {
        return [
            'labels' => [
                'casToken' => $casToken,
                'serverKey' => $serverKey,
                'key' => $key
            ]
        ];
    }

    /**
     * Extract key label from a setMulti command
     *
     * @param \Memcached $memcached
     * @param array $items The items being set in memcached.
     * @return array
     */
    public static function handleSetMulti($memcached, $items)
    {
        return [
            'labels' => ['key' => implode(',', array_keys($items))]
        ];
    }

    /**
     * Extract key label from a setMulti command
     *
     * @param \Memcached $memcached
     * @param string $serverKey The key identifying the server to store the value on or retrieve it from.
     * @param array $items The items being set in memcached.
     * @return array
     */
    public static function handleSetMultiByKey($memcached, $serverKey, $items)
    {
        return [
            'labels' => [
                'serverKey' => $serverKey,
                'key' => implode(',', array_keys($items))
            ]
        ];
    }
}
