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

namespace OpenCensus\Tests\Unit\Trace\Sampler;

use OpenCensus\Trace\Sampler\AlwaysSampleSampler;
use OpenCensus\Trace\Sampler\MultiSampler;
use OpenCensus\Trace\Sampler\NeverSampleSampler;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class MultiSamplerTest extends TestCase
{
    public function testNoSamplersShouldSample()
    {
        $sampler = new MultiSampler();

        $this->assertTrue($sampler->shouldSample());
    }

    public function testOneSamplerShouldSample()
    {
        $sampler = new MultiSampler([
            new AlwaysSampleSampler(),
        ]);

        $this->assertTrue($sampler->shouldSample());
    }

    public function testMultipleSamplersShouldSample()
    {
        $sampler = new MultiSampler([
            new AlwaysSampleSampler(),
            new AlwaysSampleSampler(),
        ]);

        $this->assertTrue($sampler->shouldSample());
    }

    public function testOneFromMultipleSamplersFailsShouldNotSample()
    {
        $sampler = new MultiSampler([
            new AlwaysSampleSampler(),
            new NeverSampleSampler(),
        ]);

        $this->assertFalse($sampler->shouldSample());
    }
}
