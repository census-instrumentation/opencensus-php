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

namespace OpenCensus\Tests\Unit\Trace;

use OpenCensus\Trace\Status;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class StatusTest extends TestCase
{
    public function correctHttpStatuses()
    {
        return [
            [200, 0],
            [201, 0],
            [299, 0],
            [300, 0],
            [302, 0],
            [399, 0],
            [499, 1],
            [500, 2],
            [504, 4],
            [404, 5],
            [409, 6],
            [403, 7],
            [429, 8],
            [501, 12],
            [503, 14],
            [401, 16],
            [999, 2],
        ];
    }

    /**
     * @dataProvider correctHttpStatuses
     * @param int $httpStatus
     * @param int $expectedCode
     */
    public function testReturnsCorrectStatusFromHttp(int $httpStatus, int $expectedCode)
    {
        $status = Status::fromHTTPStatus($httpStatus);
        $this->assertSame($expectedCode, $status->code());
    }
}
