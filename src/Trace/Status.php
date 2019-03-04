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

    /**
     * Returns Status from HTTP Code
     *
     * @param int $httpStatus
     * @return Status
     */
    public static function fromHTTPStatus(int $httpStatus) : self
    {
        if ($httpStatus >= 100 && $httpStatus <= 399) {
            return new self(self::OK, sprintf('Not an error, returned on success. Code: %d', $httpStatus));
        }

        switch ($httpStatus) {
            case 400:
                return new self(self::INVALID_ARGUMENT, 'The client specified an invalid argument');
            case 401:
                return new self(self::UNAUTHENTICATED,
                    "The requester doesn’t have valid authentication credentials for the operation");
            case 403:
                return new self(self::PERMISSION_DENIED,
                    "The caller doesn’t have permission to execute the specified operation");
            case 404:
                return new self(self::NOT_FOUND,
                    'Content was not found or request was denied for an entire class of users');
            case 409:
                return new self(self::ALREADY_EXISTS, 'The entity attempted to be created already exists');
            case 429:
                return new self(self::RESOURCE_EXHAUSTED,
                    'The resource has been exhausted e.g. per-user quota exhausted, file system out of space');
            case 499:
                return new self(self::CANCELLED, 'The operation was cancelled, typically by the caller');
            case 500:
                return new self(self::UNKNOWN,
                    "An unknown error raised by APIs that don’t return enough error information");
            case 501:
                return new self(self::UNIMPLEMENTED,
                    'The operation is not implemented or is not supported/enabled for this operation');
            case 503:
                return new self(self::UNAVAILABLE, 'The service is currently available e.g. as a transient condition');
            case 504:
                return new self(self::DEADLINE_EXCEEDED, 'The deadline expired before the operation could succeed');
        }

        return new self(self::UNKNOWN, 'Unknown error');
    }
}
