<?php
/**
 * Copyright 2017 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCensus\Core;

/**
 * This class in an implementation of a generic scope that has something to
 * execute when the scope finishes.
 *
 * Example:
 * ```
 * $scope = RequestTracer::withSpan($span);
 * try {
 *   return do_something();
 * } finally {
 *   $scope->close();
 * }
 * ```
 */
class Scope
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @var array
     */
    private $args;

    /**
     * Creates a new Scope
     *
     * @param callable $callback
     * @param array $args
     */
    public function __construct(callable $callback, array $args = [])
    {
        $this->callback = $callback;
        $this->args = $args;
    }

    /**
     * Close and clean up the scope. Runs the initial callback provided.
     */
    public function close(): void
    {
        call_user_func_array($this->callback, $this->args);
    }
}
