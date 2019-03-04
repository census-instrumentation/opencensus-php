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

namespace OpenCensus\Trace;

/**
 * The `Status` type defines a logical error model that is suitable for
 * different programming environments, including REST APIs and RPC APIs. It is
 * used by [gRPC](https://github.com/grpc).
 */
class Status
{
    /**
     * The constants are taken from the Status documentation
     * https://opencensus.io/tracing/span/status/#status
     */
    public const OK = 0;
    public const CANCELLED = 1;
    public const UNKNOWN = 2;
    public const INVALID_ARGUMENT = 3;
    public const DEADLINE_EXCEEDED = 4;
    public const NOT_FOUND = 5;
    public const ALREADY_EXISTS = 6;
    public const PERMISSION_DENIED = 7;
    public const RESOURCE_EXHAUSTED = 8;
    public const FAILED_PRECONDITION = 9;
    public const ABORTED = 10;
    public const OUT_OF_RANGE = 11;
    public const UNIMPLEMENTED = 12;
    public const INTERNAL = 13;
    public const UNAVAILABLE = 14;
    public const DATA_LOSS = 15;
    public const UNAUTHENTICATED = 16;

    /**
     * @var int The status code
     */
    private $code;

    /**
     * @var string A developer-facing error message, which should be in English
     */
    private $message;

    /**
     * Create a new Status object
     *
     * @param int $code The status code
     * @param string $message A developer-facing error message
     */
    public function __construct($code, $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * Returns the status code.
     *
     * @return int
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * Returns the developer-facing error message
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
