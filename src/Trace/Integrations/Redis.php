<?php

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

        opencensus_trace_method('Redis', 'set', [static::class, 'handleSet']);

        opencensus_trace_method('Redis', 'get', [static::class, 'handleGet']);

    }

    public static function handleConstruct($redis, $params)
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

    public static function handleConnect($redis, $params)
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

    public static function handleSet($redis, $key)
    {
        return [
            'attributes' => ['setKey' => $key],
            'kind' => Span::KIND_CLIENT
        ];
    }

    public static function handleGet($redis, $key)
    {
        return [
            'attributes' => ['retrievedKey' => $key],
            'kind' => Span::KIND_CLIENT
        ];
    }

}