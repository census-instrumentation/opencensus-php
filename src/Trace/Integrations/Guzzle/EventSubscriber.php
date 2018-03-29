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

use OpenCensus\Core\Scope;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Propagator\HttpHeaderPropagator;
use OpenCensus\Trace\Propagator\PropagatorInterface;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\EndEvent;
use GuzzleHttp\Event\SubscriberInterface;

/**
 * This class handles integration with GuzzleHttp 5. Attaching this EventSubscriber to
 * your Guzzle client, will enable distrubted tracing by passing along the trace context
 * header and will also create trace spans for all outgoing requests.
 *
 * Example:
 * ```
 * use GuzzleHttp\Client;
 * use OpenCensus\Trace\Integrations\Guzzle\EventSubscriber;
 *
 * $client = new Client();
 * $subscriber = new EventSubscriber();
 * $client->getEmitter()->attach($subscriber);
 * ```
 */
class EventSubscriber implements SubscriberInterface
{
    /**
     * @var PropagatorInterface
     */
    private $propagator;

    /**
     * @var Scope
     */
    private $scope;

    /**
     * Create a new Guzzle event listener that creates trace spans and propagates the current
     * trace context to the downstream request.
     *
     * @param PropagatorInterface $propagator Interface responsible for serializing trace context
     */
    public function __construct(PropagatorInterface $propagator = null)
    {
        $this->propagator = $propagator ?: new HttpHeaderPropagator();
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array
     */
    public function getEvents()
    {
        return [
            'before'    => ['onBefore'],
            'end'       => ['onEnd']
        ];
    }

    /**
     * Handler for the BeforeEvent request lifecycle event. Adds the current trace context
     * as the trace context header. Also starts a span representing this outgoing http request.
     *
     * @param BeforeEvent $event Event object emitted before a request is sent
     */
    public function onBefore(BeforeEvent $event)
    {
        $request = $event->getRequest();
        $context = Tracer::spanContext();
        if ($context->enabled()) {
            $request->setHeader($this->propagator->key(), $this->propagator->formatter()->serialize($context));
        }
        $span = Tracer::startSpan([
            'name' => 'GuzzleHttp::request',
            'attributes' => [
                'method' => $request->getMethod(),
                'uri' => $request->getUrl()
            ],
            'kind' => Span::KIND_CLIENT
        ]);
        $this->scope = Tracer::withSpan($span);
    }

    /**
     * Handler for the EndEvent request lifecycle event. Ends the current span which should be
     * the span started in the BeforeEvent handler.
     *
     * @param EndEvent $event A terminal event that is emitted when a request transaction has ended
     */
    public function onEnd(EndEvent $event)
    {
        $this->scope->close();
    }
}
