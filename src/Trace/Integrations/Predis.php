<?php

namespace OpenCensus\Trace\Integrations;

use OpenCensus\Trace\Span;

/**
 * This class handles instrumenting Predis requests using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Predis;
 *
 * Predis::load();
 * ```
 */
class Predis implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to predis requests
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Predis integrations.', E_USER_WARNING);
        }

        opencensus_trace_method('Predis\Client', '__construct', [static::class, 'handleConstruct']);

        opencensus_trace_method('Predis\Client', 'set', [static::class, 'handleCall']);

        opencensus_trace_method('Predis\Client', 'get', [static::class, 'handleCall']);

        opencensus_trace_method('Predis\Client', 'flushDB');
    }

    /**
     * Trace Construct Options
     *
     * @param $predis
     * @param  $params
     * @return array
     */
    public static function handleConstruct($predis, $params)
    {
        return [
            'attributes' => [
                'host' => $params['host'],
                'port' => $params['port']
            ],
            'kind' => Span::KIND_CLIENT
        ];
    }

    /**
     * Trace Set / Get Operations
     *
     * @param $predis
     * @param  $key
     * @return array
     */
    public static function handleCall($predis, $key)
    {
        return [
            'attributes' => ['key' => $key],
            'kind' => Span::KIND_CLIENT
        ];
    }
}
