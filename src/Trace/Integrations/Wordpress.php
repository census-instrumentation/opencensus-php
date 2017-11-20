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

/**
 * This class handles instrumenting the Wordpress framework's standard stack using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Wordpress;
 *
 * Wordpress::load();
 * ```
 */
class Wordpress implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to the Wordpress framework
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Wordpress integrations.', E_USER_WARNING);
            return;
        }

        Mysql::load();

        $nameClosure = function () {
            if (func_num_args() > 0) {
                return [
                    'attributes' => ['name' => func_get_arg(0)]
                ];
            }
            return [];
        };

        // void function get_sidebar( $name = null )
        opencensus_trace_function('get_sidebar', $nameClosure);

        // void function get_header( $name = null )
        opencensus_trace_function('get_header', $nameClosure);

        // function get_footer( $name = null )
        opencensus_trace_function('get_footer', $nameClosure);

        // bool function load_textdomain( $domain, $mofile )
        opencensus_trace_function('load_textdomain', function ($name, $mofile) {
            return [
                'attributes' => ['name' => $name]
            ];
        });

        // void load_template(string $template, bool $require_once = true)
        opencensus_trace_function('load_template', function ($template) {
            return [
                'attributes' => ['template' => $template]
            ];
        });
    }
}
