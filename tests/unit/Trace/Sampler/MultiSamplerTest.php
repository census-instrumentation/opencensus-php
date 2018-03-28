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

use OpenCensus\Trace\Sampler\MultiSampler;
use OpenCensus\Trace\Sampler\SamplerInterface;

/**
 * @group trace
 */
class MultiSamplerTest extends \PHPUnit_Framework_TestCase
{
    public function testNoSamplers()
    {
        $sampler = new MultiSampler();
        $this->assertTrue($sampler->shouldSample());
    }

    public function testSingleSampler()
    {
        $innerSampler = $this->prophesize(SamplerInterface::class);
        $innerSampler->shouldSample()->willReturn(true)->shouldBeCalled();

        $sampler = new MultiSampler([
            $innerSampler->reveal()
        ]);
        $this->assertTrue($sampler->shouldSample());
    }

    public function testMultipleSamplers()
    {
        $innerSampler = $this->prophesize(SamplerInterface::class);
        $innerSampler->shouldSample()->willReturn(true)->shouldBeCalled();
        $innerSampler2 = $this->prophesize(SamplerInterface::class);
        $innerSampler2->shouldSample()->willReturn(true)->shouldBeCalled();

        $sampler = new MultiSampler([
            $innerSampler->reveal(),
            $innerSampler2->reveal()
        ]);
        $this->assertTrue($sampler->shouldSample());
    }

    public function testInnerSamplerFails()
    {
        $innerSampler = $this->prophesize(SamplerInterface::class);
        $innerSampler->shouldSample()->willReturn(true)->shouldBeCalled();
        $innerSampler2 = $this->prophesize(SamplerInterface::class);
        $innerSampler2->shouldSample()->willReturn(false)->shouldBeCalled();

        $sampler = new MultiSampler([
            $innerSampler->reveal(),
            $innerSampler2->reveal()
        ]);
        $this->assertFalse($sampler->shouldSample());
    }
}
