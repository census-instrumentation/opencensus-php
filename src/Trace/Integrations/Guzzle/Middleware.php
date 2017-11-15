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

namespace OpenCensus\Trace\Integrations\Guzzle;

use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Propagator\HttpHeaderPropagator;
use OpenCensus\Trace\Propagator\PropagatorInterface;
use Psr\Http\Message\RequestInterface;

/**
 * This class handles integration with GuzzleHttp 6. Adding this middleware to
 * your Guzzle client, will enable distrubted tracing by passing along the trace context
 * header and will also create trace spans for all outgoing requests.
 *
 * Example:
 * ```
 * use GuzzleHttp\Client;
 * use GuzzleHttp\HandlerStack;
 * use OpenCensus\Trace\Integrations\Guzzle\Middleware;
 *
 * $stack = new HandlerStack();
 * $stack->setHandler(\GuzzleHttp\choose_handler());
 * $stack->push(new Middleware());
 * $client = new Client(['handler' => $stack]);
 * ```
 */
class Middleware
{
    const DEFAULT_HEADER_NAME = 'X-Cloud-Trace-Context';

    /**
     * @var PropagatorInterface
     */
    private $propagator;

    /**
     * Create a new Guzzle middleware that creates trace spans and propagates the current
     * trace context to the downstream request.
     *
     * @param PropagatorInterface $propagator Interface responsible for serializing trace context
     */
    public function __construct(PropagatorInterface $propagator = null)
    {
        $this->propagator = $propagator ?: new HttpHeaderPropagator();
    }

    /**
     * Magic method which makes this object callable. Guzzle middleware are expected to be
     * callables.
     *
     * @param  callable $handler The next handler in the HandlerStack
     * @return callable
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, $options) use ($handler) {
            if ($context = Tracer::context()) {
                $request = $request->withHeader(
                    $this->propagator->key(),
                    $this->propagator->formatter()->serialize($context)
                );
            }
            return Tracer::inSpan([
                'name' => 'GuzzleHttp::request',
                'labels' => [
                    'method' => $request->getMethod(),
                    'uri' => (string)$request->getUri()
                ]
            ], $handler, [$request, $options]);
        };
    }
}
