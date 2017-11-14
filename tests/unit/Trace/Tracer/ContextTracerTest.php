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

namespace OpenCensus\Tests\Unit\Trace\Tracer;

use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Tracer\ContextTracer;

/**
 * @group trace
 */
class ContextTracerTest extends \PHPUnit_Framework_TestCase
{
    public function testMaintainsContext()
    {
        $parentSpanId = 12345;
        $initialContext = new SpanContext('traceid', $parentSpanId);
        $initialContext->attach();

        $tracer = new ContextTracer();
        $context = SpanContext::current();

        $this->assertEquals('traceid', $context->traceId());
        $this->assertEquals($parentSpanId, $context->spanId());

        $tracer->inSpan(['name' => 'test'], function () use ($parentSpanId) {
            $context = SpanContext::current();
            $this->assertNotEquals($parentSpanId, $context->spanId());
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $span = $spans[0];
        $this->assertEquals('test', $span->name());
        $this->assertEquals($parentSpanId, $span->parentSpanId());
    }

    public function testAddsLabelsToCurrentSpan()
    {
        $tracer = new ContextTracer();
        $tracer->inSpan(['name' => 'root'], function () use ($tracer) {
            $tracer->inSpan(['name' => 'inner'], function () use ($tracer) {
                $tracer->addLabel('foo', 'bar');
            });
        });

        $spans = $tracer->spans();
        $this->assertCount(2, $spans);
        $span = $spans[1];
        $this->assertEquals('inner', $span->name());
        $info = $span->info();
        $this->assertEquals('bar', $info['labels']['foo']);
    }

    public function testAddsLabelsToRootSpan()
    {
        $tracer = new ContextTracer();
        $tracer->inSpan(['name' => 'root'], function () use ($tracer) {
            $tracer->inSpan(['name' => 'inner'], function () use ($tracer) {
                $tracer->addRootLabel('foo', 'bar');
            });
        });

        $spans = $tracer->spans();
        $this->assertCount(2, $spans);
        $span = $spans[0];
        $this->assertEquals('root', $span->name());
        $info = $span->info();
        $this->assertEquals('bar', $info['labels']['foo']);
    }

    public function testPersistsBacktrace()
    {
        $tracer = new ContextTracer();
        $tracer->inSpan(['name' => 'test'], function () {});
        $span = $tracer->spans()[0];
        $stackframe = $span->backtrace()[0];
        $this->assertEquals('testPersistsBacktrace', $stackframe['function']);
        $this->assertEquals(self::class, $stackframe['class']);
    }
}
