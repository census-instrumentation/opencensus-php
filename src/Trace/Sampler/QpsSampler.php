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

namespace OpenCensus\Trace\Sampler;

use Psr\Cache\CacheItemPoolInterface;
use Cache\Adapter\Common\CacheItem;

/**
 * This implementation of the SamplerInterface uses a cache to limit sampling to
 * the a certain number of queries per second. It requires a PSR-6 cache implementation.
 *
 * Example using cache/memcached-adapter:
 * ```
 * // This example uses the  Memcached extension and requires the
 * // cache/memcached-adapter composer package
 * use OpenCensus\Trace\Sampler\QpsSampler;
 * use Cache\Adapter\Memcached\MemcachedCachePool;
 * use Cache\Adapter\Common\PhpCacheItem;
 *
 * $client = new \Memcached();
 * $client->addServer('localhost', 11211);
 * $cache = new Memcached\MemcachedCachePool($client);
 * $sampler = new QpsSampler($cache, [
 *     'cacheItemClass' => PhpCacheItem::class
 * ]);
 * ```
 *
 * You can find a list of PSR-6 cache implementations
 * <a href="https://packagist.org/providers/psr/cache-implementation">here.</a>
 */
class QpsSampler implements SamplerInterface
{
    const DEFAULT_CACHE_KEY = '__opencensus_trace__';
    const DEFAULT_QPS_RATE = 0.1;

    /**
     * @var CacheItemPoolInterface The cache store used for storing the last
     */
    private $cache;

    /**
     * @var float The QPS rate.
     */
    private $rate;

    /**
     * @var string The class name of the cache item interface to use
     */
    private $cacheItemClass;

    /**
     * @var string The cache key
     */
    private $key;

    /**
     * Create a new QpsSampler. If the provided cache is shared between servers,
     * the queries per second will be counted across servers. If the cache is shared
     * between servers and you wish to sample independently on the servers, provide
     * your own cache key that is different on each server.
     *
     * There may be race conditions between simultaneous requests where they may
     * both (all) be sampled.
     *
     * @param CacheItemPoolInterface $cache The cache store to use
     * @param array $options [optional] {
     *     configuration options.
     *
     *     @type string $cacheItemClass The class of the item to use. This class must implement
     *           CacheItemInterface.
     *     @type float $rate The number of queries per second to allow. Must be less than or equal to 1.
     *           **Defaults to** `0.1`
     *     @type string $key The cache key to use. **Defaults to** `__opencensus_trace__`
     * }
     */
    public function __construct(CacheItemPoolInterface $cache = null, $options = [])
    {
        $this->cache = $cache ?: $this->defaultCache();
        if (!$this->cache) {
            throw new \InvalidArgumentException('Cannot use QpsSampler without providing a PSR-6 $cache');
        }

        $options += [
            'cacheItemClass' => CacheItem::class,
            'rate' => self::DEFAULT_QPS_RATE,
            'key' => self::DEFAULT_CACHE_KEY
        ];

        if (array_key_exists('cacheItemClass', $options)) {
            $this->cacheItemClass = $options['cacheItemClass'];
        }

        $this->rate = $options['rate'];
        $this->key = $options['key'];

        if ($this->rate > 1 || $this->rate <= 0) {
            throw new \InvalidArgumentException('QPS sampling rate must be less that 1 query per second');
        }
    }

    /**
     * Returns whether or not the request should be sampled.
     *
     * @return bool
     */
    public function shouldSample()
    {
        // We will store the microtime timestamp in the cache because some
        // cache implementations will not let you use expiry for anything less
        // than 1 minute
        if ($item = $this->cache->getItem($this->key)) {
            if ((float) $item->get() > microtime(true)) {
                return false;
            }
        }

        $item = new $this->cacheItemClass($this->key);
        $item->set(microtime(true) + 1.0 / $this->rate);

        // TODO: what if the cache fails to save?
        $this->cache->save($item);

        return true;
    }

    /**
     * Detect a usable PSR-6 cache implementation
     *
     * @return CacheItemPoolInterface
     */
    private function defaultCache()
    {
        if (extension_loaded('apcu') && class_exists('\\Cache\\Adapter\\Apcu\\ApcuCachePool')) {
            return new \Cache\Adapter\Apcu\ApcuCachePool();
        } elseif (extension_loaded('apc') && class_exists('\\Cache\\Adapter\\Apc\\ApcCachePool')) {
            return new \Cache\Adapter\Apc\ApcCachePool();
        }
        return null;
    }

    /**
     * Return the query-per-second rate
     *
     * @return float
     */
    public function rate()
    {
        return $this->rate;
    }
}
