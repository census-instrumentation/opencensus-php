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

use Illuminate\View\Engines\CompilerEngine;

/**
 * This class handles instrumenting the Laravel framework's standard stack using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Laravel;
 *
 * Laravel::load();
 * ```
 */
class Laravel implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to the Laravel framework
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Laravel integrations.', E_USER_WARNING);
            return;
        }

        Eloquent::load();

        // Create a trace span for every template rendered
        // public function get($path, array $data = array())
        opencensus_trace_method(CompilerEngine::class, 'get', function ($scope, $path, $data) {
            return [
                'name' => 'laravel/view',
                'attributes' => [
                    'path' => $path
                ]
            ];
        });
    }
}
