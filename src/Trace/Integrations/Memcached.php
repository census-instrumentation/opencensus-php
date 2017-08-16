<?php
/**
 * Copyright 2017 Google Inc. All Rights Reserved.
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
            return;
        }

        $handleLabels = function ($memcached, $keyOrKeys) {
            $key = is_array($keyOrKeys) ? implode(",", $keyOrKeys) : $keyOrKeys;
            return [
                'labels' => ['key' => $key]
            ];
        };

        $handleLabelsByKey = function ($memcached, $serverKey, $keyOrKeys) {
            $key = is_array($keyOrKeys) ? implode(",", $keyOrKeys) : $keyOrKeys;
            return [
                'labels' => [
                    'serverKey' => $serverKey,
                    'key' => $key
                ]
            ];
        }

        // bool Memcached::add ( string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'add', $handleLabels);

        // bool Memcached::addByKey ( string $server_key , string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'add', $handleLabelsByKey);

        // bool Memcached::append ( string $key , string $value )
        opencensus_trace_method('Memcached', 'append', $handleLabels);

        // bool Memcached::appendByKey ( string $server_key , string $key , string $value )
        opencensus_trace_method('Memcached', 'appendByKey', $handleLabelsByKey);

        // bool Memcached::cas ( float $cas_token , string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'cas', function ($memcached, $casToken, $key) {
            return [
                'labels' => [
                    'casToken' => $casToken,
                    'key' => $key
                ]
            ];
        });

        // bool Memcached::casByKey ( float $cas_token , string $server_key , string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'casByKey', function ($memcached, $casToken, $serverKey, $key) {
            return [
                'labels' => [
                    'casToken' => $casToken,
                    'serverKey' => $serverKey,
                    'key' => $key
                ]
            ];
        });

        // int Memcached::decrement ( string $key [, int $offset = 1 [, int $initial_value = 0 [, int $expiry = 0 ]]] )
        opencensus_trace_method('Memcached', 'decrement', $handleLabels);

        // int Memcached::decrementByKey ( string $server_key , string $key [, int $offset = 1 [, int $initial_value = 0 [, int $expiry = 0 ]]] )
        opencensus_trace_method('Memcached', 'decrementByKey', $handleLabelsByKey);

        // bool Memcached::delete ( string $key [, int $time = 0 ] )
        opencensus_trace_method('Memcached', 'delete', $handleLabels);

        // bool Memcached::deleteByKey ( string $server_key , string $key [, int $time = 0 ] )
        opencensus_trace_method('Memcached', 'deleteByKey', $handleLabelsByKey);

        // bool Memcached::flush ([ int $delay = 0 ] )
        opencensus_trace_method('Memcached', 'flush');

        // mixed Memcached::get ( string $key [, callable $cache_cb [, int &$flags ]] )
        opencensus_trace_method('Memcached', 'get', $handleLabels);

        // mixed Memcached::getByKey ( string $server_key , string $key [, callable $cache_cb [, int $flags ]] )
        opencensus_trace_method('Memcached', 'getByKey', $handleLabelsByKey);

        // mixed Memcached::getMulti ( array $keys [, int $flags ] )
        opencensus_trace_method('Memcached', 'getMulti', $handleLabels);

        // array Memcached::getMultiByKey ( string $server_key , array $keys [, int $flags ] )
        opencensus_trace_method('Memcached', 'getMultiByKey', $handleLabelsByKey);

        // int Memcached::increment ( string $key [, int $offset = 1 [, int $initial_value = 0 [, int $expiry = 0 ]]] )
        opencensus_trace_method('Memcached', 'increment', $handleLabels);

        // int Memcached::incrementByKey ( string $server_key , string $key [, int $offset = 1 [, int $initial_value = 0 [, int $expiry = 0 ]]] )
        opencensus_trace_method('Memcached', 'incrementByKey', $handleLabelsByKey);

        // bool Memcached::prepend ( string $key , string $value )
        opencensus_trace_method('Memcached', 'prepend', $handleLabels);

        // bool Memcached::prependByKey ( string $server_key , string $key , string $value )
        opencensus_trace_method('Memcached', 'prependByKey', $handleLabelsByKey);

        // bool Memcached::replace ( string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'replace', $handleLabels);

        // bool Memcached::replaceByKey ( string $server_key , string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'replaceByKey', $handleLabelsByKey);

        // bool Memcached::set ( string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'set', $handleLabels);

        // bool Memcached::setByKey ( string $server_key , string $key , mixed $value [, int $expiration ] )
        opencensus_trace_method('Memcached', 'setByKey', $handleLabelsByKey);

        // bool Memcached::setMulti ( array $items [, int $expiration ] )
        opencensus_trace_method('Memcached', 'setMulti', function ($memcached, $items) {
            return [
                'labels' => ['key' => implode(',', array_keys($items))]
            ]
        });

        // bool Memcached::setMultiByKey ( string $server_key , array $items [, int $expiration ] )
        opencensus_trace_method('Memcached', 'setMultiByKey', function ($memcached, $serverKey, $items) {
            return [
                'labels' => [
                    'serverKey' => $serverKey,
                    'key' => implode(',', array_keys($items))
                ]
            ]
        });



    }
}
