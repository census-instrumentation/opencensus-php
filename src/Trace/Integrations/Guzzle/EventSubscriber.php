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
use OpenCensus\Trace\Propagator\ArrayHeaders;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Propagator\HttpHeaderPropagator;
use OpenCensus\Trace\Propagator\PropagatorInterface;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\EndEvent;
use GuzzleHttp\Event\SubscriberInterface;
use OpenCensus\Trace\Tracer\TracerInterface;

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
     * @var TracerInterface
     */
    private $tracer;
    /**
     * @var bool
     */
    private $logBody;
    /**
     * @var Scope
     */
    private $scope;
    /**
     * @var Span
     */
    private $span;

    /**
     * Create a new Guzzle event listener that creates trace spans and propagates the current
     * trace context to the downstream request.
     *
     * @param TracerInterface $tracer
     * @param PropagatorInterface $propagator Interface responsible for serializing trace context
     */
    public function __construct(TracerInterface $tracer, PropagatorInterface $propagator = null, bool $logBody = true)
    {
        $this->propagator = $propagator ?: new HttpHeaderPropagator();
        $this->tracer = $tracer;
        $this->logBody = $logBody;
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
        $headers = new ArrayHeaders();
        $this->propagator->inject($this->tracer->spanContext(), $headers);
        $request->setHeaders($headers->toArray());

        $attrHeaders = [];
        foreach ($request->getHeaders() as $name => $values) {
            $attrHeaders['request.' . $name] = implode(', ', $values);
        }

        $this->span = $this->tracer->startSpan([
            'name' => sprintf('Guzzle: %s', $request->getHost()),
            'attributes' => [
                    'http.method' => $request->getMethod(),
                    'http.uri' => $request->getUrl(),
                ] + $attrHeaders,
            'kind' => Span::KIND_CLIENT,
            'sameProcessAsParentSpan' => !empty($this->spans),
        ]);
        $this->scope = $this->tracer->withSpan($this->span);
    }

    /**
     * Handler for the EndEvent request lifecycle event. Ends the current span which should be
     * the span started in the BeforeEvent handler.
     *
     * @param EndEvent $event A terminal event that is emitted when a request transaction has ended
     */
    public function onEnd(EndEvent $event)
    {
        $response = $event->getResponse();
        $exception = $event->getException();

        if ($exception) {
            $this->span->addAttribute('error', 'true');
            $this->span->addAttribute('exception', sprintf('%s: %s', get_class($exception), $exception->getMessage()));
        }

        if ($response === null) {
            $this->scope->close();
            return;
        }

        $statusCode = $response->getStatusCode();
        $this->span->addAttribute('http.status_code', (string)$statusCode);

        // If it's an error, annotate it as such
        if ($statusCode >= 400) {
            $this->span->addAttribute('error', 'true');
        }

        if ($this->logBody) {
            $bodyLength = (int)$response->getHeader('Content-Length');
            if ($bodyLength > 0 && $bodyLength <= 4096) {
                $body = (string)$response->getBody();
            } else {
                $body = 'Either Content-Length is missing, or it is bigger than 4096';
            }
            $this->span->addAttribute('response.body', $body);
        }

        $attrHeaders = [];
        foreach ($response->getHeaders() as $name => $values) {
            $attrHeaders['response.' . $name] = implode(', ', $values);
        }
        $this->span->addAttributes($attrHeaders);

        $this->scope->close();
    }
}
