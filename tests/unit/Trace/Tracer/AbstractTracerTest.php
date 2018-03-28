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

use OpenCensus\Trace\MessageEvent;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\SpanContext;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
abstract class AbstractTracerTest extends TestCase
{
    abstract protected function getTracerClass();

    public function testMaintainsContext()
    {
        $class = $this->getTracerClass();
        $parentSpanId = 12345;
        $initialContext = new SpanContext('traceid', $parentSpanId);
        $tracer = new $class($initialContext);
        $context = $tracer->spanContext();

        $this->assertEquals('traceid', $context->traceId());
        $this->assertEquals($parentSpanId, $context->spanId());

        $tracer->inSpan(['name' => 'test'], function () use ($parentSpanId, $tracer) {
            $context = $tracer->spanContext();
            $this->assertNotEquals($parentSpanId, $context->spanId());
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $spanData = $spans[0]->spanData();
        $this->assertEquals('test', $spanData->name());
        $this->assertEquals($parentSpanId, $spanData->parentSpanId());
    }

    public function testAddsAttributesToCurrentSpan()
    {
        $class = $this->getTracerClass();
        $tracer = new $class();
        $tracer->inSpan(['name' => 'root'], function () use ($tracer) {
            $tracer->inSpan(['name' => 'inner'], function () use ($tracer) {
                $tracer->addAttribute('foo', 'bar');
            });
        });

        $spans = $tracer->spans();
        $this->assertCount(2, $spans);
        $spanData = $spans[1]->spanData();
        $this->assertEquals('inner', $spanData->name());
        $attributes = $spanData->attributes();
        $this->assertArrayHasKey('foo', $attributes);
        $this->assertEquals('bar', $attributes['foo']);
    }

    public function testAddsAttributesToRootSpan()
    {
        $class = $this->getTracerClass();
        $tracer = new $class();
        $rootSpan = $tracer->startSpan(['name' => 'root']);
        $scope = $tracer->withSpan($rootSpan);
        $tracer->inSpan(['name' => 'inner'], function () use ($tracer, $rootSpan) {
            $tracer->addAttribute('foo', 'bar', ['span' => $rootSpan]);
        });
        $scope->close();

        $spans = $tracer->spans();
        $this->assertCount(2, $spans);
        $spanData = $spans[0]->spanData();
        $this->assertEquals('root', $spanData->name());
        $attributes = $spanData->attributes();
        $this->assertArrayHasKey('foo', $attributes);
        $this->assertEquals('bar', $attributes['foo']);
    }

    public function testPersistsBacktrace()
    {
        $class = $this->getTracerClass();
        $tracer = new $class();
        $tracer->inSpan(['name' => 'test'], function () {});
        $spanData = $tracer->spans()[0]->spanData();;
        $stackframe = $spanData->stackTrace()[0];
        $this->assertEquals('testPersistsBacktrace', $stackframe['function']);
        $this->assertEquals(self::class, $stackframe['class']);
    }

    public function testWithSpan()
    {
        $span = new Span(['name' => 'foo']);
        $class = $this->getTracerClass();
        $tracer = new $class();

        $this->assertNull($tracer->spanContext()->spanId());
        $scope = $tracer->withSpan($span);
        $this->assertEquals($span->spanId(), $tracer->spanContext()->spanId());
        $scope->close();
        $this->assertNull($tracer->spanContext()->spanId());
    }

    public function testSetStartTime()
    {
        $time = microtime(true) - 10;
        $span = new Span(['name' => 'foo', 'startTime' => $time]);
        $class = $this->getTracerClass();
        $tracer = new $class();
        $scope = $tracer->withSpan($span);
        usleep(100);
        $scope->close();

        $this->assertEquivalentTimestamps(
            $span->spanData()->startTime(),
            $tracer->spans()[0]->spanData()->startTime()
        );
    }

    public function testAddsAnnotations()
    {
        $class = $this->getTracerClass();
        $tracer = new $class();
        $tracer->inSpan(['name' => 'root'], function () use ($tracer) {
            $tracer->addAnnotation('some root annotation', ['attributes' => ['foo' => 'bar']]);
            $tracer->inSpan(['name' => 'inner'], function () use ($tracer) {
                $tracer->addAnnotation('some inner annotation');
            });
        });

        $spans = $tracer->spans();
        $rootSpanData = $spans[0]->spanData();
        $this->assertCount(1, $rootSpanData->timeEvents());
        $innerSpanData = $spans[1]->spanData();
        $this->assertCount(1, $innerSpanData->timeEvents());
    }

    public function testAddsAnnotationToRootSpan()
    {
        $class = $this->getTracerClass();
        $tracer = new $class();
        $rootSpan = $tracer->startSpan(['name' => 'root']);
        $scope = $tracer->withSpan($rootSpan);
        $tracer->inSpan(['name' => 'inner'], function () use ($tracer, $rootSpan) {
            $tracer->addAnnotation('some root annotation', [
                'attributes' => ['foo' => 'bar'],
                'span' => $rootSpan
            ]);
        });
        $scope->close();

        $spans = $tracer->spans();
        $this->assertCount(2, $spans);

        $spanData = $spans[0]->spanData();
        $this->assertEquals('root', $spanData->name());
        $this->assertCount(1, $spanData->timeEvents());
    }

    public function testAddsLinks()
    {
        $class = $this->getTracerClass();
        $tracer = new $class();
        $tracer->inSpan(['name' => 'root'], function () use ($tracer) {
            $tracer->addLink('traceid', 'spanid', ['attributes' => ['foo' => 'bar']]);
            $tracer->inSpan(['name' => 'inner'], function () use ($tracer) {
                $tracer->addLink('traceid', 'spanid');
            });
        });

        $spans = $tracer->spans();
        $rootSpanData = $spans[0]->spanData();
        $this->assertCount(1, $rootSpanData->links());
        $innerSpanData = $spans[1]->spanData();
        $this->assertCount(1, $innerSpanData->links());
    }

    public function testAddsLinkToRootSpan()
    {
        $class = $this->getTracerClass();
        $tracer = new $class();
        $rootSpan = $tracer->startSpan(['name' => 'root']);
        $scope = $tracer->withSpan($rootSpan);
        $tracer->inSpan(['name' => 'inner'], function () use ($tracer, $rootSpan) {
            $tracer->addLink('traceid', 'spanid', [
                'span' => $rootSpan
            ]);
        });
        $scope->close();

        $spans = $tracer->spans();
        $this->assertCount(2, $spans);
        $spanData = $spans[0]->spanData();

        $this->assertEquals('root', $spanData->name());
        $this->assertCount(1, $spanData->links());
    }

    public function testAddMessageEvents()
    {
        $class = $this->getTracerClass();
        $tracer = new $class();
        $tracer->inSpan(['name' => 'root'], function () use ($tracer) {
            $tracer->addMessageEvent(MessageEvent::TYPE_SENT, 'id1', ['uncompressedSize' => 1234, 'compressedSize' => 1000]);
            $tracer->inSpan(['name' => 'inner'], function () use ($tracer) {
                $tracer->addMessageEvent(MessageEvent::TYPE_RECEIVED, 'id2');
            });
        });

        $spans = $tracer->spans();
        $rootSpanData = $spans[0]->spanData();
        $this->assertCount(1, $rootSpanData->timeEvents());
        $innerSpanData = $spans[1]->spanData();
        $this->assertCount(1, $innerSpanData->timeEvents());
    }

    public function testAddsMessageEventToRootSpan()
    {
        $class = $this->getTracerClass();
        $tracer = new $class();
        $rootSpan = $tracer->startSpan(['name' => 'root']);
        $scope = $tracer->withSpan($rootSpan);
        $tracer->inSpan(['name' => 'inner'], function () use ($tracer, $rootSpan) {
            $tracer->addMessageEvent(MessageEvent::TYPE_RECEIVED, 'id2', [
                'span' => $rootSpan
            ]);
        });
        $scope->close();

        $spans = $tracer->spans();
        $this->assertCount(2, $spans);
        $spanData = $spans[0]->spanData();

        $this->assertEquals('root', $spanData->name());
        $this->assertCount(1, $spanData->timeEvents());
    }

    public function testInSpanSetsDefaultStartTime()
    {
        $class = $this->getTracerClass();
        $tracer = new $class();
        $tracer->inSpan(['name' => 'foo'], function () {
            // do nothing
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $spanData = $spans[0]->spanData();

        // #131 - Span should be initialized with current time, not the epoch.
        $this->assertNotEquals(0, $spanData->startTime()->getTimestamp());
    }

    public function testStackTraceShouldBeSet()
    {
        $class = $this->getTracerClass();
        $tracer = new $class();
        $tracer->inSpan(['name' => 'foo'], function () {
            // do nothing
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $spanData = $spans[0]->spanData();

        $this->assertInternalType('array', $spanData->stackTrace());
        $this->assertNotEmpty($spanData->stackTrace());
    }

    public function testAttributesShouldBeSet()
    {
        $class = $this->getTracerClass();
        $tracer = new $class();
        $tracer->inSpan(['name' => 'foo'], function () {
            // do nothing
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $spanData = $spans[0]->spanData();

        $this->assertInternalType('array', $spanData->attributes());
        $this->assertEmpty($spanData->attributes());
    }

    public function testLinksShouldBeSet()
    {
        $class = $this->getTracerClass();
        $tracer = new $class();
        $tracer->inSpan(['name' => 'foo'], function () {
            // do nothing
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $spanData = $spans[0]->spanData();

        $this->assertInternalType('array', $spanData->links());
        $this->assertEmpty($spanData->links());
    }

    public function testTimeEventsShouldBeSet()
    {
        $class = $this->getTracerClass();
        $tracer = new $class();
        $tracer->inSpan(['name' => 'foo'], function () {
            // do nothing
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $spanData = $spans[0]->spanData();

        $this->assertInternalType('array', $spanData->timeEvents());
        $this->assertEmpty($spanData->timeEvents());
    }

    private function assertEquivalentTimestamps($expected, $value)
    {
        $this->assertEquals((float)($expected->format('U.u')), (float)($value->format('U.u')), '', 0.000001);
    }
}
