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

namespace OpenCensus\Tests\Unit\Trace\Sampler;

use OpenCensus\Trace\Sampler\ProbabilitySampler;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class ProbabilitySamplerTest extends TestCase
{
    /**
     * @dataProvider invalidRates
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidRate($rate)
    {
        $sampler = new ProbabilitySampler($rate);
    }

    public function invalidRates()
    {
        return [
            [-1],
            [10],
            [1.1]
        ];
    }
}
