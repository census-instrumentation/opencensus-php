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

use OpenCensus\Trace\Exporter\ExporterInterface;
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Tracer\NullTracer;

/**
 * @group trace
 */
class TracerTest extends \PHPUnit_Framework_TestCase
{
    private $reporter;

    public function setUp()
    {
        $this->reporter = $this->prophesize(ExporterInterface::class);
    }

    public function testForceDisabled()
    {
        $rt = Tracer::start($this->reporter->reveal(), [
            'sampler' => ['type' => 'disabled']
        ]);
        $tracer = $rt->tracer();

        $this->assertFalse($tracer->enabled());
        $this->assertInstanceOf(NullTracer::class, $tracer);
    }

    public function testForceEnabled()
    {
        $rt = Tracer::start($this->reporter->reveal(), [
            'sampler' => ['type' => 'enabled']
        ]);
        $tracer = $rt->tracer();

        $this->assertTrue($tracer->enabled());
    }
}
