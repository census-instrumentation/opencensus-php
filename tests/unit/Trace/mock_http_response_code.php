<?php
/**
 * Copyright 2018 OpenCensus Authors
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

namespace OpenCensus\Trace;

/**
 * A mock function for testing the http_response_code function.
 * See http://us2.php.net/manual/en/function.http-response-code.php
 */

function http_response_code($status = 0)
{
    if (php_sapi_name() === 'cli') {
        if ($status) {
            MockHttpResponseCode::$status = $status;
            return true;
        } else {
            return MockHttpResponseCode::$status ?: false;
        }
    } else {
        $last_status = MockHttpResponseCode::$status ?: 200;
        if ($status) {
            MockHttpResponseCode::$status = $status;
        }
        return $last_status;
    }
}

/**
 * A class for overriding the return value of the mocked http_response_code function.
 */

class MockHttpResponseCode
{
    public static $status = null;
}
