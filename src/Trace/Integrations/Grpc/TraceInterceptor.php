<?php
/**
 * Copyright 2018 OpenCensus Authors
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

namespace OpenCensus\Trace\Integrations\Grpc;

use Grpc\Interceptor;
use OpenCensus\Trace\Propagator\PropagatorInterface;
use OpenCensus\Trace\Propagator\GrpcMetadataPropagator;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\Tracer;

/**
 * This interceptor creates a Span for each type of gRPC call and injects a
 * metadata attribute to propagate SpanContext to downstream services.
 *
 * Example:
 * ```
 * $interceptor = new TraceInterceptor();
 * $channel = new Grpc\Channel('127.0.0.1:1234');
 * $channel = Grpc\Interceptor::intercept($channel, $interceptor);
 * $client = new MyGrpcClient('127.0.0.1:1234', $channel);
 * ```
 */
class TraceInterceptor extends Interceptor
{
    /* @var PropagatorInterface */
    private $propagator;

    /**
     * Create a new TraceInterceptor.
     *
     * @param PropagatorInterface $propagator The
     */
    public function __construct($propagator = null)
    {
        $this->propagator = $propagator ?: new GrpcMetadataPropagator();
    }

    /**
     * This interceptor is for simple unary requests.
     *
     * @param string $method The name of the method to call
     * @param mixed $argument The argument to the method
     * @param array $metadata A metadata map to send to the server (optional)
     * @param array $options An array of options (optional)
     * @param callable $continuation The next interceptor to call
     */
    public function interceptUnaryUnary(
        $method,
        $argument,
        array $metadata,
        array $options,
        $continuation
    ) {
        $spanOptions = [
            'name' => 'grpc/simpleRequest',
            'attributes' => [
                'method' => $method
            ],
            'kind' => Span::KIND_CLIENT
        ];
        $metadata = $this->injectMetadata($metadata);

        return Tracer::inSpan($spanOptions, $continuation, [$method, $argument, $metadata, $options]);
    }

    /**
     * This interceptor is for client streaming requests.
     *
     * @param string $method The name of the method to call
     * @param array $metadata A metadata map to send to the server (optional)
     * @param array $options An array of options (optional)
     * @param callable $continuation The next interceptor to call
     */
    public function interceptStreamUnary(
        $method,
        array $metadata,
        array $options,
        $continuation
    ) {
        $spanOptions = [
            'name' => 'grpc/clientStreamRequest',
            'attributes' => [
                'method' => $method
            ],
            'kind' => Span::KIND_CLIENT
        ];
        $metadata = $this->injectMetadata($metadata);
        return Tracer::inSpan($spanOptions, $continuation, [$method, $metadata, $options]);
    }

    /**
     * This interceptor is for server streaming requests.
     *
     * @param string $method The name of the method to call
     * @param mixed $argument The argument to the method
     * @param array $metadata A metadata map to send to the server (optional)
     * @param array $options An array of options (optional)
     * @param callable $continuation The next interceptor to call
     */
    public function interceptUnaryStream(
        $method,
        $argument,
        array $metadata,
        array $options,
        $continuation
    ) {
        $spanOptions = [
            'name' => 'grpc/serverStreamRequest',
            'attributes' => [
                'method' => $method
            ],
            'kind' => Span::KIND_CLIENT
        ];
        $metadata = $this->injectMetadata($metadata);
        return Tracer::inSpan($spanOptions, $continuation, [$method, $argument, $metadata, $options]);
    }

    /**
     * This interceptor is for server streaming requests.
     *
     * @param string $method The name of the method to call
     * @param array $metadata A metadata map to send to the server (optional)
     * @param array $options An array of options (optional)
     * @param callable $continuation The next interceptor to call
     */
    public function interceptStreamStream(
        $method,
        array $metadata,
        array $options,
        $continuation
    ) {
        $spanOptions = [
            'name' => 'grpc/bidiRequest',
            'attributes' => [
                'method' => $method
            ],
            'kind' => Span::KIND_CLIENT
        ];
        $metadata = $this->injectMetadata($metadata);
        return Tracer::inSpan($spanOptions, $continuation, [$method, $metadata, $options]);
    }

    private function injectMetadata(array $metadata)
    {
        $context = Tracer::spanContext();
        return $this->propagator->inject($context, $metadata);
    }
}
