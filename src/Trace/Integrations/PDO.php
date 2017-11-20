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
 * This class handles instrumenting PDO requests using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\PDO;
 *
 * PDO::load();
 * ```
 */
class PDO implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to the PDO requests
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load PDO integrations.', E_USER_WARNING);
            return;
        }

        // public int PDO::exec(string $query)
        opencensus_trace_method('PDO', 'exec', [static::class, 'handleQuery']);

        // public PDOStatement PDO::query(string $query)
        // public PDOStatement PDO::query(string $query, int PDO::FETCH_COLUMN, int $colno)
        // public PDOStatement PDO::query(string $query, int PDO::FETCH_CLASS, string $classname, array $ctorargs)
        // public PDOStatement PDO::query(string $query, int PDO::FETCH_INFO, object $object)
        opencensus_trace_method('PDO', 'query', [static::class, 'handleQuery']);

        // public bool PDO::commit ( void )
        opencensus_trace_method('PDO', 'commit');

        // public PDO::__construct(string $dsn [, string $username [, string $password [, array $options]]])
        opencensus_trace_method('PDO', '__construct', [static::class, 'handleConnect']);

        // public bool PDOStatement::execute([array $params])
        opencensus_trace_method('PDOStatement', 'execute', [static::class, 'handleStatementExecute']);
    }

    /**
     * Handle extracting the SQL query from the first argument
     *
     * @internal
     * @param PDO $pdo The connectoin
     * @param string $query The SQL query to extract
     * @return array
     */
    public static function handleQuery($pdo, $query)
    {
        return [
            'attributes' => ['query' => $query]
        ];
    }

    /**
     * Handle extracting the Data Source Name (DSN) from the constructor aruments to PDO
     *
     * @internal
     * @param PDO $pdo
     * @param string $dsn The connection DSN
     * @return array
     */
    public static function handleConnect($pdo, $dsn)
    {
        return [
            'attributes' => ['dsn' => $dsn]
        ];
    }

    /**
     * Handle extracting the SQL query from a PDOStatement instance
     *
     * @internal
     * @param PDOStatement $statement The prepared statement
     * @return array
     */
    public static function handleStatementExecute($statement)
    {
        return [
            'attributes' => ['query' => $statement->queryString]
        ];
    }
}
