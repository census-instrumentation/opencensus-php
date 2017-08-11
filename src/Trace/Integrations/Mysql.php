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
 * This class handles instrumenting mysql requests using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Mysql
 *
 * Mysql::load();
 */
class Mysql implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to mysql requests
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            return;
        }

        // mixed mysqli_query ( mysqli $link , string $query [, int $resultmode = MYSQLI_STORE_RESULT ] )
        opencensus_function('mysqli_query', function ($mysqli, $query) {
            return [
                'labels' => ['query' => $query]
            ];
        });

        // mysqli_stmt mysqli_prepare ( mysqli $link , string $query )
        opencensus_function('mysqli_prepare', function ($mysqli, $query) {
            return [
                'labels' => ['query' => $query]
            ];
        });

        // bool mysqli_commit ( mysqli $link [, int $flags [, string $name ]] )
        opencensus_function('mysqli_commit', function ($mysqli) {
            if (func_num_args() > 2) {
                return [
                    'labels' => [
                        'name' => func_get_arg(3)
                    ]
                ];
            } else {
                return [];
            }
        });

        // mysqli mysqli_connect ([ string $host = ini_get("mysqli.default_host")
        //      [, string $username = ini_get("mysqli.default_user")
        //      [, string $passwd = ini_get("mysqli.default_pw")
        //      [, string $dbname = ""
        //      [, int $port = ini_get("mysqli.default_port")
        //      [, string $socket = ini_get("mysqli.default_socket") ]]]]]] )
        opencensus_function('mysqli_connect', function ($host) {
            return [
                'labels' => ['host' => $host]
            ];
        });

        // bool mysqli_stmt_execute ( mysqli_stmt $stmt )
        opencensus_function('mysqli_stmt_execute');

        // mixed mysqli::query ( string $query [, int $resultmode = MYSQLI_STORE_RESULT ] )
        opencensus_method('mysqli', 'query', function ($mysqli, $query) {
            return [
                'labels' => ['query' => $query]
            ];
        });

        // mysqli_stmt mysqli::prepare ( string $query )
        opencensus_method('mysqli', 'prepare', function ($mysqli, $query) {
            return [
                'labels' => ['query' => $query]
            ];
        });

        // bool mysqli::commit ([ int $flags [, string $name ]] )
        opencensus_method('mysqli', 'commit', function ($mysqli) {
            if (func_num_args() > 1) {
                return [
                    'labels' => [
                        'name' => func_get_arg(2)
                    ]
                ];
            } else {
                return [];
            }
        });

        // mysqli::__construct ([ string $host = ini_get("mysqli.default_host")
        //      [, string $username = ini_get("mysqli.default_user")
        //      [, string $passwd = ini_get("mysqli.default_pw")
        //      [, string $dbname = ""
        //      [, int $port = ini_get("mysqli.default_port")
        //      [, string $socket = ini_get("mysqli.default_socket") ]]]]]] )
        opencensus_method('mysqli', '__construct', function ($mysqli, $host) {
            return [
                'labels' => ['host' => $host]
            ];
        });

        // bool mysqli_stmt::execute ( void )
        opencensus_method('mysqli_stmt', 'execute');
    }
}
