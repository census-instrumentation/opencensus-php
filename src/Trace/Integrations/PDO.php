<?php
/**
 * Copyright 2017 Google Inc. All Rights Reserved.
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
 * This class handles instrumenting PDO requests using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\PDO
 *
 * PDO::load();
 */
class PDO implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to the PDO requests
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            return;
        }

        // public int PDO::exec(string $query)
        opencensus_trace_method('PDO', 'exec', function ($scope, $query) {
            return [
                'labels' => ['query' => $query]
            ];
        });

        // public PDOStatement PDO::query(string $query)
        // public PDOStatement PDO::query(string $query, int PDO::FETCH_COLUMN, int $colno)
        // public PDOStatement PDO::query(string $query, int PDO::FETCH_CLASS, string $classname, array $ctorargs)
        // public PDOStatement PDO::query(string $query, int PDO::FETCH_INFO, object $object)
        opencensus_trace_method('PDO', 'query', function ($scope, $query) {
            return [
                'labels' => ['query' => $query]
            ];
        });

        // public bool PDO::commit ( void )
        opencensus_trace_method('PDO', 'commit');

        // public PDO::__construct(string $dsn [, string $username [, string $password [, array $options]]])
        opencensus_trace_method('PDO', '__construct', function ($scope, $dsn) {
            return [
                'labels' => ['dsn' => $dsn]
            ];
        });

        // public bool PDOStatement::execute([array $params])
        opencensus_trace_method('PDOStatement', 'execute', function ($scope) {
            return [
                'labels' => ['query' => $scope->queryString]
            ];
        });
    }

    public static function handleQuery($scope, $query)
    {
        return [
            'labels' => ['query' => $query]
        ];
    }
}
