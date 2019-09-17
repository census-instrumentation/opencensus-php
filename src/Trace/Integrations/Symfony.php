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

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * This class handles instrumenting the Symfony framework's standard stack using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Symfony;
 *
 * Symfony::load();
 * ```
 */
class Symfony implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to the Symfony framework
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Symfony integrations.', E_USER_WARNING);
            return;
        }

        Doctrine::load();

        // public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
        opencensus_trace_method(HttpKernel::class, 'handle', function ($kernel, $request) {
            return [
                'name' => 'kernel/handle'
            ];
        });

        // public function dispatch($event/*, string $eventName = null*/);
        opencensus_trace_method(EventDispatcher::class, 'dispatch', function ($dispatcher, $event, $eventName = null) {
            if (!isset($eventName)) {
                $eventName = \is_object($event) ? \get_class($event) : $event;
            }
            return [
                'name' => $eventName,
                'attributes' => ['eventName' => $eventName]
            ];
        });
    }
}
