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

namespace OpenCensus\Tests\Unit\Trace;

use OpenCensus\Trace\Annotation;
use OpenCensus\Trace\Link;
use OpenCensus\Trace\MessageEvent;
use OpenCensus\Trace\SpanData;
use OpenCensus\Trace\Status;

/**
 * @group trace
 */
class SpanDataTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaults()
    {
        $startTime = new \DateTime();
        $endTime = new \DateTime();
        $spanData = new SpanData('span-name', 'aaa', 'bbb', $startTime, $endTime);
        $this->assertEquals('span-name', $spanData->name());
        $this->assertEquals('aaa', $spanData->traceId());
        $this->assertEquals('bbb', $spanData->spanId());
        $this->assertEquals($startTime, $spanData->startTime());
        $this->assertEquals($endTime, $spanData->endTime());
    }

    /**
     * @dataProvider spanDataOptions
     */
    public function testAttributes($key, $value)
    {
        $startTime = new \DateTime();
        $endTime = new \DateTime();
        $spanData = new SpanData('span-name', 'aaa', 'bbb', $startTime, $endTime, [
            $key => $value
        ]);
        $this->assertEquals($value, call_user_func([$spanData, $key]));
    }

    public function spanDataOptions()
    {
        return [
            ['attributes', ['foo' => 'bar']],
            ['timeEvents', [
                new Annotation('description'),
                new MessageEvent(MessageEvent::TYPE_SENT, 'message-id')]
            ],
            ['links', [new Link('traceId', 'spanId')]],
            ['status', new Status(200, 'OK')],
            ['stackTrace', debug_backtrace()]
        ];
    }
}
