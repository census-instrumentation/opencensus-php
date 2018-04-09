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

use OpenCensus\Trace\Annotation;
use OpenCensus\Trace\Link;
use OpenCensus\Trace\MessageEvent;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\RequestHandler;
use OpenCensus\Trace\Exporter\ExporterInterface;
use OpenCensus\Trace\Sampler\SamplerInterface;
use OpenCensus\Trace\Tracer\NullTracer;
use OpenCensus\Trace\Propagator\HttpHeaderPropagator;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class RequestHandlerTest extends TestCase
{
    private $exporter;

    private $sampler;

    public function setUp()
    {
        if (extension_loaded('opencensus')) {
            opencensus_trace_clear();
        }
        $this->exporter = $this->prophesize(ExporterInterface::class);
        $this->sampler = $this->prophesize(SamplerInterface::class);
    }

    public function testCanTrackContext()
    {
        $this->sampler->shouldSample()->willReturn(true);

        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $rt->inSpan(['name' => 'inner'], function () {});
        $rt->onExit();
        $spans = $rt->tracer()->spans();
        $this->assertCount(2, $spans);
        foreach ($spans as $span) {
            $this->assertInstanceOf(Span::class, $span);
            $this->assertNotEmpty($span->spanData()->endTime());
        }
        $spanData1 = $spans[0]->spanData();
        $spanData2 = $spans[1]->spanData();
        $this->assertEquals('main', $spanData1->name());
        $this->assertEquals('inner', $spanData2->name());
        $this->assertEquals($spanData1->spanId(), $spanData2->parentSpanId());
    }

    public function testCanParseParentContext()
    {
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'headers' => [
                    'HTTP_X_CLOUD_TRACE_CONTEXT' => '12345678901234567890123456789012/5555;o=1'
                ],
                'skipReporting' => true
            ]
        );
        $span = $rt->tracer()->spans()[0];
        $this->assertEquals('15b3', $span->spanData()->parentSpanId());
        $context = $rt->tracer()->spanContext();
        $this->assertEquals('12345678901234567890123456789012', $context->traceId());
    }

    public function testForceEnabledContextHeader()
    {
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'headers' => [
                    'HTTP_X_CLOUD_TRACE_CONTEXT' => '12345678901234567890123456789012;o=1'
                ],
                'skipReporting' => true
            ]
        );
        $tracer = $rt->tracer();

        $this->assertTrue($tracer->enabled());
    }

    public function testForceDisabledContextHeader()
    {
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'headers' => [
                    'HTTP_X_CLOUD_TRACE_CONTEXT' => '12345678901234567890123456789012;o=0'
                ],
                'skipReporting' => true
            ]
        );
        $tracer = $rt->tracer();

        $this->assertFalse($tracer->enabled());
        $this->assertInstanceOf(NullTracer::class, $tracer);
    }

    public function testAddsAttributes()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt) {
            $rt->addAttribute('foo', 'bar');
        });
        $scope->close();

        $spanAttributes = array_map(function ($span) {
            return $span->spanData()->attributes();
        }, $rt->tracer()->spans());
        $this->assertEquals([[], [], ['foo' => 'bar']], $spanAttributes);
    }


    public function testAddsAttributesToSpecificSpan()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt, $outer) {
            $rt->addAttribute('foo', 'bar', [
                'span' => $outer
            ]);
        });
        $scope->close();

        $spanAttributes = array_map(function ($span) {
            return $span->spanData()->attributes();
        }, $rt->tracer()->spans());
        $this->assertEquals([[], ['foo' => 'bar'], []], $spanAttributes);
    }

    public function testAddsAttributesToSpecificUnattachedDetachedSpan()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $outer->addAttribute('foo', 'bar');
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt) {
        });
        $scope->close();

        $spanAttributes = array_map(function ($span) {
            return $span->spanData()->attributes();
        }, $rt->tracer()->spans());
        $this->assertEquals([[], ['foo' => 'bar'], []], $spanAttributes);
    }

    public function testAddsAttributesToSpecificDetachedSpan()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt, $outer) {
            $outer->addAttribute('foo', 'bar');
        });
        $scope->close();

        $spanAttributes = array_map(function ($span) {
            return $span->spanData()->attributes();
        }, $rt->tracer()->spans());
        $this->assertEquals([[], ['foo' => 'bar'], []], $spanAttributes);
    }

    public function testAddsAnnotation()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt) {
            $rt->addAnnotation('some message', [
                'attributes' => [
                    'foo' => 'bar'
                ]
            ]);
        });
        $scope->close();

        $spanTimeEvents = array_map(function ($span) {
            return $span->spanData()->timeEvents();
        }, $rt->tracer()->spans());
        $this->assertEquals([0, 0, 1], array_map(function ($timeEvents) {
            return count($timeEvents);
        }, $spanTimeEvents));
        $annotation = $spanTimeEvents[2][0];
        $this->assertInstanceOf(Annotation::class, $annotation);
        $this->assertCount(1, $annotation->attributes());
        $this->assertEquals('bar', $annotation->attributes()['foo']);
    }

    public function testAddsAnnotationToSpecificSpan()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt, $outer) {
            $rt->addAnnotation('some message', [
                'attributes' => [
                    'foo' => 'bar'
                ],
                'span' => $outer
            ]);
        });
        $scope->close();

        $spanTimeEvents = array_map(function ($span) {
            return $span->spanData()->timeEvents();
        }, $rt->tracer()->spans());
        $this->assertEquals([0, 1, 0], array_map(function ($timeEvents) {
            return count($timeEvents);
        }, $spanTimeEvents));
        $annotation = $spanTimeEvents[1][0];
        $this->assertInstanceOf(Annotation::class, $annotation);
        $this->assertCount(1, $annotation->attributes());
        $this->assertEquals('bar', $annotation->attributes()['foo']);
    }

    public function testAddsAnnotationToSpecificUnattachedDetachedSpan()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $outer->addTimeEvent(new Annotation('some message', [
            'attributes' => [
                'foo' => 'bar'
            ]
        ]));
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt) {
        });
        $scope->close();

        $spanTimeEvents = array_map(function ($span) {
            return $span->spanData()->timeEvents();
        }, $rt->tracer()->spans());
        $this->assertEquals([0, 1, 0], array_map(function ($timeEvents) {
            return count($timeEvents);
        }, $spanTimeEvents));
        $annotation = $spanTimeEvents[1][0];
        $this->assertInstanceOf(Annotation::class, $annotation);
        $this->assertCount(1, $annotation->attributes());
        $this->assertEquals('bar', $annotation->attributes()['foo']);
    }

    public function testAddsAnnotationToSpecificDetachedSpan()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt, $outer) {
            $outer->addTimeEvent(new Annotation('some message', [
                'attributes' => [
                    'foo' => 'bar'
                ]
            ]));
        });
        $scope->close();

        $spanTimeEvents = array_map(function ($span) {
            return $span->spanData()->timeEvents();
        }, $rt->tracer()->spans());
        $this->assertEquals([0, 1, 0], array_map(function ($timeEvents) {
            return count($timeEvents);
        }, $spanTimeEvents));
        $annotation = $spanTimeEvents[1][0];
        $this->assertInstanceOf(Annotation::class, $annotation);
        $this->assertCount(1, $annotation->attributes());
        $this->assertEquals('bar', $annotation->attributes()['foo']);
    }

    public function testAddsLink()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt) {
            $rt->addLink('aaa', 'bbb', [
                'type' => Link::TYPE_PARENT_LINKED_SPAN,
                'attributes' => [
                    'foo' => 'bar'
                ]
            ]);
        });
        $scope->close();

        $spanLinks = array_map(function ($span) {
            return $span->spanData()->links();
        }, $rt->tracer()->spans());
        $this->assertEquals([0, 0, 1], array_map(function ($links) {
            return count($links);
        }, $spanLinks));
        $links = $spanLinks[2];
        $this->assertEquals('aaa', $links[0]->traceId());
        $this->assertEquals('bbb', $links[0]->spanId());
        $this->assertEquals('PARENT_LINKED_SPAN', $links[0]->type());
        $this->assertCount(1, $links[0]->attributes());
        $this->assertEquals('bar', $links[0]->attributes()['foo']);
    }

    public function testAddsLinkToSpecificSpan()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt, $outer) {
            $rt->addLink('aaa', 'bbb', [
                'type' => Link::TYPE_PARENT_LINKED_SPAN,
                'attributes' => [
                    'foo' => 'bar'
                ],
                'span' => $outer
            ]);
        });
        $scope->close();

        $spanLinks = array_map(function ($span) {
            return $span->spanData()->links();
        }, $rt->tracer()->spans());
        $this->assertEquals([0, 1, 0], array_map(function ($links) {
            return count($links);
        }, $spanLinks));
        $links = $spanLinks[1];
        $this->assertEquals('aaa', $links[0]->traceId());
        $this->assertEquals('bbb', $links[0]->spanId());
        $this->assertEquals('PARENT_LINKED_SPAN', $links[0]->type());
        $this->assertCount(1, $links[0]->attributes());
        $this->assertEquals('bar', $links[0]->attributes()['foo']);
    }

    public function testAddsLinkToSpecificUnattachedDetachedSpan()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $outer->addLink(new Link('aaa', 'bbb', [
            'type' => Link::TYPE_PARENT_LINKED_SPAN,
            'attributes' => [
                'foo' => 'bar'
            ]
        ]));
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt) {
        });
        $scope->close();

        $spanLinks = array_map(function ($span) {
            return $span->spanData()->links();
        }, $rt->tracer()->spans());
        $this->assertEquals([0, 1, 0], array_map(function ($links) {
            return count($links);
        }, $spanLinks));
        $links = $spanLinks[1];
        $this->assertEquals('aaa', $links[0]->traceId());
        $this->assertEquals('bbb', $links[0]->spanId());
        $this->assertEquals('PARENT_LINKED_SPAN', $links[0]->type());
        $this->assertCount(1, $links[0]->attributes());
        $this->assertEquals('bar', $links[0]->attributes()['foo']);
    }

    public function testAddsLinkToSpecificDetachedSpan()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt, $outer) {
            $outer->addLink(new Link('aaa', 'bbb', [
                'type' => Link::TYPE_PARENT_LINKED_SPAN,
                'attributes' => [
                    'foo' => 'bar'
                ]
            ]));
        });
        $scope->close();

        $spanLinks = array_map(function ($span) {
            return $span->spanData()->links();
        }, $rt->tracer()->spans());
        $this->assertEquals([0, 1, 0], array_map(function ($links) {
            return count($links);
        }, $spanLinks));
        $links = $spanLinks[1];
        $this->assertEquals('aaa', $links[0]->traceId());
        $this->assertEquals('bbb', $links[0]->spanId());
        $this->assertEquals('PARENT_LINKED_SPAN', $links[0]->type());
        $this->assertCount(1, $links[0]->attributes());
        $this->assertEquals('bar', $links[0]->attributes()['foo']);
    }

    public function testAddsMessageEvent()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt) {
            $rt->addMessageEvent(MessageEvent::TYPE_SENT, 'message-id', [
                'compressedSize' => 123,
                'uncompressedSize' => 234
            ]);
        });
        $scope->close();

        $spanTimeEvents = array_map(function ($span) {
            return $span->spanData()->timeEvents();
        }, $rt->tracer()->spans());
        $this->assertEquals([0, 0, 1], array_map(function ($timeEvents) {
            return count($timeEvents);
        }, $spanTimeEvents));
        $messageEvent = $spanTimeEvents[2][0];
        $this->assertInstanceOf(MessageEvent::class, $messageEvent);
        $this->assertEquals('SENT', $messageEvent->type());
        $this->assertEquals('message-id', $messageEvent->id());
        $this->assertEquals(123, $messageEvent->compressedSize());
        $this->assertEquals(234, $messageEvent->uncompressedSize());
    }

    public function testAddsMessageEventToSpecificSpan()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt, $outer) {
            $rt->addMessageEvent(MessageEvent::TYPE_SENT, 'message-id', [
                'compressedSize' => 123,
                'uncompressedSize' => 234,
                'span' => $outer
            ]);
        });
        $scope->close();

        $spanTimeEvents = array_map(function ($span) {
            return $span->spanData()->timeEvents();
        }, $rt->tracer()->spans());
        $this->assertEquals([0, 1, 0], array_map(function ($timeEvents) {
            return count($timeEvents);
        }, $spanTimeEvents));
        $messageEvent = $spanTimeEvents[1][0];
        $this->assertInstanceOf(MessageEvent::class, $messageEvent);
        $this->assertEquals('SENT', $messageEvent->type());
        $this->assertEquals('message-id', $messageEvent->id());
        $this->assertEquals(123, $messageEvent->compressedSize());
        $this->assertEquals(234, $messageEvent->uncompressedSize());
    }

    public function testAddsMessageEventToSpecificUnattachedDetachedSpan()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $outer->addTimeEvent(
            new MessageEvent(
                MessageEvent::TYPE_SENT, 'message-id', [
                    'compressedSize' => 123,
                    'uncompressedSize' => 234
                ]
            )
        );
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt) {
        });
        $scope->close();

        $spanTimeEvents = array_map(function ($span) {
            return $span->spanData()->timeEvents();
        }, $rt->tracer()->spans());
        $this->assertEquals([0, 1, 0], array_map(function ($timeEvents) {
            return count($timeEvents);
        }, $spanTimeEvents));
        $messageEvent = $spanTimeEvents[1][0];
        $this->assertInstanceOf(MessageEvent::class, $messageEvent);
        $this->assertEquals('SENT', $messageEvent->type());
        $this->assertEquals('message-id', $messageEvent->id());
        $this->assertEquals(123, $messageEvent->compressedSize());
        $this->assertEquals(234, $messageEvent->uncompressedSize());
    }

    public function testAddsMessageEventToSpecificDetachedSpan()
    {
        $this->sampler->shouldSample()->willReturn(true);
        $rt = new RequestHandler(
            $this->exporter->reveal(),
            $this->sampler->reveal(),
            new HttpHeaderPropagator(),
            [
                'skipReporting' => true
            ]
        );
        $outer = $rt->startSpan(['name' => 'outer']);
        $scope = $rt->withSpan($outer);
        $rt->inSpan(['name' => 'inner'], function () use ($rt, $outer) {
            $outer->addTimeEvent(
                new MessageEvent(
                    MessageEvent::TYPE_SENT, 'message-id', [
                        'compressedSize' => 123,
                        'uncompressedSize' => 234
                    ]
                )
            );
        });
        $scope->close();

        $spanTimeEvents = array_map(function ($span) {
            return $span->spanData()->timeEvents();
        }, $rt->tracer()->spans());
        $this->assertEquals([0, 1, 0], array_map(function ($timeEvents) {
            return count($timeEvents);
        }, $spanTimeEvents));
        $messageEvent = $spanTimeEvents[1][0];
        $this->assertInstanceOf(MessageEvent::class, $messageEvent);
        $this->assertEquals('SENT', $messageEvent->type());
        $this->assertEquals('message-id', $messageEvent->id());
        $this->assertEquals(123, $messageEvent->compressedSize());
        $this->assertEquals(234, $messageEvent->uncompressedSize());
    }
}
