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

use OpenCensus\Trace\Span;

/**
 * This class handles instrumenting postgres requests using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Postgres;
 *
 * Postgres::load();
 * ```
 */
class Postgres implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to the postgres requests
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load pg integrations.', E_USER_WARNING);
            return;
        }

        // resource pg_connect ( string $connection_string [, int $connect_type ] )
        // resource pg_pconnect ( string $connection_string [, int $connect_type ] )
        opencensus_trace_function('pg_connect');
        opencensus_trace_function('pg_pconnect');

        // resource pg_query([resource $connection], string $query)
        opencensus_trace_function('pg_query', function () {
            $query = func_num_args() > 1
                ? func_get_arg(1)
                : func_get_arg(0);
            return [
                'attributes' => ['query' => $query],
                'kind' => Span::KIND_CLIENT
            ];
        });

        // resource pg_query_params([resource $connection], $string $query, array $params)
        opencensus_trace_function('pg_query_params', function () {
            $query = func_num_args() > 2
                ? func_get_arg(1)
                : func_get_arg(0);
            return [
                'attributes' => ['query' => $query],
                'kind' => Span::KIND_CLIENT
            ];
        });

        // resource pg_prepare ([ resource $connection ], string $stmtname , string $query )
        opencensus_trace_function('pg_prepare', function () {
            $statementName = func_num_args() > 2
                ? func_get_arg(1)
                : func_get_arg(0);
            $query = func_num_args() > 2
                ? func_get_arg(2)
                : func_get_arg(1);
            return [
                'attributes' => [
                    'statement' => $statementName,
                    'query' => $query
                ],
                'kind' => Span::KIND_CLIENT
            ];
        });

        // resource pg_execute([resource $connection], string $stmtname, array $params)
        opencensus_trace_function('pg_execute', function () {
            $statementName = func_num_args() > 2
                ? func_get_arg(1)
                : func_get_arg(0);
            return [
                'attributes' => ['statement' => $statementName],
                'kind' => Span::KIND_CLIENT
            ];
        });
    }
}
