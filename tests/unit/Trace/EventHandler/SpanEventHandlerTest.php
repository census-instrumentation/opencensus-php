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

namespace OpenCensus\Tests\Unit\Trace\EventHandler;

use OpenCensus\Trace\EventHandler\SpanEventHandlerInterface;
use OpenCensus\Trace\Annotation;
use OpenCensus\Trace\Link;
use OpenCensus\Trace\MessageEvent;
use OpenCensus\Trace\Span;
use Prophecy\Argument;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class SpanEventHandlerTest extends TestCase
{
    public function testAddingAttributeTriggersEvent()
    {
        $eventHandler = $this->prophesize(SpanEventHandlerInterface::class);
        $eventHandler->attributeAdded(Argument::that(function ($span) {
            return $span->spanId() == 'aaa';
        }), 'foo', 'bar')->shouldBeCalled();

        $span = new Span([
            'spanId' => 'aaa',
            'eventHandler' => $eventHandler->reveal()
        ]);
        $span->addAttribute('foo', 'bar');
    }

    public function testAddingAnnotationTriggersEvent()
    {
        $annotation = new Annotation('description');

        $eventHandler = $this->prophesize(SpanEventHandlerInterface::class);
        $eventHandler->timeEventAdded(Argument::that(function ($span) {
            return $span->spanId() == 'aaa';
        }), $annotation)->shouldBeCalled();

        $span = new Span([
            'spanId' => 'aaa',
            'eventHandler' => $eventHandler->reveal()
        ]);
        $span->addTimeEvent($annotation);
    }

    public function testAddingMessageEventTriggersEvent()
    {
        $messageEvent = new MessageEvent('type', 'id');

        $eventHandler = $this->prophesize(SpanEventHandlerInterface::class);
        $eventHandler->timeEventAdded(Argument::that(function ($span) {
            return $span->spanId() == 'aaa';
        }), $messageEvent)->shouldBeCalled();

        $span = new Span([
            'spanId' => 'aaa',
            'eventHandler' => $eventHandler->reveal()
        ]);
        $span->addTimeEvent($messageEvent);
    }
}
