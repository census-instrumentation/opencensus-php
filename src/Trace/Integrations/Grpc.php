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

use Grpc\BaseStub;
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Propagator\GrpcMetadataPropagator;

/**
 * This class handles instrumenting grpc requests using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Grpc
 *
 * Grpc::load();
 * ```
 */
class Grpc implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to grpc requests
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load grpc integrations.', E_USER_WARNING);
            return;
        }

        // protected function _simpleRequest($method, $argument, $deserialize, array $metadata = [],
        //                                   array $options = [])
        opencensus_trace_method(BaseStub::class, '_simpleRequest', function ($stub, $method) {
            return [
                'name' => 'grpc/simpleRequest',
                'labels' => [
                    'host' => $stub->getTarget(),
                    'uri' => $method
                ]
            ];
        });

        // protected function _clientStreamRequest($method, $argument, $deserialize, array $metadata = [],
        //                                         array $options = [])
        opencensus_trace_method(BaseStub::class, '_clientStreamRequest', function ($stub, $method) {
            return [
                'name' => 'grpc/clientStreamRequest',
                'labels' => [
                    'host' => $stub->getTarget(),
                    'uri' => $method
                ]
            ];
        });

        // protected function _serverStreamRequest($method, $argument, $deserialize, array $metadata = [],
        //                                         array $options = [])
        opencensus_trace_method(BaseStub::class, '_serverStreamRequest', function ($stub, $method) {
            return [
                'name' => 'grpc/serverStreamRequest',
                'labels' => [
                    'host' => $stub->getTarget(),
                    'uri' => $method
                ]
            ];
        });

        // protected function _bidiRequest($method, $deserialize, array $metadata = [], array $options = [])
        opencensus_trace_method(BaseStub::class, '_bidiRequest', function ($stub, $method) {
            return [
                'name' => 'grpc/bidiRequest',
                'labels' => [
                    'host' => $stub->getTarget(),
                    'uri' => $method
                ]
            ];
        });
    }

    /**
     * Update metadata handler for grpc clients.
     *
     * Example:
     *
     * ```
     * use OpenCensus\Trace\Integrations\Grpc;
     *
     * # MathClient is the generated client from the proto definition
     * $client = new Math\MathClient($host, ['update_metadata' => [Grpc::class, 'updateMetadata']]);
     *
     * # The call will pass the current trace context using the GrpcMetadataPropagator and BinaryFormatter
     * $call = $client->Div($divArgs);
     * $response = $call->wait();
     * ```
     *
     * @param array $metadata
     * @param string $jwtAuthUri
     * @return array
     */
    public static function updateMetadata($metadata, $jwtAuthUri)
    {
        if ($context = Tracer::context()) {
            $propagator = new GrpcMetadataPropagator();
            $metadata += [
                $propagator->key() => $propagator->formatter()->serialize($context)
            ];
        }
        return $metadata;
    }
}
